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

use Sidus\DataGridBundle\Renderer\ColumnValueRendererInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Overrides base renderer for Sidus/DataGridBundle v2.0
 *
 * This code is written for PHP7 because it can only work with the Sidus/DataGridBundle that supports PHP7+ only
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @property ColumnValueRendererInterface parent
 */
class EAVColumnValueRenderer extends AbstractColumnValueRenderer implements ColumnValueRendererInterface
{
    /**
     * @param ColumnValueRendererInterface $parent
     * @param TranslatorInterface          $translator
     * @param array                        $choiceTypes
     */
    public function __construct(
        ColumnValueRendererInterface $parent,
        TranslatorInterface $translator,
        array $choiceTypes
    ) {
        $this->parent = $parent;
        $this->translator = $translator;
        $this->choiceTypes = $choiceTypes;
    }
}
