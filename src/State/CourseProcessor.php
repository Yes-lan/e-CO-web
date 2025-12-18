<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processor for Course creation and updates
 * Automatically sets user, createAt, and updateAt
 */
final class CourseProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    /**
     * Process Course creation or update
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Course) {
            return $data;
        }

        $user = $this->security->getUser();
        
        // For new courses, set user and createAt
        if (!$data->getId()) {
            $data->setUser($user);
            $data->setCreateAt(new \DateTime());
            $data->setPlacementCompletedAt(new \DateTime());
        }
        
        // Always update updateAt
        $data->setUpdateAt(new \DateTime());

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
