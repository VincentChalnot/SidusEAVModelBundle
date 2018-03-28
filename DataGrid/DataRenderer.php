<?php

namespace Sidus\EAVModelBundle\DataGrid;

use Sidus\DataGridBundle\Model\Column;
use Sidus\DataGridBundle\Renderer\RenderableInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Overrides base renderer for Sidus/DataGridBundle
 *
 * This code is written for PHP7 because it can only work with the Sidus/DataGridBundle that supports PHP7+ only
 */
class DataRenderer implements RenderableInterface
{
    /** @var RenderableInterface */
    protected $parent;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $choiceTypes;

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
        if ($object instanceof DataInterface && $column instanceof Column) {
            try {
                $attribute = $object->getFamily()->getAttribute($column->getPropertyPath());
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
