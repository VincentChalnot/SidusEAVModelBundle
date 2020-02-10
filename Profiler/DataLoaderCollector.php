<?php declare(strict_types=1);

namespace Sidus\EAVModelBundle\Profiler;

use Sidus\EAVModelBundle\Doctrine\Debug\CollectedDataNode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @method reset()
 */
class DataLoaderCollector extends DataCollector
{
    /** @var array|Data */
    protected $data = [
        'nodes' => [],
        'count' => null,
    ];

    /** @var array */
    protected $builtNodeIds = [];

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['count'] = count(CollectedDataNode::getIndex());
        foreach (CollectedDataNode::getIndex() as $dataId => $node) {
            $data = $node->getData();
            $relatedNodes = [];
            foreach ($node->getRelatedNodes() as $relatedNode) {
                $relatedNodes[] = $relatedNode->getData()->getId();
            }
            $relatedNodes = array_unique($relatedNodes);
            $duration = null;
            if ($node->getEndTime()) {
                $duration = $node->getEndTime()->getTimestamp() - $node->getStartTime()->getTimestamp();
            }
            $this->data['nodes'][$dataId] = [
                'familyCode' => $data->getFamilyCode(),
                'id' => $data->getId(),
                'identifier' => $data->getIdentifier(),
                'label' => $data->getLabel(),
                'rootNode' => $node->isRootNode(),
                'startTime' => $node->getStartTime(),
                'endTime' => $node->getEndTime(),
                'duration' => $duration,
                'relatedNodes' => $relatedNodes,
            ];
        }
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->data['count'];
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->data['nodes'];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sidus_data_loader';
    }
}
