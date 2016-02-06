<?php

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data as SidusData;
use Sidus\EAVModelBundle\Entity\Value;
use Sidus\EAVModelBundle\Entity\ValueRepository;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Sidus\EAVModelBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
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
     * @param string $dataClass
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param TranslatorInterface $translator
     * @param $doctrine
     */
    public function __construct($dataClass, FamilyConfigurationHandler $familyConfigurationHandler, TranslatorInterface $translator, Registry $doctrine)
    {
        $this->dataClass = $dataClass;
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param SidusData $data The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     * @throws Exception
     */
    public function validate($data, Constraint $constraint)
    {
        if (!$data instanceof $this->dataClass) {
            $class = get_class($data);
            throw new \UnexpectedValueException("Can't validate data of class {$class}");
        }
        foreach ($data->getFamily()->getAttributes() as $attribute) {
            if ($attribute->isRequired() && $data->isEmpty($attribute)) {
                $this->buildAttributeViolation($attribute, 'required');
            }
            if ($attribute->isUnique()) {
                $this->checkUnique($attribute, $data);
            }
            if (count($attribute->getValidationRules())) {
                $this->validateRules($attribute, $data);
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param SidusData $data
     * @throws Exception
     */
    protected function checkUnique(AttributeInterface $attribute, SidusData $data)
    {
        $valueData = $data->getValueData($attribute);
        /** @var ValueRepository $repo */
        $repo = $this->doctrine->getRepository($data->getFamily()->getValueClass());
        $values = $repo->findBy([
            'attributeCode' => $attribute->getCode(),
            $attribute->getType()->getDatabaseType() => $valueData,
        ]);
        /** @var Value $value */
        foreach ($values as $value) {
            if ($value->getData()->getId() !== $data->getId()) {
                $this->buildAttributeViolation($attribute, 'unique');
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param SidusData $data
     * @throws Exception
     */
    protected function validateRules(AttributeInterface $attribute, SidusData $data)
    {
        if ($attribute->isMultiple()) {
            $value = $data->getValuesData($attribute);
        } else {
            $value = $data->getValueData($attribute);
        }
        $loader = new BaseLoader();
        foreach ($attribute->getValidationRules() as $validationRule) {
            foreach ($validationRule as $item => $options) {
                $constraint = $loader->newConstraint($item, $options);
                $violations = $this->context->getValidator()->validate($value, $constraint);
                /** @var ConstraintViolationInterface $violation */
                foreach ($violations as $violation) {
                    if ($violation->getMessage()) {
                        $this->context->buildViolation($violation->getMessage())
                            ->atPath($attribute->getCode())
                            ->addViolation();
                    } else {
                        $this->buildAttributeViolation($attribute, strtolower($item));
                    }
                }
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     * @param string $type
     * @throws \InvalidArgumentException
     */
    protected function buildAttributeViolation(AttributeInterface $attribute, $type)
    {
        $this->context->buildViolation($this->buildMessage($attribute, $type))
            ->atPath($attribute->getCode())
            ->addViolation();
    }

    /**
     * @param AttributeInterface $attribute
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function buildMessage(AttributeInterface $attribute, $type)
    {
        $tId = "eav.attribute.{$attribute->getCode()}.validation.{$type}";
        return $this->tryTranslate([
            $tId,
            "eav.attribute.validation.{$type}",
        ], [
            '%attribute%' => $this->translator->trans((string) $attribute),
        ], $tId);
    }
}
