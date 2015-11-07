<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Model\FamilyInterface;

class FamilyConfigurationHandler
{
    /** @var FamilyInterface[] */
    protected $families;

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
     * @param $code
     * @return FamilyInterface
     */
    public function getFamily($code)
    {
        if (empty($this->families[$code])) {
            throw new \UnexpectedValueException("No family with code : {$code}");
        }
        return $this->families[$code];
    }

    public static function getInstance()
    {
        return self::$instance;
    }
}