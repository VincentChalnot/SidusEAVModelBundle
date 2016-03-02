<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait GenericContext
{
    /**
     * ISO 3166-1 alpha-2 country code
     *
     * @var string
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    protected $country;

    /**
     * ISO ISO 639-2 language code
     *
     * @var string
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    protected $language;

    /**
     * Version number
     *
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $version;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $head;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $channel;

    /**
     * @return array
     */
    public function getAllowedKeys()
    {
        return [
            'country',
            'language',
            'version',
            'head',
            'channel',
        ];
    }
}
