<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provider for /api/users/me endpoint
 * Returns the currently authenticated user based on JWT token
 */
final class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security
    ) {}

    /**
     * Provide the current authenticated user
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            throw new \RuntimeException('User not authenticated');
        }
        
        return $user;
    }
}
