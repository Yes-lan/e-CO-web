<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BeaconRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provider for /api/beacons endpoint
 * Returns only beacons belonging to the current authenticated user
 */
final class UserBeaconProvider implements ProviderInterface
{
    public function __construct(
        private BeaconRepository $beaconRepository,
        private Security $security
    ) {}

    /**
     * Provide beacons filtered by current user
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return [];
        }

        // Return only beacons where user = current user
        return $this->beaconRepository->findBy(['user' => $user]);
    }
}
