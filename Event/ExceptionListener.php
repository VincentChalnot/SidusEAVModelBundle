<?php

namespace Sidus\EAVModelBundle\Event;

use InvalidArgumentException;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles missing family exceptions and clear the database from the corrupted values
 * Deactivated for the moment as it can be dangerous
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ExceptionListener implements EventSubscriberInterface
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var DataRepository */
    protected $dataRepository;

    /** @var Session */
    protected $session;

    /**
     * ExceptionListener constructor.
     *
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param DataRepository             $dataRepository
     * @param SessionInterface           $session
     */
    public function __construct(
        FamilyConfigurationHandler $familyConfigurationHandler,
        DataRepository $dataRepository,
        SessionInterface $session
    ) {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->dataRepository = $dataRepository;
        $this->session = $session;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @throws \Exception
     */
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
        $familyCodes = $this->familyConfigurationHandler->getFamilyCodes();

        $qb = $this->dataRepository->createQueryBuilder('d');
        $qb->delete()
            ->where('d.family NOT IN (:families)')
            ->setParameter('families', $familyCodes);
        $qb->getQuery()->execute();

        $this->session->getFlashBag()->add('error', 'sidus.exception.missing_family');

        $response = new RedirectResponse($event->getRequest()->getUri());
        $event->setResponse($response); // this will stop event propagation
    }
}
