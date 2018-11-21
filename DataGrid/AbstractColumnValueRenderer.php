<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\DataGrid;

use Sidus\DataGridBundle\Renderer\ColumnValueRendererInterface;
use Sidus\DataGridBundle\Renderer\RenderableInterface;
use Sidus\DataGridBundle\Model\Column;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This abstraction layer is only used for backward compatibility between v1.3 and v2.0 of the Sidus/DataGridBundle
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractColumnValueRenderer
{
    /** @var ColumnValueRendererInterface|RenderableInterface */
    protected $parent;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $choiceTypes;

    /**
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     *
     * @return string
     */
    public function renderValue($value, array $options = []): string
    {
        $object = isset($options['object']) ? $options['object'] : null;
        $column = isset($options['column']) ? $options['column'] : null;
        $attributeCode = isset($options['attribute']) ? $options['attribute'] : null;
        if ($column instanceof Column && null === $attributeCode) {
            $attributeCode = $column->getPropertyPath();
        }

        if ($object instanceof DataInterface && $attributeCode) {
            try {
                $attribute = $object->getFamily()->getAttribute($attributeCode);
            } catch (\Exception $e) {
                // do nothing, it's okay
                return $this->parent->renderValue($value, $options);
            }
            $formOptions = $attribute->getFormOptions();
            if (!isset($formOptions['choices'])
                || !\in_array($attribute->getType()->getCode(), $this->choiceTypes, true)
            ) {
                return $this->parent->renderValue($value, $options);
            }
            $key = array_search($value, $formOptions['choices'], true);
            if (false !== $key) {
                $translationDomain = null;
                if (array_key_exists('choice_translation_domain', $formOptions)) {
                    $translationDomain = $formOptions['choice_translation_domain'];
                }
                if (false === $translationDomain) {
                    return $key;
                }

                return $this->translator->trans($key, [], $translationDomain);
            }
        }

        return $this->parent->renderValue($value, $options);
    }
}
