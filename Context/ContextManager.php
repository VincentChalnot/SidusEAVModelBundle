<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\Context;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Manager for setting, saving and getting the current context when using ContextualData & ContextualValue
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextManager
{
    const SESSION_KEY = 'sidus_data_context';

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var array */
    protected $defaultContext;

    /** @var Request */
    protected $request;

    /** @var string */
    protected $contextSelectorType;

    /** @var FormInterface */
    protected $contextSelectorForm;

    /** @var array */
    protected $context;

    /**
     * @param FormFactoryInterface $formFactory
     * @param string               $contextSelectorType
     * @param array                $defaultContext
     */
    public function __construct(FormFactoryInterface $formFactory, $contextSelectorType, array $defaultContext)
    {
        $this->formFactory = $formFactory;
        $this->contextSelectorType = $contextSelectorType;
        $this->defaultContext = $defaultContext;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        // If context exists in the session, use this information
        if ($this->getSession() && $this->getSession()->has(self::SESSION_KEY)) {
            return $this->getSession()->get(self::SESSION_KEY);
        }

        // If context was saved in the service, this means we are probably in command-line
        if ($this->context) {
            return $this->context;
        }

        return $this->getDefaultContext();
    }

    /**
     * This method is exposed only for command-line applications, please use the context selector form
     *
     * @param array $context
     *
     * @internal Warning, this method will save the context without any checks on the values
     */
    public function setContext(array $context)
    {
        // Try to save the context in session, fallback to property in service otherwise
        if ($this->getSession()) {
            $this->getSession()->set(self::SESSION_KEY, $context);
            $this->getSession()->save();
        } else {
            $this->context = $context;
        }
    }

    /**
     * @return array
     */
    public function getDefaultContext()
    {
        return $this->defaultContext;
    }

    /**
     * @return FormInterface
     * @throws InvalidOptionsException
     */
    public function getContextSelectorForm()
    {
        if (!$this->contextSelectorForm) {
            if (!$this->contextSelectorType) {
                return null;
            }

            $formOptions = [
                'action' => $this->request->getRequestUri(),
                'attr' => [
                    'novalidate' => 'novalidate',
                    'class' => 'form-inline',
                ],
            ];
            $this->contextSelectorForm = $this->formFactory->createNamed(
                self::SESSION_KEY,
                $this->contextSelectorType,
                $this->getContext(),
                $formOptions
            );
        }

        return $this->contextSelectorForm;
    }

    /**
     * Global hook checking if context form was submitted because the context form can appear on any page
     *
     * @param GetResponseEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->request = $event->getRequest();

        $form = $this->getContextSelectorForm();
        if (!$form) {
            return;
        }

        $form->handleRequest($this->request);
        // Check if form is submitted and redirect to same url in GET
        if ($form->isSubmitted() && $form->isValid()) {
            $this->setContext($form->getData());
            $redirectResponse = new RedirectResponse($event->getRequest()->getRequestUri());
            $event->setResponse($redirectResponse);
        }
    }

    /**
     * @return null|SessionInterface
     */
    protected function getSession()
    {
        if ($this->request) {
            return $this->request->getSession();
        }

        return null;
    }
}
