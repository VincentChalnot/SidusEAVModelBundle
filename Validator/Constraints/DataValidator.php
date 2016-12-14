<?php

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Entity\ValueRepository;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\IdentifierAttributeType;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Sidus\EAVModelBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @property ExecutionContextInterface $context
 */
class DataValidator extends ConstraintValidator
{
    use TranslatableTrait;

    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var string */
    protected $dataClass;

    /** @var Registry */
    protected $doctrine;

    /**
     * @param string                     $dataClass
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param TranslatorInterface        $translator
     * @param Registry                   $doctrine
     */
    public function __construct(
        $dataClass,
        FamilyConfigurationHandler $familyConfigurationHandler,
        TranslatorInterface $translator,
        Registry $doctrine
    ) {
        $this->dataClass = $dataClass;
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param DataInterface $data       The value that should be validated
     * @param Constraint    $constraint The constraint for the validation
     *
     * @return ConstraintViolationListInterface
     * @throws Exception
     */
    public function validate($data, Constraint $constraint)
    {
        if (!$data instanceof $this->dataClass) {
            $class = get_class($data);
            throw new \UnexpectedValueException("Can't validate data of class {$class}");
        }
        $context = $this->context; // VERY IMPORTANT ! context will be lost otherwise
        foreach ($data->getFamily()->getAttributes() as $attribute) {
            if ($attribute->isRequired() && $data->isEmpty($attribute)) {
                $this->buildAttributeViolation($context, $attribute, 'required', $data->getValueData($attribute));
            }
            if ($attribute->isUnique()) {
                $this->checkUnique($context, $attribute, $data);
            }
            if (count($attribute->getValidationRules())) {
                $this->validateRules($context, $attribute, $data);
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param DataInterface             $data
     *
     * @throws Exception
     */
    protected function checkUnique(
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        DataInterface $data
    ) {
        $valueData = $data->get($attribute->getCode());

        if ($attribute->getType() instanceof IdentifierAttributeType) {
            /** @var DataRepository $repo */
            $repo = $this->doctrine->getRepository($data->getFamily()->getDataClass());
            $result = $repo->findByIdentifier($data->getFamily(), $valueData);
            if ($result && $result->getId() !== $data->getId()) {
                $this->buildAttributeViolation($context, $attribute, 'unique', $valueData);
            }

            return;
        }

        /** @var ValueRepository $repo */
        $repo = $this->doctrine->getRepository($data->getFamily()->getValueClass());
        $values = $repo->findBy(
            [
                'attributeCode' => $attribute->getCode(),
                $attribute->getType()->getDatabaseType() => $valueData,
            ]
        );
        /** @var ValueInterface $value */
        foreach ($values as $value) {
            if (!$value->getData()) {
                continue; // @warning this should not occur ! Log an error
            }
            if ($value->getData()->getId() !== $data->getId()) {
                $this->buildAttributeViolation($context, $attribute, 'unique', $valueData);

                return;
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param DataInterface             $data
     *
     * @throws Exception
     */
    protected function validateRules(
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        DataInterface $data
    ) {
        if ($attribute->isCollection()) {
            $valueData = $data->getValuesData($attribute);
        } else {
            $valueData = $data->getValueData($attribute);
        }
        $loader = new BaseLoader();
        foreach ($attribute->getValidationRules() as $validationRule) {
            foreach ($validationRule as $item => $options) {
                $constraint = $loader->newConstraint($item, $options);
                $violations = $context->getValidator()->validate($valueData, $constraint, $context->getGroup());
                /** @var ConstraintViolationInterface $violation */
                foreach ($violations as $violation) {
                    /** @noinspection DisconnectedForeachInstructionInspection */
                    $path = $attribute->getCode();
                    if ($attribute->getType()->isEmbedded()) {
                        if (!$attribute->isMultiple()) { // Or isCollection ?
                            $path .= '.';
                        }
                        $path .= $violation->getPropertyPath();
                    }
                    if ($violation->getMessage()) {
                        $context->buildViolation($violation->getMessage())
                            ->atPath($path)
                            ->setInvalidValue($valueData)
                            ->addViolation();
                    } else {
                        $this->buildAttributeViolation($context, $attribute, strtolower($item), $valueData, $path);
                    }
                }
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param string                    $type
     * @param mixed                     $invalidValue
     * @param string                    $path
     *
     * @throws \InvalidArgumentException
     */
    protected function buildAttributeViolation(
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        $type,
        $invalidValue = null,
        $path = null
    ) {
        if (null === $path) {
            $path = $attribute->getCode();
        }
        $context->buildViolation($this->buildMessage($attribute, $type))
            ->atPath($path)
            ->setInvalidValue($invalidValue)
            ->addViolation();
    }

    /**
     * @param AttributeInterface $attribute
     * @param string             $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function buildMessage(AttributeInterface $attribute, $type)
    {
        return $this->tryTranslate(
            [
                "eav.attribute.{$attribute->getCode()}.validation.{$type}",
                "eav.attribute.validation.{$type}",
            ],
            [
                '%attribute%' => $this->translator->trans((string) $attribute),
            ],
            $type
        );
    }
}
