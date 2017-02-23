<?php

namespace Sidus\EAVModelBundle\Utilities;

use DateTime;
use UnexpectedValueException;

/**
 * Parse datetime values
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DateTimeUtility
{
    /**
     * Parse a datetime, allowing either DateTime objects (passthrough), Unix timestamps as integers or valid W3C or
     * ISO8601 string
     *
     * @param DateTime|int|string $data
     * @param bool                $allowNull
     *
     * @return DateTime
     * @throws UnexpectedValueException
     */
    public static function parse($data, $allowNull = true)
    {
        if (null === $data) {
            if ($allowNull) {
                return null;
            } else {
                throw new UnexpectedValueException('Expecting DateTime or timestamp, null given');
            }
        }
        if ($data instanceof DateTime) {
            return $data;
        }
        if (is_int($data)) {
            if (0 === $data) {
                throw new UnexpectedValueException('Expecting timestamp, numeric value "0" given');
            }
            $date = new DateTime();
            $date->setTimestamp($data);

            return $date;
        }
        $date = DateTime::createFromFormat(DateTime::W3C, $data);
        if (!$date) {
            $date = DateTime::createFromFormat(DateTime::ISO8601, $data);
        }
        if (!$date) {
            throw new \UnexpectedValueException(
                "Unable to parse DateTime value: '{$data}' expecting DateTime or timestamp"
            );
        }

        return $date;
    }
}
