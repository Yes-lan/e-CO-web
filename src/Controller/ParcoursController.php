<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Beacon;
use App\Entity\BoundariesCourse;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manages Courses (orienteering courses with beacons and boundaries)
 * Note: Despite the class name "ParcoursController", this manages Course entities
 * Routes: /parcours/* for courses (UI displays as "Courses"/"Cours")
 */
class ParcoursController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseRepository $courseRepository
    ) {}

    #[Route('/parcours', name: 'app_parcours_list')]
    public function listParcours(): Response
    {
        return $this->render('courses_orienteering/list.html.twig');
    }

    #[Route('/parcours/create', name: 'app_parcours_create')]
    public function createParcours(): Response
    {
        return $this->render('courses_orienteering/create.html.twig');
    }

    #[Route('/api/parcours', name: 'api_parcours_list', methods: ['GET'])]
    public function apiListParcours(): JsonResponse
    {
        $parcours = $this->courseRepository->findAll();
        
        $parcoursData = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'description' => $p->getDescription(),
                'status' => $p->getStatus(),
                'createdAt' => $p->getCreateAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $p->getUpdateAt()?->format('Y-m-d H:i:s'),
                'waypoints' => array_map(function($beacon) {
                    return [
                        'id' => $beacon->getId(),
                        'name' => $beacon->getName(),
                        'latitude' => $beacon->getLatitude(),
                        'longitude' => $beacon->getLongitude(),
                        'lat' => $beacon->getLatitude(),
                        'lng' => $beacon->getLongitude(),
                        'type' => $beacon->getType(),
                        'qr' => $beacon->getQr()
                    ];
                }, $p->getBeacons()->toArray()),
                'boundaryPoints' => array_map(function($boundary) {
                    return [
                        'lat' => $boundary->getLatitude(),
                        'lng' => $boundary->getLongitude()
                    ];
                }, $p->getBoundariesCourses()->toArray()),
                'boundary_points' => array_map(function($boundary) {
                    return [
                        'lat' => $boundary->getLatitude(),
                        'lng' => $boundary->getLongitude()
                    ];
                }, $p->getBoundariesCourses()->toArray())
            ];
        }, $parcours);

        return new JsonResponse(['courses' => $parcoursData]);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_get', methods: ['GET'])]
    public function getParcours(int $id): JsonResponse
    {
        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }
        
        $parcoursData = [
            'id' => $parcours->getId(),
            'name' => $parcours->getName(),
            'description' => $parcours->getDescription(),
            'status' => $parcours->getStatus(),
            'createdAt' => $parcours->getCreateAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $parcours->getUpdateAt()?->format('Y-m-d H:i:s'),
            'waypoints' => array_map(function($beacon) {
                return [
                    'id' => $beacon->getId(),
                    'name' => $beacon->getName(),
                    'latitude' => $beacon->getLatitude(),
                    'longitude' => $beacon->getLongitude(),
                    'lat' => $beacon->getLatitude(),
                    'lng' => $beacon->getLongitude(),
                    'type' => $beacon->getType(),
                    'qr' => $beacon->getQr()
                ];
            }, $parcours->getBeacons()->toArray()),
            'boundaryPoints' => array_map(function($boundary) {
                return [
                    'lat' => $boundary->getLatitude(),
                    'lng' => $boundary->getLongitude()
                ];
            }, $parcours->getBoundariesCourses()->toArray()),
            'boundary_points' => array_map(function($boundary) {
                return [
                    'lat' => $boundary->getLatitude(),
                    'lng' => $boundary->getLongitude()
                ];
            }, $parcours->getBoundariesCourses()->toArray())
        ];
        
        return new JsonResponse(['parcours' => $parcoursData]);
    }

    #[Route('/api/parcours/save', name: 'api_parcours_save', methods: ['POST'])]
    public function saveParcours(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $parcours = new Course();
        $parcours->setName($data['name']);
        $parcours->setDescription($data['description'] ?? '');
        $parcours->setStatus($data['status'] ?? 'draft');
        $parcours->setCreateAt(new \DateTime());
        $parcours->setUpdateAt(new \DateTime());
        $parcours->setPlacementCompletedAt(new \DateTime());

        // Save boundaries
        if (!empty($data['boundaryPoints'])) {
            foreach ($data['boundaryPoints'] as $point) {
                $boundary = new BoundariesCourse();
                $boundary->setLatitude($point['lat']);
                $boundary->setLongitude($point['lng']);
                $this->entityManager->persist($boundary);
                $parcours->addBoundariesCourse($boundary);
            }
        }

        // Save waypoints/beacons
        if (!empty($data['waypoints'])) {
            foreach ($data['waypoints'] as $waypoint) {
                // Skip waypoints with null or invalid coordinates
                if (empty($waypoint['latitude']) || empty($waypoint['longitude']) || 
                    $waypoint['latitude'] === null || $waypoint['longitude'] === null) {
                    continue;
                }
                
                $beacon = new Beacon();
                $beacon->setName($waypoint['name']);
                $beacon->setLatitude((string)$waypoint['latitude']);
                $beacon->setLongitude((string)$waypoint['longitude']);
                $beacon->setType($waypoint['type'] ?? 'control');
                $beacon->setIsPlaced(false);
                $beacon->setQr($waypoint['qr'] ?? '');
                $beacon->setCreatedAt(new \DateTime());
                $beacon->setPlacedAt(null);
                
                $this->entityManager->persist($beacon);
                $parcours->addBeacon($beacon);
            }
        }

        $this->entityManager->persist($parcours);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $parcours->getId()]);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_update', methods: ['PUT'])]
    public function updateParcours(int $id, Request $request): JsonResponse
    {
        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        // Prevent editing finished courses
        if ($parcours->getStatus() === 'finished') {
            return new JsonResponse(['error' => 'Cannot edit a finished course'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Update basic info
        if (isset($data['name'])) {
            $parcours->setName($data['name']);
        }
        if (isset($data['description'])) {
            $parcours->setDescription($data['description']);
        }
        $parcours->setUpdateAt(new \DateTime());

        // Update boundaries
        if (isset($data['boundaryPoints'])) {
            // Remove old boundaries
            foreach ($parcours->getBoundariesCourses() as $boundary) {
                $this->entityManager->remove($boundary);
            }
            
            // Add new boundaries
            foreach ($data['boundaryPoints'] as $point) {
                $boundary = new BoundariesCourse();
                $boundary->setLatitude((string)$point['lat']);
                $boundary->setLongitude((string)$point['lng']);
                $this->entityManager->persist($boundary);
                $parcours->addBoundariesCourse($boundary);
            }
        }

        // Update waypoints/beacons
        if (isset($data['waypoints'])) {
            // Remove old beacons
            foreach ($parcours->getBeacons() as $beacon) {
                $this->entityManager->remove($beacon);
            }
            
            // Add new beacons
            foreach ($data['waypoints'] as $waypoint) {
                // Skip waypoints with null or empty coordinates
                if (empty($waypoint['latitude']) || empty($waypoint['longitude']) || 
                    $waypoint['latitude'] === null || $waypoint['longitude'] === null) {
                    continue;
                }
                
                $beacon = new Beacon();
                $beacon->setName($waypoint['name'] ?? '');
                $beacon->setLatitude((string)$waypoint['latitude']);
                $beacon->setLongitude((string)$waypoint['longitude']);
                $beacon->setType($waypoint['type'] ?? 'control');
                $beacon->setIsPlaced(false);
                $beacon->setQr($waypoint['qr'] ?? '');
                $beacon->setCreatedAt(new \DateTime());
                $beacon->setPlacedAt(null);
                
                $this->entityManager->persist($beacon);
                $parcours->addBeacon($beacon);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $parcours->getId()]);
    }

    #[Route('/api/parcours/{id}/finish', name: 'api_parcours_finish', methods: ['POST'])]
    public function finishParcours(int $id): JsonResponse
    {
        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        $parcours->setStatus('finished');
        $parcours->setUpdateAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'status' => 'finished']);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_delete', methods: ['DELETE'])]
    public function deleteParcours(int $id): JsonResponse
    {
        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        $this->entityManager->remove($parcours);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
