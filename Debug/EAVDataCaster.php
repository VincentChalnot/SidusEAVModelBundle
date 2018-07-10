<?php

namespace Sidus\EAVModelBundle\Debug;


use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\VarDumper\Cloner\Stub;

class EAVDataCaster
{

    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * DataCaster constructor.
     *
     * @param NormalizerInterface $normalizer
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function castDataInterface(DataInterface $data, array $a, Stub $stub, $isNested, $filter)
    {
        $normalizedData = $this->normalizer->normalize(
            $data,
            'json'
        );

        return $normalizedData;
    }
}
