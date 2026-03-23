<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Family\FamilyRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validator for EAV data entities.
 *
 * Validates each attribute according to its validation rules.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataValidator extends ConstraintValidator
{
    /**
     * @param class-string<DataInterface> $dataClass
     */
    public function __construct(
        private readonly string $dataClass,
        private readonly FamilyRegistry $familyRegistry,
        private readonly ?TranslatorInterface $translator,
        private readonly ?EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Data) {
            throw new UnexpectedTypeException($constraint, Data::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof DataInterface) {
            throw new UnexpectedTypeException($value, DataInterface::class);
        }

        $this->validateData($value);
    }

    /**
     * Validates a data entity and its attribute values.
     */
    private function validateData(DataInterface $data): void
    {
        $family = $data->getFamily();

        foreach ($family->getAttributes() as $attribute) {
            $this->validateAttribute($data, $attribute);
        }
    }

    /**
     * Validates a single attribute of the data entity.
     */
    private function validateAttribute(DataInterface $data, AttributeInterface $attribute): void
    {
        try {
            $value = $data->get($attribute->getCode());
        } catch (\Throwable $e) {
            $this->logger?->warning(
                'Error getting attribute value for validation',
                ['attribute' => $attribute->getCode(), 'exception' => $e]
            );
            return;
        }

        // Check required
        if ($attribute->isRequired() && $this->isEmpty($value)) {
            $this->context->buildViolation('This value is required.')
                ->atPath($attribute->getCode())
                ->addViolation();
            return;
        }

        // Check uniqueness
        if ($attribute->isUnique() && !$this->isEmpty($value)) {
            $this->validateUniqueness($data, $attribute, $value);
        }

        // Apply validation rules
        foreach ($attribute->getValidationRules() as $rule) {
            $this->applyValidationRule($attribute, $value, $rule);
        }

        // Recursively validate embedded data
        if ($attribute->getType()->isEmbedded()) {
            $this->validateEmbeddedValue($attribute, $value);
        }
    }

    /**
     * Validates that a value is unique within the family.
     */
    private function validateUniqueness(DataInterface $data, AttributeInterface $attribute, mixed $value): void
    {
        if ($this->entityManager === null) {
            return;
        }

        $family = $data->getFamily();
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from($this->dataClass, 'e')
            ->join('e.values', 'v')
            ->where('e.family = :family')
            ->andWhere('v.attributeCode = :attrCode')
            ->setParameter('family', $family)
            ->setParameter('attrCode', $attribute->getCode());

        // Determine the value column
        $databaseType = $attribute->getType()->getDatabaseType();
        $qb->andWhere(sprintf('v.%s = :value', $databaseType))
            ->setParameter('value', $value);

        // Exclude the current entity if it has an ID
        $dataId = $data->getId();
        if ($dataId !== null) {
            $qb->andWhere('e.id != :dataId')
                ->setParameter('dataId', $dataId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        if ($count > 0) {
            $this->context->buildViolation('This value is already used.')
                ->atPath($attribute->getCode())
                ->addViolation();
        }
    }

    /**
     * Applies a single validation rule.
     *
     * @param array<string, mixed> $rule
     */
    private function applyValidationRule(AttributeInterface $attribute, mixed $value, array $rule): void
    {
        $validator = $this->context->getValidator();

        foreach ($rule as $constraintName => $options) {
            try {
                $constraintClass = $this->resolveConstraintClass($constraintName);
                $constraint = new $constraintClass($options ?? []);

                $violations = $validator->validate($value, $constraint);

                foreach ($violations as $violation) {
                    $this->context->buildViolation($violation->getMessage())
                        ->atPath($attribute->getCode())
                        ->addViolation();
                }
            } catch (\Throwable $e) {
                $this->logger?->warning(
                    'Error applying validation rule',
                    ['attribute' => $attribute->getCode(), 'constraint' => $constraintName, 'exception' => $e]
                );
            }
        }
    }

    /**
     * Validates embedded data recursively.
     */
    private function validateEmbeddedValue(AttributeInterface $attribute, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $values = $attribute->isCollection() ? $value : [$value];

        foreach ($values as $embeddedData) {
            if ($embeddedData instanceof DataInterface) {
                $violations = $this->context->getValidator()->validate($embeddedData);

                foreach ($violations as $violation) {
                    $path = $attribute->getCode() . '.' . $violation->getPropertyPath();
                    $this->context->buildViolation($violation->getMessage())
                        ->atPath($path)
                        ->addViolation();
                }
            }
        }
    }

    /**
     * Checks if a value is empty.
     */
    private function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        if (is_iterable($value)) {
            foreach ($value as $item) {
                if (!$this->isEmpty($item)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Resolves a constraint class name.
     *
     * @return class-string<Constraint>
     */
    private function resolveConstraintClass(string $name): string
    {
        // If it's already a FQCN, return it
        if (class_exists($name)) {
            return $name;
        }

        // Try Symfony namespace
        $symfonyClass = 'Symfony\\Component\\Validator\\Constraints\\' . $name;
        if (class_exists($symfonyClass)) {
            return $symfonyClass;
        }

        throw new \InvalidArgumentException(sprintf('Constraint "%s" not found', $name));
    }
}
