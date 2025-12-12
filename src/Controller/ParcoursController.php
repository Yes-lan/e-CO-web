<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Beacon;
use App\Entity\User;
use App\Form\CourseType;
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
    public function listParcours(CourseRepository $parcoursRepository): Response
    {
        return $this->render('courses_orienteering/list.html.twig', [
            // TODO: restreindre parcours utilisateur
            'courses' => $parcoursRepository->findAll(),
        ]);
    }

    #[Route('/course/{id}/view', name: 'app_parcours_view')]
    public function viewParcours(Course $course, User $user): Response
    {
        return $this->render('courses_orienteering/view.html.twig', [
            'course' => $course,
            'user' => $user,
        ]);
    }

    #[Route('/course/new', name: 'app_parcours_create')]
    public function createParcours(): Response
    {

        $course = new Course();

        $courseForm = $this->createForm(CourseType::class, $course);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();

            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('task_success');
        }

        return $this->render('courses_orienteering/create.html.twig');
    }

    #[Route('/course/{id}/tags', name: 'app_parcours_tags')]
    public function waypointsParcours(Course $course): Response
    {
        return $this->render('courses_orienteering/tags.html.twig', [
            'course' => $course,
        ]);
    }

    /*
    #[Route('/api/parcours', name: 'api_parcours_list', methods: ['GET'])]
    public function apiListParcours(): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get all courses
        $parcours = $this->courseRepository->findAll();
        
        // Filter courses to only include those created by the current user
        $parcours = array_filter($parcours, function($course) use ($currentUser) {
            return $course->getUser() && $course->getUser()->getId() === $currentUser->getId();
        });
        
        // Re-index array to avoid JSON object instead of array
        $parcours = array_values($parcours);
        
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
                }, array_filter($p->getBeacons()->toArray(), function($beacon) {
                    return $beacon->getType() === 'control';
                })),
                'startBeacon' => $p->getStartBeacon() ? [
                    'id' => $p->getStartBeacon()->getId(),
                    'name' => $p->getStartBeacon()->getName(),
                    'latitude' => $p->getStartBeacon()->getLatitude(),
                    'longitude' => $p->getStartBeacon()->getLongitude(),
                    'lat' => $p->getStartBeacon()->getLatitude(),
                    'lng' => $p->getStartBeacon()->getLongitude(),
                    'type' => 'start',
                    'qr' => $p->getStartBeacon()->getQr()
                ] : null,
                'finishBeacon' => $p->getFinishBeacon() ? [
                    'id' => $p->getFinishBeacon()->getId(),
                    'name' => $p->getFinishBeacon()->getName(),
                    'latitude' => $p->getFinishBeacon()->getLatitude(),
                    'longitude' => $p->getFinishBeacon()->getLongitude(),
                    'lat' => $p->getFinishBeacon()->getLatitude(),
                    'lng' => $p->getFinishBeacon()->getLongitude(),
                    'type' => 'finish',
                    'qr' => $p->getFinishBeacon()->getQr()
                ] : null,
                'sameStartFinish' => $p->isSameStartFinish()
            ];
        }, $parcours);

        return new JsonResponse(['courses' => $parcoursData]);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_get', methods: ['GET'])]
    public function getParcours(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Check if the course belongs to the current user
        if (!$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
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
            }, array_filter($parcours->getBeacons()->toArray(), function($beacon) {
                return $beacon->getType() === 'control';
            })),
            'startBeacon' => $parcours->getStartBeacon() ? [
                'id' => $parcours->getStartBeacon()->getId(),
                'name' => $parcours->getStartBeacon()->getName(),
                'latitude' => $parcours->getStartBeacon()->getLatitude(),
                'longitude' => $parcours->getStartBeacon()->getLongitude(),
                'lat' => $parcours->getStartBeacon()->getLatitude(),
                'lng' => $parcours->getStartBeacon()->getLongitude(),
                'type' => 'start',
                'qr' => $parcours->getStartBeacon()->getQr()
            ] : null,
            'finishBeacon' => $parcours->getFinishBeacon() ? [
                'id' => $parcours->getFinishBeacon()->getId(),
                'name' => $parcours->getFinishBeacon()->getName(),
                'latitude' => $parcours->getFinishBeacon()->getLatitude(),
                'longitude' => $parcours->getFinishBeacon()->getLongitude(),
                'lat' => $parcours->getFinishBeacon()->getLatitude(),
                'lng' => $parcours->getFinishBeacon()->getLongitude(),
                'type' => 'finish',
                'qr' => $parcours->getFinishBeacon()->getQr()
            ] : null,
            'sameStartFinish' => $parcours->isSameStartFinish()
        ];
        
        return new JsonResponse(['parcours' => $parcoursData]);
    }

    #[Route('/api/parcours/{id}/waypoints', name: 'api_parcours_waypoints', methods: ['GET'])]
    public function getParcoursWaypoints(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Check if the course belongs to the current user
        if (!$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }
        
        // Collect all waypoints including start and finish beacons
        $waypoints = [];
        
        // Add start beacon
        if ($parcours->getStartBeacon()) {
            $startBeacon = $parcours->getStartBeacon();
            $waypoints[] = [
                'id' => $startBeacon->getId(),
                'name' => $startBeacon->getName(),
                'latitude' => $startBeacon->getLatitude(),
                'longitude' => $startBeacon->getLongitude(),
                'type' => $startBeacon->getType(),
                'isPlaced' => $startBeacon->isPlaced(),
                'placedAt' => $startBeacon->getPlacedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $startBeacon->getCreatedAt()?->format('Y-m-d H:i:s'),
                'qr' => $startBeacon->getQr(),
                'description' => $startBeacon->getDescription()
            ];
        }
        
        // Add control beacons
        foreach ($parcours->getBeacons() as $beacon) {
            if ($beacon->getType() === 'control') {
                $waypoints[] = [
                    'id' => $beacon->getId(),
                    'name' => $beacon->getName(),
                    'latitude' => $beacon->getLatitude(),
                    'longitude' => $beacon->getLongitude(),
                    'type' => $beacon->getType(),
                    'isPlaced' => $beacon->isPlaced(),
                    'placedAt' => $beacon->getPlacedAt()?->format('Y-m-d H:i:s'),
                    'createdAt' => $beacon->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'qr' => $beacon->getQr(),
                    'description' => $beacon->getDescription()
                ];
            }
        }
        
        // Add finish beacon (if different from start)
        if (!$parcours->isSameStartFinish() && $parcours->getFinishBeacon()) {
            $finishBeacon = $parcours->getFinishBeacon();
            $waypoints[] = [
                'id' => $finishBeacon->getId(),
                'name' => $finishBeacon->getName(),
                'latitude' => $finishBeacon->getLatitude(),
                'longitude' => $finishBeacon->getLongitude(),
                'type' => $finishBeacon->getType(),
                'isPlaced' => $finishBeacon->isPlaced(),
                'placedAt' => $finishBeacon->getPlacedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $finishBeacon->getCreatedAt()?->format('Y-m-d H:i:s'),
                'qr' => $finishBeacon->getQr(),
                'description' => $finishBeacon->getDescription()
            ];
        }
        
        return new JsonResponse(['waypoints' => $waypoints]);
    }

    #[Route('/api/parcours/save', name: 'api_parcours_save', methods: ['POST'])]
    public function saveParcours(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = new Course();
        $parcours->setName($data['name']);
        $parcours->setDescription($data['description'] ?? '');
        $parcours->setStatus($data['status'] ?? 'draft');
        $parcours->setCreateAt(new \DateTime());
        $parcours->setUpdateAt(new \DateTime());
        $parcours->setPlacementCompletedAt(new \DateTime());
        $parcours->setUser($currentUser);
        $parcours->setSameStartFinish($data['sameStartFinish'] ?? false);

        $this->entityManager->persist($parcours);
        $this->entityManager->flush(); // Flush first to get the course ID
        
        // Save waypoints/beacons
        $createdBeacons = []; // Keep track of created beacons
        if (!empty($data['waypoints'])) {
            foreach ($data['waypoints'] as $index => $waypoint) {
                $beacon = new Beacon();
                $beacon->setName($waypoint['name'] ?? 'Balise');
                
                // Set coordinates if provided, otherwise leave as 0 (to be filled via QR scan)
                if (isset($waypoint['latitude']) && $waypoint['latitude'] !== null) {
                    $beacon->setLatitude((float)$waypoint['latitude']);
                } else {
                    $beacon->setLatitude(0.0); // Default value since column is NOT NULL
                }
                
                if (isset($waypoint['longitude']) && $waypoint['longitude'] !== null) {
                    $beacon->setLongitude((float)$waypoint['longitude']);
                } else {
                    $beacon->setLongitude(0.0); // Default value since column is NOT NULL
                }
                
                $beacon->setType($waypoint['type'] ?? 'control');
                $beacon->setIsPlaced(false);
                $beacon->setCreatedAt(new \DateTime());
                $beacon->setPlacedAt(null);
                $beacon->setQr(''); // Temporary empty string, will be updated after flush
                
                $this->entityManager->persist($beacon);
                $beacon->addCourse($parcours);
                $createdBeacons[] = $beacon; // Track this beacon
            }
            
            // Flush to get beacon IDs
            $this->entityManager->flush();
            
            // Generate QR codes for all created beacons now that we have their IDs
            foreach ($createdBeacons as $beacon) {
                $qrData = json_encode([
                    'type' => 'WAYPOINT',
                    'courseId' => $parcours->getId(),
                    'courseName' => $parcours->getName(),
                    'waypointId' => $beacon->getId(),
                    'waypointName' => $beacon->getName(),
                    'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
                ]);
                $beacon->setQr($qrData);
            }
            
            // Flush again to save the QR codes
            $this->entityManager->flush();
        }

        // Create start and finish beacons
        $startBeacon = new Beacon();
        $startBeacon->setName('Départ');
        $startBeacon->setLatitude(0.0);
        $startBeacon->setLongitude(0.0);
        $startBeacon->setType('start');
        $startBeacon->setIsPlaced(false);
        $startBeacon->setCreatedAt(new \DateTime());
        $startBeacon->setPlacedAt(null);
        $startBeacon->setQr(''); // Will be updated after flush
        $this->entityManager->persist($startBeacon);
        $startBeacon->addCourse($parcours);

        $finishBeacon = null; // Track finish beacon reference
        if (!$parcours->isSameStartFinish()) {
            // Create separate finish beacon
            $finishBeacon = new Beacon();
            $finishBeacon->setName('Arrivée');
            $finishBeacon->setLatitude(0.0);
            $finishBeacon->setLongitude(0.0);
            $finishBeacon->setType('finish');
            $finishBeacon->setIsPlaced(false);
            $finishBeacon->setCreatedAt(new \DateTime());
            $finishBeacon->setPlacedAt(null);
            $finishBeacon->setQr(''); // Will be updated after flush
            $this->entityManager->persist($finishBeacon);
            $finishBeacon->addCourse($parcours);
        }
        // Note: If sameStartFinish is true, only the start beacon is created
        // The getFinishBeacon() method will return the start beacon automatically

        // Flush to get beacon IDs
        $this->entityManager->flush();

        // Generate QR codes for start/finish beacons
        $qrType = $parcours->isSameStartFinish() ? 'START_FINISH' : 'START';
        $startQrData = json_encode([
            'type' => $qrType,
            'courseId' => $parcours->getId(),
            'courseName' => $parcours->getName(),
            'waypointId' => $startBeacon->getId(),
            'waypointName' => $startBeacon->getName(),
            'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
        ]);
        $startBeacon->setQr($startQrData);

        if (!$parcours->isSameStartFinish() && $finishBeacon !== null) {
            $finishQrData = json_encode([
                'type' => 'FINISH',
                'courseId' => $parcours->getId(),
                'courseName' => $parcours->getName(),
                'waypointId' => $finishBeacon->getId(),
                'waypointName' => $finishBeacon->getName(),
                'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
            ]);
            $finishBeacon->setQr($finishQrData);
        }

        // Final flush to save QR codes
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $parcours->getId()]);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_update', methods: ['PUT'])]
    public function updateParcours(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        // Check ownership
        if (!$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        // Prevent editing ready and finished courses
        if ($parcours->getStatus() === 'finished' || $parcours->getStatus() === 'ready') {
            return new JsonResponse(['error' => 'Cannot edit a ready or finished course'], 403);
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
        if (isset($data['status'])) {
            $parcours->setStatus($data['status']);
        }
        $parcours->setUpdateAt(new \DateTime());
        
        // Update sameStartFinish flag
        if (isset($data['sameStartFinish'])) {
            $parcours->setSameStartFinish($data['sameStartFinish']);
        }

        // Update waypoints/beacons
        if (isset($data['waypoints'])) {
            $existingBeacons = $parcours->getBeacons()->toArray();
            $incomingBeaconIds = [];
            $newBeacons = []; // Track newly created beacons for QR code generation
            
            // Process incoming waypoints
            foreach ($data['waypoints'] as $waypoint) {
                if (isset($waypoint['id']) && $waypoint['id']) {
                    // Existing beacon - update it
                    $incomingBeaconIds[] = $waypoint['id'];
                    
                    $beacon = null;
                    foreach ($existingBeacons as $existingBeacon) {
                        if ($existingBeacon->getId() === $waypoint['id']) {
                            $beacon = $existingBeacon;
                            break;
                        }
                    }
                    
                    if ($beacon) {
                        $beacon->setName($waypoint['name'] ?? $beacon->getName());
                        
                        // Update coordinates if provided
                        if (isset($waypoint['latitude']) && $waypoint['latitude'] !== null) {
                            $beacon->setLatitude((float)$waypoint['latitude']);
                        }
                        if (isset($waypoint['longitude']) && $waypoint['longitude'] !== null) {
                            $beacon->setLongitude((float)$waypoint['longitude']);
                        }
                        
                        if (isset($waypoint['type'])) {
                            $beacon->setType($waypoint['type']);
                        }
                    }
                } else {
                    // New beacon - create it
                    $beacon = new Beacon();
                    $beacon->setName($waypoint['name'] ?? 'Balise');
                    
                    // Set coordinates if provided, otherwise use 0
                    if (isset($waypoint['latitude']) && $waypoint['latitude'] !== null) {
                        $beacon->setLatitude((float)$waypoint['latitude']);
                    } else {
                        $beacon->setLatitude(0.0);
                    }
                    
                    if (isset($waypoint['longitude']) && $waypoint['longitude'] !== null) {
                        $beacon->setLongitude((float)$waypoint['longitude']);
                    } else {
                        $beacon->setLongitude(0.0);
                    }
                    
                    $beacon->setType($waypoint['type'] ?? 'control');
                    $beacon->setIsPlaced(false);
                    $beacon->setQr(''); // Will be updated after flush
                    $beacon->setCreatedAt(new \DateTime());
                    $beacon->setPlacedAt(null);
                    
                    $this->entityManager->persist($beacon);
                    $beacon->addCourse($parcours);
                    $newBeacons[] = $beacon;
                }
            }
            
            // Delete beacons that are no longer in the waypoints list
            foreach ($existingBeacons as $existingBeacon) {
                if (!in_array($existingBeacon->getId(), $incomingBeaconIds)) {
                    $this->entityManager->remove($existingBeacon);
                }
            }
            
            // Flush to save changes and get IDs for new beacons
            $this->entityManager->flush();
            
            // Generate QR codes for new beacons
            if (!empty($newBeacons)) {
                foreach ($newBeacons as $beacon) {
                    $qrData = json_encode([
                        'type' => 'WAYPOINT',
                        'courseId' => $parcours->getId(),
                        'courseName' => $parcours->getName(),
                        'waypointId' => $beacon->getId(),
                        'waypointName' => $beacon->getName(),
                        'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
                    ]);
                    $beacon->setQr($qrData);
                }
                
                // Flush again to save QR codes
                $this->entityManager->flush();
            }
        } else {
            $this->entityManager->flush();
        }

        // Handle start/finish beacon updates
        if (isset($data['sameStartFinish'])) {
            $sameStartFinish = $data['sameStartFinish'];
            $parcours->setSameStartFinish($sameStartFinish);
            
            if ($sameStartFinish) {
                // Same location for start and finish
                if (!$parcours->getStartBeacon()) {
                    // Create new start beacon (will serve as both start and finish)
                    $startBeacon = new Beacon();
                    $startBeacon->setName('Départ/Arrivée');
                    $startBeacon->setLatitude(0.0);
                    $startBeacon->setLongitude(0.0);
                    $startBeacon->setType('start');
                    $startBeacon->setIsPlaced(false);
                    $startBeacon->setCreatedAt(new \DateTime());
                    $startBeacon->setPlacedAt(null);
                    $startBeacon->setQr('');
                    $this->entityManager->persist($startBeacon);
                    $startBeacon->addCourse($parcours);
                    
                    $this->entityManager->flush();
                    
                    // Generate QR code
                    $qrData = json_encode([
                        'type' => 'START_FINISH',
                        'courseId' => $parcours->getId(),
                        'courseName' => $parcours->getName(),
                        'waypointId' => $startBeacon->getId(),
                        'waypointName' => $startBeacon->getName(),
                        'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
                    ]);
                    $startBeacon->setQr($qrData);
                }
                
                // Remove any existing separate finish beacon
                $existingFinishBeacon = null;
                foreach ($parcours->getBeacons() as $beacon) {
                    if ($beacon->getType() === 'finish') {
                        $existingFinishBeacon = $beacon;
                        break;
                    }
                }
                if ($existingFinishBeacon) {
                    $parcours->removeBeacon($existingFinishBeacon);
                    $this->entityManager->remove($existingFinishBeacon);
                }
            } else {
                // Different locations for start and finish
                if (!$parcours->getStartBeacon()) {
                    $startBeacon = new Beacon();
                    $startBeacon->setName('Départ');
                    $startBeacon->setLatitude(0.0);
                    $startBeacon->setLongitude(0.0);
                    $startBeacon->setType('start');
                    $startBeacon->setIsPlaced(false);
                    $startBeacon->setCreatedAt(new \DateTime());
                    $startBeacon->setPlacedAt(null);
                    $startBeacon->setQr('');
                    $this->entityManager->persist($startBeacon);
                    $startBeacon->addCourse($parcours);
                }
                
                if (!$parcours->getFinishBeacon()) {
                    $finishBeacon = new Beacon();
                    $finishBeacon->setName('Arrivée');
                    $finishBeacon->setLatitude(0.0);
                    $finishBeacon->setLongitude(0.0);
                    $finishBeacon->setType('finish');
                    $finishBeacon->setIsPlaced(false);
                    $finishBeacon->setCreatedAt(new \DateTime());
                    $finishBeacon->setPlacedAt(null);
                    $finishBeacon->setQr('');
                    $this->entityManager->persist($finishBeacon);
                    $finishBeacon->addCourse($parcours);
                }
                
                $this->entityManager->flush();
                
                // Generate QR codes if new beacons were created
                if ($parcours->getStartBeacon() && !$parcours->getStartBeacon()->getQr()) {
                    $startQrData = json_encode([
                        'type' => 'START',
                        'courseId' => $parcours->getId(),
                        'courseName' => $parcours->getName(),
                        'waypointId' => $parcours->getStartBeacon()->getId(),
                        'waypointName' => $parcours->getStartBeacon()->getName(),
                        'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
                    ]);
                    $parcours->getStartBeacon()->setQr($startQrData);
                }
                
                if ($parcours->getFinishBeacon() && !$parcours->getFinishBeacon()->getQr()) {
                    $finishQrData = json_encode([
                        'type' => 'FINISH',
                        'courseId' => $parcours->getId(),
                        'courseName' => $parcours->getName(),
                        'waypointId' => $parcours->getFinishBeacon()->getId(),
                        'waypointName' => $parcours->getFinishBeacon()->getName(),
                        'courseCreated' => $parcours->getCreateAt()->format('Y-m-d H:i:s')
                    ]);
                    $parcours->getFinishBeacon()->setQr($finishQrData);
                }
            }
            
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true, 'id' => $parcours->getId()]);
    }

    #[Route('/api/parcours/{id}/finish', name: 'api_parcours_finish', methods: ['POST'])]
    public function finishParcours(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        // Check ownership
        if (!$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $parcours->setStatus('finished');
        $parcours->setUpdateAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'status' => 'finished']);
    }

    #[Route('/api/parcours/{id}', name: 'api_parcours_delete', methods: ['DELETE'])]
    public function deleteParcours(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $parcours = $this->courseRepository->find($id);
        
        if (!$parcours) {
            return new JsonResponse(['error' => 'Parcours not found'], 404);
        }

        // Check ownership
        if (!$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $this->entityManager->remove($parcours);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
        */
}
