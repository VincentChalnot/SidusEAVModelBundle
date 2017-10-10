<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Psr\Log\LoggerInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Entity\ValueRepository;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Sidus\EAVModelBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Used to validate Data entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataValidator extends ConstraintValidator
{
    use TranslatableTrait;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var string */
    protected $dataClass;

    /** @var Registry */
    protected $doctrine;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param string              $dataClass
     * @param FamilyRegistry      $familyRegistry
     * @param TranslatorInterface $translator
     * @param Registry            $doctrine
     * @param LoggerInterface     $logger
     */
    public function __construct(
        $dataClass,
        FamilyRegistry $familyRegistry,
        TranslatorInterface $translator,
        Registry $doctrine,
        LoggerInterface $logger
    ) {
        $this->dataClass = $dataClass;
        $this->familyRegistry = $familyRegistry;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param DataInterface $data       The value that should be validated
     * @param Constraint    $constraint The constraint for the validation
     *
     * @throws Exception
     *
     * @return ConstraintViolationListInterface
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
                $this->buildAttributeViolation(
                    $data,
                    $context,
                    $attribute,
                    'required',
                    $data->get($attribute->getCode())
                );
            }
            if ($attribute->isUnique()) {
                $this->checkUnique($context, $attribute, $data);
            }

            if ($attribute->getOption('allowed_families')) {
                $this->validateFamilies($context, $attribute, $data);
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
        $family = $data->getFamily();
        $valueData = $data->get($attribute->getCode());

        if (null === $valueData) { // Do not check uniqueness for null values
            return;
        }

        $query = [
            'attributeCode' => $attribute->getCode(),
            'familyCode' => $family->getCode(),
            $attribute->getType()->getDatabaseType() => $valueData,
        ];
        if ($attribute->getOption('global_unique')) {
            unset($query['familyCode']);
        }
        if ($attribute->getOption('unique_families')) {
            $query['familyCode'] = $attribute->getOption('unique_families');
        }

        /** @var ValueRepository $repo */
        $repo = $this->doctrine->getRepository($family->getValueClass());
        $values = $repo->findBy($query);

        /** @var ValueInterface $value */
        foreach ($values as $value) {
            if (!$value->getData()) {
                $this->logger->critical(
                    "Very weird Doctrine behavior: missing data for value #{$value->getIdentifier()}"
                );

                continue;
            }
            if ($value->getData()->getId() !== $data->getId()) {
                $this->buildAttributeViolation($data, $context, $attribute, 'unique', $valueData);

                return;
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param DataInterface             $data
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \InvalidArgumentException
     * @throws \Sidus\EAVModelBundle\Exception\ContextException
     * @throws \Sidus\EAVModelBundle\Exception\InvalidValueDataException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     */
    protected function validateFamilies(
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        DataInterface $data
    ) {
        $allowedFamilies = [];
        $allowedFamilyCodes = $attribute->getOption('allowed_families');
        if ($allowedFamilyCodes) {
            if (!is_array($allowedFamilyCodes)) {
                $allowedFamilyCodes = [$allowedFamilyCodes];
            }
            /** @var array $allowedFamilyCodes */
            foreach ($allowedFamilyCodes as $familyCode) {
                $allowedFamilies[$familyCode] = $this->familyRegistry->getFamily($familyCode);
            }
        }
        if (0 === count($allowedFamilies)) {
            return;
        }

        $valueData = $data->get($attribute->getCode());
        if (null === $valueData) {
            return;
        }
        if (!$attribute->isCollection()) {
            $valueData = [$valueData];
        }
        /** @var array $valueData */
        foreach ($valueData as $value) {
            if (!$value instanceof DataInterface) {
                $this->buildAttributeViolation($data, $context, $attribute, 'invalid_data', $value);

                continue;
            }
            if (!array_key_exists($value->getFamilyCode(), $allowedFamilies)) {
                $this->buildAttributeViolation($data, $context, $attribute, 'invalid_family', $value);
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param DataInterface             $data
     *
     * @throws \Symfony\Component\Validator\Exception\MappingException
     * @throws \InvalidArgumentException
     * @throws \Sidus\EAVModelBundle\Exception\ContextException
     * @throws \Sidus\EAVModelBundle\Exception\InvalidValueDataException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     */
    protected function validateRules(
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        DataInterface $data
    ) {
        $valueData = $data->get($attribute->getCode());
        $loader = new BaseLoader();

        foreach ($loader->loadCustomConstraints($attribute->getValidationRules()) as $constraint) {
            $violations = $context->getValidator()->validate($valueData, $constraint, $context->getGroup());
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                /** @noinspection DisconnectedForeachInstructionInspection */
                $path = $attribute->getCode();
                if ($attribute->getType()->isEmbedded()) {
                    if (!$attribute->isCollection()) {
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
                    $this->buildAttributeViolation(
                        $data,
                        $context,
                        $attribute,
                        $this->getConstraintType($constraint),
                        $valueData,
                        $path
                    );
                }
            }
        }
    }

    /**
     * @param DataInterface             $data
     * @param ExecutionContextInterface $context
     * @param AttributeInterface        $attribute
     * @param string                    $type
     * @param mixed                     $invalidValue
     * @param string                    $path
     *
     * @throws \InvalidArgumentException
     */
    protected function buildAttributeViolation(
        DataInterface $data,
        ExecutionContextInterface $context,
        AttributeInterface $attribute,
        $type,
        $invalidValue = null,
        $path = null
    ) {
        if (null === $path) {
            $path = $attribute->getCode();
        }
        $context->buildViolation($this->buildMessage($data, $attribute, $type))
            ->atPath($path)
            ->setInvalidValue($invalidValue)
            ->addViolation();
    }

    /**
     * @param DataInterface      $data
     * @param AttributeInterface $attribute
     * @param string             $type
     *
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     *
     * @return string
     */
    protected function buildMessage(DataInterface $data, AttributeInterface $attribute, $type)
    {
        return $this->tryTranslate(
            [
                "eav.family.{$data->getFamilyCode()}.attribute.{$attribute->getCode()}.validation.{$type}",
                "eav.attribute.{$attribute->getCode()}.validation.{$type}",
                "eav.validation.{$type}",
            ],
            [
                '%attribute%' => $this->translator->trans((string) $attribute),
                '%family%' => $this->translator->trans((string) $data->getFamily()),
            ],
            $type
        );
    }

    /**
     * Get the constraint type (e.g. "email"), used for error message
     *
     * @see https://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name#19909556
     *
     * @param Constraint $constraint
     *
     * @return string
     */
    protected function getConstraintType(Constraint $constraint): string
    {
        $reflect = new \ReflectionClass($constraint);

        return strtolower($reflect->getShortName());
    }
}
