<?php

namespace Sidus\EAVModelBundle\Event;

use InvalidArgumentException;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof MissingFamilyException) {
            $this->handleMissingFamilyException($event);
        }
    }

    /**
     * Quick and dirty way to handle data with missing family, please feel free to override !
     *
     * @param GetResponseForExceptionEvent $event
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws InvalidArgumentException
     */
    protected function handleMissingFamilyException(GetResponseForExceptionEvent $event)
    {
        $familyCodes = $this->container->get('sidus_eav_model.family_configuration.handler')->getFamilyCodes();

        $qb = $this->container->get('sidus_eav_model.doctrine.repository.data')->createQueryBuilder('d');
        $qb->delete()
            ->where('d.familyCode NOT IN (:familyCodes)')
            ->setParameter('familyCodes', $familyCodes);
        $qb->getQuery()->execute();

        $this->container->get('session')->getFlashBag()->add('error', 'sidus.exception.missing_family');

        $response = new RedirectResponse($event->getRequest()->getUri());
        $event->setResponse($response); // this will stop event propagation
    }
}
