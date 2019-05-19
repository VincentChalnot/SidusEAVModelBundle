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

use Sidus\DataGridBundle\Renderer\RenderableInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Overrides base renderer for Sidus/DataGridBundle v1.3
 *
 * This code is written for PHP7 because it can only work with the Sidus/DataGridBundle that supports PHP7+ only
 *
 * @deprecated Please migrate to the sidus/datagrid-bundle v2.0
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @property RenderableInterface parent
 */
class DataRenderer extends AbstractColumnValueRenderer implements RenderableInterface
{
    /**
     * @param RenderableInterface $parent
     * @param TranslatorInterface $translator
     * @param array               $choiceTypes
     */
    public function __construct(RenderableInterface $parent, TranslatorInterface $translator, array $choiceTypes)
    {
        $this->parent = $parent;
        $this->translator = $translator;
        $this->choiceTypes = $choiceTypes;
    }
}
