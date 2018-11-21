<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Context;

use Psr\Log\LoggerInterface;
use Sidus\BaseBundle\Utilities\DebugInfoUtility;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\VarDumper\Caster\Caster;

/**
 * Manager for setting, saving and getting the current context when using ContextualData & ContextualValue
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextManager implements ContextManagerInterface
{
    const SESSION_KEY = 'sidus_data_context';

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var LoggerInterface */
    protected $logger;

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
     * @param LoggerInterface      $logger
     * @param string               $contextSelectorType
     * @param array                $defaultContext
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        LoggerInterface $logger,
        $contextSelectorType,
        array $defaultContext
    ) {
        $this->formFactory = $formFactory;
        $this->logger = $logger;
        $this->contextSelectorType = $contextSelectorType;
        $this->defaultContext = $defaultContext;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        $session = $this->getSession();
        try {
            // If context exists in the session, use this information
            if ($session && $session->has(static::SESSION_KEY)) {
                return $session->get(static::SESSION_KEY);
            }
        } catch (\Exception $e) {
            $this->logger->error("Unable to save context to session: {$e->getMessage()}");
        }

        // If context was saved in the service, this means we are probably in command-line or no session was started
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
        $context = array_merge($this->getDefaultContext(), $context);

        // Always save to property in service for fallback
        $this->context = $context;

        // Try to save the context in session,
        $session = $this->getSession();
        if ($session) {
            $session->set(static::SESSION_KEY, $context);
            $session->save();
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
     * @throws InvalidOptionsException
     *
     * @return FormInterface
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
                static::SESSION_KEY,
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
     * Destroys the request because we won't need it anymore
     */
    public function __sleep()
    {
        $this->request = null;
        $this->formFactory = null;
        $this->contextSelectorForm = null;
    }

    /**
     * Custom debugInfo to prevent profiler from crashing
     *
     * @return array
     */
    public function __debugInfo()
    {
        return DebugInfoUtility::debugInfo(
            $this,
            [
                Caster::PREFIX_PROTECTED.'formFactory',
                Caster::PREFIX_PROTECTED.'request',
                Caster::PREFIX_PROTECTED.'contextSelectorForm',
            ]
        );
    }

    /**
     * @return null|SessionInterface
     */
    protected function getSession()
    {
        if ($this->request) {
            try {
                return $this->request->getSession();
            } catch (\Exception $e) {
                $this->logger->error("Unable to access session: {$e->getMessage()}");

                return null;
            }
        }

        return null;
    }
}
