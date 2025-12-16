<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CourseRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provider for /api/parcours endpoint
 * Returns only courses belonging to the current authenticated user
 */
final class UserCoursesProvider implements ProviderInterface
{
    public function __construct(
        private CourseRepository $courseRepository,
        private Security $security
    ) {}

    /**
     * Provide courses filtered by current user
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return [];
        }
        
        // Return only courses where user = current user
        return $this->courseRepository->findBy(['user' => $user]);
    }
}
