<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

class FamilyConfigurationHandler
{
    /** @var FamilyInterface[] */
    protected $families;

    /** @var FamilyConfigurationHandler */
    protected static $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

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
     * @return FamilyInterface
     * @throws MissingFamilyException
     */
    public function getFamily($code)
    {
        if (empty($this->families[$code])) {
            throw new MissingFamilyException($code);
        }
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
     * @return FamilyConfigurationHandler
     */
    public static function getInstance()
    {
        return self::$instance;
    }
}