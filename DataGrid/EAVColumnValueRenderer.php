<?php

namespace Sidus\EAVModelBundle\DataGrid;

use Sidus\DataGridBundle\Model\Column;
use Sidus\DataGridBundle\Renderer\ColumnValueRendererInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Overrides base renderer for Sidus/DataGridBundle
 *
 * This code is written for PHP7 because it can only work with the Sidus/DataGridBundle that supports PHP7+ only
 */
class EAVColumnValueRenderer implements ColumnValueRendererInterface
{
    /** @var ColumnValueRendererInterface */
    protected $parent;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $choiceTypes;

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
