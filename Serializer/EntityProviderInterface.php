<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Tries to find an existing entity based on the provided data, fallback to create a new entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface EntityProviderInterface
{
    /**
     * @param FamilyInterface        $family
     * @param mixed                  $data
     * @param NameConverterInterface $nameConverter
     *
     * @throws UnexpectedValueException
     *
     * @return DataInterface|null
     */
    public function getEntity(FamilyInterface $family, $data, NameConverterInterface $nameConverter = null);
}
