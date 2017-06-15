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

namespace Sidus\EAVModelBundle\Serializer;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Handling depth & max depth context in serialization
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class CircularReferenceHandler
{
    /** @var int */
    protected $circularReferenceLimit = 2;

    /** @var callable */
    protected $circularReferenceHandler;

    /**
     * Set circular reference limit.
     *
     * @param int $circularReferenceLimit limit of iterations for the same object
     *
     * @return self
     */
    public function setCircularReferenceLimit($circularReferenceLimit)
    {
        $this->circularReferenceLimit = $circularReferenceLimit;

        return $this;
    }

    /**
     * Set circular reference handler.
     *
     * @param callable $circularReferenceHandler
     *
     * @return self
     */
    public function setCircularReferenceHandler(callable $circularReferenceHandler)
    {
        $this->circularReferenceHandler = $circularReferenceHandler;

        return $this;
    }

    /**
     * Detects if the configured circular reference limit is reached.
     *
     * @param mixed $object
     * @param array $context
     *
     * @throws CircularReferenceException
     *
     * @return bool
     */
    public function isCircularReference($object, &$context)
    {
        $objectHash = spl_object_hash($object);

        if (isset($context[AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT][$objectHash])) {
            if ($context[AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT][$objectHash] >= $this->circularReferenceLimit) {
                unset($context[AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT][$objectHash]);

                return true;
            }

            ++$context[AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT][$objectHash];
        } else {
            $context[AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT][$objectHash] = 1;
        }

        return false;
    }

    /**
     * Handles a circular reference.
     *
     * If a circular reference handler is set, it will be called. Otherwise, a
     * {@class CircularReferenceException} will be thrown.
     *
     * @param mixed $object
     *
     * @throws CircularReferenceException
     *
     * @return mixed
     */
    public function handleCircularReference($object)
    {
        if ($this->circularReferenceHandler) {
            return call_user_func($this->circularReferenceHandler, $object);
        }

        throw new CircularReferenceException(
            sprintf('A circular reference has been detected (configured limit: %d).', $this->circularReferenceLimit)
        );
    }
}
