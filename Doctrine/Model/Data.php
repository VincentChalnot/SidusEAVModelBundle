<?php

namespace Sidus\EAVModelBundle\Doctrine\Model;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractData;

/**
 * WARNING ! This class is just an example of how to declare your own data entity, do not use it !
 *
 *
 * @ORM\Table(name="eav_data", indexes={
 *     @ORM\Index(name="family", columns={"family_code"}),
 *     @ORM\Index(name="integer_identifier", columns={"family_code", "integer_identifier"}),
 *     @ORM\Index(name="string_identifier", columns={"family_code", "string_identifier"}),
 *     @ORM\Index(name="updated_at", columns={"updated_at"}),
 *     @ORM\Index(name="created_at", columns={"created_at"})
 * })
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\DataRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class Data extends AbstractData
{
}
