<?php

namespace Sidus\EAVModelBundle\Doctrine\Model;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractValue;

/**
 * WARNING ! This class is just an example of how to declare your own value entity, do not use it !
 *
 *
 * @ORM\Table(name="eav_value", indexes={
 *     @ORM\Index(name="attribute", columns={"attribute_code"}),
 *     @ORM\Index(name="string_search", columns={"attribute_code", "string_value"}),
 *     @ORM\Index(name="int_search", columns={"attribute_code", "integer_value"}),
 *     @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\ValueRepository")
 */
class Value extends AbstractValue
{
}
