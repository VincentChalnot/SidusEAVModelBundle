<?php

namespace Sidus\EAVModelBundle\Serializer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Tries to find an existing entity based on the provided data, fallback to create a new entity
 */
class EntityProvider
{
    /** @var Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param FamilyInterface        $family
     * @param mixed                  $data
     * @param NameConverterInterface $nameConverter
     *
     * @throws UnexpectedValueException
     *
     * @return DataInterface
     */
    public function getEntity(FamilyInterface $family, $data, NameConverterInterface $nameConverter = null)
    {
        /** @var DataRepository $repository */
        $repository = $this->doctrine->getRepository($family->getDataClass());

        if ($family->isSingleton()) {
            try {
                return $repository->getInstance($family);
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to get singleton for family {$family->getCode()}", 0, $e);
            }
        }

        // In case we are trying to resolve a simple reference
        if (is_scalar($data)) {
            try {
                $entity = $repository->findByIdentifier($family, $data, true);
            } catch (\Exception $e) {
                throw new UnexpectedValueException(
                    "Unable to resolve id/identifier {$data} for family {$family->getCode()}", 0, $e
                );
            }
            if (!$entity) {
                throw new UnexpectedValueException(
                    "No entity found for {$family->getCode()} with identifier '{$data}'"
                );
            }

            return $entity;
        }

        if (!is_array($data) && !$data instanceof \ArrayAccess) {
            throw new UnexpectedValueException('Unable to denormalize data from unknown format');
        }

        // If the id is set, don't even look for the identifier
        if (array_key_exists('id', $data)) {
            return $repository->find($data['id']);
        }

        // Try to resolve the identifier
        $reference = $this->resolveIdentifier($data, $family, $nameConverter);

        if (null !== $reference) {
            try {
                $entity = $repository->findByIdentifier($family, $reference);
                if ($entity) {
                    return $entity;
                }
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to resolve identifier {$reference}", 0, $e);
            }
        }

        return $family->createData();
    }

    /**
     * @param array|\ArrayAccess     $data
     * @param FamilyInterface        $family
     * @param NameConverterInterface $nameConverter
     *
     * @return mixed
     */
    protected function resolveIdentifier(
        array $data,
        FamilyInterface $family,
        NameConverterInterface $nameConverter = null
    ) {
        if (!$family->getAttributeAsIdentifier()) {
            return null;
        }
        $attributeCode = $family->getAttributeAsIdentifier()->getCode();
        if ($nameConverter) {
            $attributeCode = $nameConverter->normalize($attributeCode);
        }
        if (array_key_exists($attributeCode, $data)) {
            return $data[$attributeCode];
        }

        return null;
    }
}
