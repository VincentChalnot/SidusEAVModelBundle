<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Context
 *
 * @ORM\Table(name="sidus_context")
 * @ORM\Entity()
 */
class Context implements ContextInterface
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
     * Context constructor.
     * @param array $context
     */
    public function __construct(array $context)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($context as $key => $value) {
            if (in_array($key, ['id', 'value'], true)) {
                throw new \UnexpectedValueException("Cannot set '{$key}' via context configuration");
            }
            $accessor->setValue($this, $key, $value);
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
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return boolean
     */
    public function isHead()
    {
        return $this->head;
    }

    /**
     * @param boolean $head
     */
    public function setHead($head)
    {
        $this->head = $head;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @inheritdoc
     */
    public function getContextValue($key)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return $accessor->getValue($this, $key);
    }
}
