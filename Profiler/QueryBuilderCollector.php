<?php declare(strict_types=1);

namespace Sidus\EAVModelBundle\Profiler;

use Sidus\EAVModelBundle\Doctrine\AttributeQueryBuilder;
use Sidus\EAVModelBundle\Doctrine\AttributeQueryBuilderInterface;
use Sidus\EAVModelBundle\Doctrine\EAVQueryBuilder;
use Sidus\EAVModelBundle\Doctrine\EAVQueryBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @method reset()
 */
class QueryBuilderCollector extends DataCollector
{
    /** @var array|Data */
    protected $data = [
        'joins' => [],
        'query_builders' => [],
    ];

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        foreach (EAVQueryBuilder::getQueryBuilders() as $queryBuilder) {
            $this->data['query_builders'][] = $this->parseQueryBuilder($queryBuilder);
        }
        foreach (AttributeQueryBuilder::getBuiltJoins() as $alias => $builtJoin) {
            $this->data['joins'][$alias] = $this->parseAttributeQueryBuilder($builtJoin);
        }
    }

    /**
     * @return array
     */
    public function getQueryBuilders()
    {
        return $this->data['query_builders'];
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->data['joins'];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sidus_query_builder_collector';
    }

    /**
     * @param EAVQueryBuilderInterface $queryBuilder
     *
     * @return array
     */
    protected function parseQueryBuilder(EAVQueryBuilderInterface $queryBuilder)
    {
        return [
            'object_hash' => spl_object_hash($queryBuilder),
            'alias' => $queryBuilder->getAlias(),
            'dql' => $queryBuilder->getQueryBuilder()->getDQL(),
            'parsed_dql' => $this->parseDQL($queryBuilder->getQueryBuilder()->getDQL()),
            'parent_attribute' => $this->parseAttributeQueryBuilder($queryBuilder->getParentAttributeQueryBuilder()),
        ];
    }

    /**
     * @param AttributeQueryBuilderInterface $attributeQueryBuilder
     *
     * @return array
     */
    protected function parseAttributeQueryBuilder(AttributeQueryBuilderInterface $attributeQueryBuilder = null)
    {
        if (!$attributeQueryBuilder) {
            return null;
        }
        $attribute = $attributeQueryBuilder->getAttribute();

        return [
            'attribute' => "{$attribute->getFamily()->getCode()}.{$attribute->getCode()}",
            'column' => $attributeQueryBuilder->getColumn(),
            'query_builder' => $this->parseQueryBuilder($attributeQueryBuilder->getEavQueryBuilder()),
            'join_applied' => $attributeQueryBuilder->isJoinApplied(),
        ];
    }

    /**
     * @param string $dql
     *
     * @return string
     */
    protected function parseDQL($dql)
    {
        $replace = [];
        foreach (AttributeQueryBuilder::getBuiltJoins() as $alias => $builtJoin) {
            $attribute = $builtJoin->getAttribute();
            $fullAttribute = "{$attribute->getFamily()->getCode()}.{$attribute->getCode()}";
            $replace[$builtJoin->getColumn()] = $fullAttribute;
            $replace[$alias] = $fullAttribute;
        }

        return strtr($dql, $replace);
    }
}
