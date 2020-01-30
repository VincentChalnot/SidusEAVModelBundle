<?php declare(strict_types=1);

namespace Sidus\EAVModelBundle\Doctrine\Debug;

use Sidus\EAVModelBundle\Entity\DataInterface;

/**
 * Used for debug purposes to collect data hydration times, using a static index for convenience because it's just a
 * debug class
 */
class CollectedDataNode
{
    /** @var array */
    protected static $index = [];

    /** @var CollectedDataNode|null */
    protected $parentNode;

    /** @var DataInterface */
    protected $data;

    /** @var CollectedDataNode[] */
    protected $relatedNodes = [];

    /** @var \DateTimeInterface */
    protected $startTime;

    /** @var \DateTimeInterface|null */
    protected $endTime;

    /** @var bool */
    protected $rootNode;

    /**
     * @param DataInterface $data
     * @param bool          $rootNode
     */
    protected function __construct(DataInterface $data, $rootNode = false)
    {
        $this->data = $data;
        $this->startTime = new \DateTime();
        $this->rootNode = $rootNode;
        if ($data->getId()) {
            self::$index[$data->getId()] = $this;
        }
    }

    /**
     * @param DataInterface $data
     * @param bool          $rootNode
     *
     * @return self
     */
    public static function createOrGetNode(DataInterface $data, $rootNode = false)
    {
        if (array_key_exists($data->getId(), self::$index)) {
            return self::$index[$data->getId()];
        }

        return new CollectedDataNode($data, $rootNode);
    }

    /**
     * @return self[]
     */
    public static function getIndex()
    {
        return self::$index;
    }

    /**
     * @return CollectedDataNode|null
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * @return DataInterface
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return CollectedDataNode[]
     */
    public function getRelatedNodes()
    {
        return $this->relatedNodes;
    }

    /**
     * @param CollectedDataNode $node
     */
    public function addRelatedNode(CollectedDataNode $node)
    {
        $this->relatedNodes[] = $node;
        if (!$node->isRootNode()) { // Prevent obvious circular references
            $node->parentNode = $this;
        }
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @return bool
     */
    public function isRootNode(): bool
    {
        return $this->rootNode;
    }

    /**
     * Terminate stopwatch
     */
    public function terminate(): void
    {
        if (null === $this->endTime) {
            $this->endTime = new \DateTime();
        }
    }
}
