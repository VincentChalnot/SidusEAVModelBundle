<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

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

    public function getRootFamilies()
    {
        $root = [];
        foreach ($this->getFamilies() as $family) {
            if ($family->isInstantiable()) {
                $p = $family->getParent();
                if (($p && !$p->isInstantiable()) || !$p) {
                    $root[$family->getCode()] = $family;
                }
            }
        }
        return $root;
    }
}