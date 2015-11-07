<?php

namespace Sidus\EAVModelBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sidus\EAVModelBundle\Entity\Data;

class DataManager
{
    /** @var Registry */
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    public function save(Data $entity, $flush = true)
    {
        $em = $this->doctrine->getManager();
        $em->persist($entity);

        if ($flush) {
            $em->flush($entity);
            foreach ($entity->getValues() as $value) {
                $em->flush($value);
            }
        }
    }
}