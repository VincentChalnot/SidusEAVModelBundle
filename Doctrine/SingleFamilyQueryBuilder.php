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

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Build complex doctrine queries with the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SingleFamilyQueryBuilder extends EAVQueryBuilder
{
    /** @var FamilyInterface */
    protected $family;

    /**
     * @param FamilyInterface $family
     * @param QueryBuilder    $queryBuilder
     * @param string          $alias
     */
    public function __construct(FamilyInterface $family, QueryBuilder $queryBuilder, $alias = 'e')
    {
        parent::__construct($queryBuilder, $alias);
        $this->family = $family;

        $queryBuilder
            ->andWhere($alias.'.family = :familyCode')
            ->setParameter('familyCode', $family->getCode());
    }

    /**
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @param string $attributeCode
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attributeByCode($attributeCode)
    {
        $attribute = $this->getFamily()->getAttribute($attributeCode);

        return $this->attribute($attribute);
    }

    /**
     * @param string $attributeCode
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function a($attributeCode)
    {
        return $this->attributeByCode($attributeCode);
    }
}
