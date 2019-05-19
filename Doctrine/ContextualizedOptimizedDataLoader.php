<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

use Sidus\EAVModelBundle\Entity\ContextualDataInterface;

/**
 * Override of the main OptimizedDataLoader to allow context injection
 */
class ContextualizedOptimizedDataLoader extends OptimizedDataLoader implements ContextualizedDataLoaderInterface
{
    /** @var array */
    protected $currentContext;

    /**
     * @param array $context
     */
    public function setCurrentContext(array $context)
    {
        $this->currentContext = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function load($entities, $depth = 1)
    {
        if (null !== $this->currentContext) {
            if (!\is_array($entities) && !$entities instanceof \Traversable) {
                throw new \InvalidArgumentException(self::E_MSG);
            }

            foreach ($entities as $entity) {
                if ($entity instanceof ContextualDataInterface) {
                    $entity->setCurrentContext($this->currentContext);
                }
            }
        }

        parent::load($entities, $depth);
    }
}
