<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
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
     * @return array
     */
    public function getFamilyCodes()
    {
        return array_keys($this->families);
    }

    /**
     * @param $code
     * @return FamilyInterface
     */
    public function getFamily($code)
    {
        if (empty($this->families[$code])) {
            throw new MissingFamilyException($code);
        }
        return $this->families[$code];
    }

    public static function getInstance()
    {
        return self::$instance;
    }
}