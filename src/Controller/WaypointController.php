<?php

namespace App\Controller;

use App\Entity\Beacon;
use App\Repository\BeaconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manages Waypoints (Beacons) operations
 */
class WaypointController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BeaconRepository $beaconRepository
    ) {}

    #[Route('/api/waypoints/{id}/place', name: 'api_waypoint_place', methods: ['PATCH'])]
    public function placeWaypoint(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $beacon = $this->beaconRepository->find($id);
        
        if (!$beacon) {
            return new JsonResponse(['error' => 'Waypoint not found'], 404);
        }

        // Verify that the beacon belongs to a course owned by the current user
        $courses = $beacon->getCourse();
        $hasAccess = false;
        foreach ($courses as $course) {
            if ($course->getUser() && $course->getUser()->getId() === $currentUser->getId()) {
                $hasAccess = true;
                break;
            }
        }
        
        if (!$hasAccess) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        // Get latitude and longitude from request
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(['error' => 'Latitude and longitude are required'], 400);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        // Update beacon position
        $beacon->setLatitude($latitude);
        $beacon->setLongitude($longitude);
        $beacon->setIsPlaced(true);
        $beacon->setPlacedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Waypoint placed successfully',
            'waypoint' => [
                'id' => $beacon->getId(),
                'name' => $beacon->getName(),
                'latitude' => $beacon->getLatitude(),
                'longitude' => $beacon->getLongitude(),
                'type' => $beacon->getType(),
                'isPlaced' => $beacon->isPlaced(),
                'placedAt' => $beacon->getPlacedAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }
}
