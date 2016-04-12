<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Container for families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyConfigurationHandler
{
    /** @var FamilyInterface[] */
    protected $families;

    /** @var bool */
    protected $isInitialized = false;

    /**
     * @param FamilyInterface $family
     */
    public function addFamily(FamilyInterface $family)
    {
        $this->isInitialized = false;
        $this->families[$family->getCode()] = $family;
    }

    /**
     * @return FamilyInterface[]
     */
    public function getFamilies()
    {
        $this->initialize();

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
     * @return FamilyInterface
     * @throws MissingFamilyException
     */
    public function getFamily($code)
    {
        if (empty($this->families[$code])) {
            throw new MissingFamilyException($code);
        }
        $this->initialize();

        return $this->families[$code];
    }

    /**
     * @param string $code
     * @return bool
     */
    public function hasFamily($code)
    {
        return !empty($this->families[$code]);
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
     * Build family tree
     */
    protected function initialize()
    {
        if ($this->isInitialized) {
            return;
        }
        $this->isInitialized = true;
        foreach ($this->families as $family) {
            foreach ($this->families as $subFamily) {
                if ($subFamily->getParent() && $subFamily->getParent()->getCode() === $family->getCode()) {
                    $family->addChild($subFamily);
                }
            }
        }
    }
}
