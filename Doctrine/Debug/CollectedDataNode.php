<?php declare(strict_types=1);

namespace Sidus\EAVModelBundle\Doctrine\Debug;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;

/**
 * Used for debug purposes to collect data hydration times, using a static index for convenience because it's just a
 * debug class
 */
class CollectedDataNode
{
    /** @var array */
    protected static $index = [];

    /** @var int[] */
    protected static $valueIds = [];

    /** @var CollectedDataNode|null */
    protected $parentNode;

    /** @var DataInterface */
    protected $data;

    /** @var CollectedDataNode[] */
    protected $relatedNodes = [];

    /** @var int */
    protected $startTime;

    /** @var int|null */
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
        $this->startTime = function_exists('hrtime') ? hrtime(true) : microtime(true);
        $this->rootNode = $rootNode;
        if ($data->getId()) {
            self::$index[$data->getId()] = $this;
        }
    }

    /**
     * @param ValueInterface $value
     */
    public static function addValueLoadingStatistics(ValueInterface $value)
    {
        self::createOrGetNode($value->getData())->terminate();
        self::$valueIds[$value->getIdentifier()] = null;
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
     * @return int
     */
    public static function getValuesCount()
    {
        return count(self::$valueIds);
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
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return int|null
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
            $this->endTime = function_exists('hrtime') ? hrtime(true) : microtime(true);
        }
    }
}
