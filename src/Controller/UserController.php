<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * Get current authenticated user information
     */
    #[Route('/api/users/me', name: 'api_users_me', methods: ['GET'], priority: 10)]
    public function getCurrentUser(): JsonResponse
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        return new JsonResponse([
            'id' => $currentUser->getId(),
            'email' => $currentUser->getEmail(),
            'FirstName' => $currentUser->getFirstName(),
            'LastName' => $currentUser->getLastName(),
            'roles' => $currentUser->getRoles(),
        ]);
    }
}
