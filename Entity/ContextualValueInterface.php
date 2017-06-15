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

use Sidus\EAVModelBundle\Exception\ContextException;

/**
 * All values must implements this class that defines how context information is handled
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualValueInterface extends ValueInterface
{
    /**
     * @return array
     */
    public function getContext();

    /**
     * @param string $key
     *
     * @throws ContextException
     *
     * @return mixed
     */
    public function getContextValue($key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws ContextException
     */
    public function setContextValue($key, $value);

    /**
     * Context constructor.
     *
     * @param array $context
     *
     * @throws ContextException
     */
    public function setContext(array $context);

    /**
     * Clean all contextual keys
     */
    public function clearContext();

    /**
     * @return array
     */
    public function getContextKeys();
}
