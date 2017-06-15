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

namespace Sidus\EAVModelBundle\Entity;

/**
 * Generic context you can use in your applications
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait GenericContextTrait
{
    /**
     * ISO 3166-1 alpha-2 country code
     *
     * @var string
     * @\Doctrine\ORM\Mapping\Column(type="string", length=2, nullable=true)
     */
    protected $country;

    /**
     * ISO ISO 639-2 language code
     *
     * @var string
     * @\Doctrine\ORM\Mapping\Column(type="string", length=3, nullable=true)
     */
    protected $language;

    /**
     * Version number
     *
     * @var int
     * @\Doctrine\ORM\Mapping\Column(type="integer", nullable=true)
     */
    protected $version;

    /**
     * @var bool
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=true)
     */
    protected $head;

    /**
     * @var string
     * @\Doctrine\ORM\Mapping\Column(type="string", length=64, nullable=true)
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
