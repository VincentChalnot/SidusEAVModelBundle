<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class BaseContext implements ContextInterface
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Value
     * @ORM\OneToOne(targetEntity="Sidus\EAVModelBundle\Entity\Value", inversedBy="context", fetch="EAGER")
     * @ORM\JoinColumn(name="value_id", referencedColumnName="id", onDelete="cascade", nullable=false)
     */
    protected $value;

    /**
     * Context constructor.
     * @param array $context
     */
    public function __construct(array $context)
    {
        foreach ($context as $key => $value) {
            if (!in_array($key, $this->getAllowedKeys())) {
                throw new \UnexpectedValueException("Trying to set an non-authorized key {$key}");
            }
            $this->$key = $value;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!in_array($key, $this->getAllowedKeys())) {
            throw new \UnexpectedValueException("Trying to get an non-authorized key {$key}");
        }
        return $this->$key;
    }
}
