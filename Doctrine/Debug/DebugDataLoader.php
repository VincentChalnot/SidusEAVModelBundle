<?php declare(strict_types=1);

namespace Sidus\EAVModelBundle\Doctrine\Debug;

use Sidus\EAVModelBundle\Doctrine\OptimizedDataLoader;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Wraps the DataLoader to collect data about loaded entities
 */
class DebugDataLoader extends OptimizedDataLoader
{
    /**
     * {@inheritDoc}
     */
    public function load($entities, $depth = 1)
    {
        if (!\is_array($entities) && !$entities instanceof \Traversable) {
            throw new \InvalidArgumentException(self::E_MSG);
        }
        /** @var CollectedDataNode[] $collectedNodes */
        $collectedNodes = [];
        foreach ($entities as $entity) {
            if ($entity instanceof DataInterface) {
                $collectedNodes[] = CollectedDataNode::createOrGetNode($entity, true);
            }
        }

        parent::load($entities, $depth);

        foreach ($collectedNodes as $node) {
            $node->terminate();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function appendRelatedEntities(
        AttributeInterface $attribute,
        DataInterface $entity
    ) {
        $parentNode = CollectedDataNode::createOrGetNode($entity);
        $relatedEntities = parent::appendRelatedEntities(
            $attribute,
            $entity
        );
        foreach ($relatedEntities as $relatedEntity) {
            $parentNode->addRelatedNode(CollectedDataNode::createOrGetNode($relatedEntity));
        }

        return $relatedEntities;
    }

    /**
     * {@inheritDoc}
     */
    protected function getValues($valueClass, array $entitiesById)
    {
        $values = parent::getValues($valueClass, $entitiesById);
        foreach ($values as $value) {
            CollectedDataNode::createOrGetNode($value->getData())->terminate();
        }

        return $values;
    }
}
