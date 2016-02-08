<?php

namespace Sidus\EAVModelBundle\Model;

interface AttributeTypeInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getDatabaseType();

    /**
     * @return string
     */
    public function getFormType();

    /**
     * @return bool
     */
    public function isEmbedded();
}
