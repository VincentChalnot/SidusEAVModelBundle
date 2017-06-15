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

namespace Sidus\EAVModelBundle\Registry;

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Container for families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyRegistry
{
    /** @var FamilyInterface[] */
    protected $families;

    /**
     * @param FamilyInterface $family
     */
    public function addFamily(FamilyInterface $family)
    {
        $this->families[$family->getCode()] = $family;
    }

    /**
     * @return FamilyInterface[]
     */
    public function getFamilies()
    {
        return $this->families;
    }

    /**
     * @return array
     */
    public function getFamilyCodes()
    {
        return array_keys($this->families);
    }

    /**
     * @param string $code
     *
     * @return FamilyInterface
     * @throws MissingFamilyException
     */
    public function getFamily($code)
    {
        if (!$this->hasFamily($code)) {
            throw new MissingFamilyException($code);
        }

        return $this->families[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasFamily($code)
    {
        return array_key_exists($code, $this->families);
    }

    /**
     * Get all instantiable families with no parent
     *
     * @return array
     */
    public function getRootFamilies()
    {
        $root = [];
        foreach ($this->getFamilies() as $family) {
            if ($family->isInstantiable()) {
                $p = $family->getParent();
                if (!$p || ($p && !$p->isInstantiable())) {
                    $root[$family->getCode()] = $family;
                }
            }
        }

        return $root;
    }

    /**
     * @param FamilyInterface $family
     *
     * @return array
     */
    public function getByParent(FamilyInterface $family)
    {
        $families = [];
        foreach ($this->families as $subFamily) {
            if ($subFamily->getParent() && $subFamily->getParent()->getCode() === $family->getCode()) {
                $families[$subFamily->getCode()] = $subFamily;
            }
        }

        return $families;
    }
}
