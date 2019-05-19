<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\DataGrid;

use Sidus\DataGridBundle\Model\Column;
use Sidus\DataGridBundle\Renderer\ColumnRendererInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Overriding base column label rendering for Sidus/DataGridBundle v1.3
 *
 * This code is written for PHP7 because it can only work with the Sidus/DataGridBundle that supports PHP7+ only
 *
 * @deprecated Please migrate to the sidus/datagrid-bundle v2.0
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVColumnRenderer implements ColumnRendererInterface
{
    /** @var ColumnRendererInterface */
    protected $baseRenderer;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ColumnRendererInterface $baseRenderer
     * @param TranslatorInterface     $translator
     */
    public function __construct(ColumnRendererInterface $baseRenderer, TranslatorInterface $translator)
    {
        $this->baseRenderer = $baseRenderer;
        $this->translator = $translator;
    }

    /**
     * @param Column $column
     *
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     *
     * @return string
     */
    public function renderColumnLabel(Column $column): string
    {
        // If already defined in translations, ignore
        $key = "datagrid.{$column->getDataGrid()->getCode()}.{$column->getCode()}"; // Same as base logic
        if ($this->translator instanceof TranslatorBagInterface && $this->translator->getCatalogue()->has($key)) {
            return $this->baseRenderer->renderColumnLabel($column);
        }

        $queryHandler = $column->getDataGrid()->getQueryHandler();
        // EAVFilterBundle might not be installed
        /** @noinspection ClassConstantCanBeUsedInspection */
        if (!is_a($queryHandler, 'Sidus\EAVFilterBundle\Query\Handler\EAVQueryHandlerInterface')) {
            return $this->baseRenderer->renderColumnLabel($column);
        }

        /** @var \Sidus\EAVFilterBundle\Query\Handler\EAVQueryHandlerInterface $queryHandler */
        $family = $queryHandler->getFamily();
        if (!$family->hasAttribute($column->getCode())) {
            return $this->baseRenderer->renderColumnLabel($column);
        }

        return $family->getAttribute($column->getCode())->getLabel();
    }
}
