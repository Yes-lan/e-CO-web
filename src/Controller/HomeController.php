<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Beacon;
use App\Entity\BoundariesCourse;
use App\Repository\CourseRepository;
use App\Repository\SessionRepository;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseRepository $courseRepository,
        private SessionRepository $sessionRepository,
        private LanguageRepository $languageRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Redirect to login if not authenticated
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        // Redirect to parcours list (no homepage)
        return $this->redirectToRoute('app_parcours_list');
    }

    #[Route('/map', name: 'app_map')]
    public function map(Request $request): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Get URL parameters
        $sessionId = $request->query->get('sessionId');
        $courseId = $request->query->get('courseId');

        // Verify ownership if accessing specific session or course
        if ($sessionId) {
            $session = $this->sessionRepository->find($sessionId);
            if (!$session) {
                throw $this->createNotFoundException('Session not found');
            }
            
            $course = $session->getCourse();
            if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
                throw $this->createAccessDeniedException('You do not have access to this session');
            }
        }

        if ($courseId) {
            $course = $this->courseRepository->find($courseId);
            if (!$course) {
                throw $this->createNotFoundException('Course not found');
            }
            
            if (!$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
                throw $this->createAccessDeniedException('You do not have access to this course');
            }
        }

        return $this->render('map/view.html.twig');
    }

    #[Route('/api/languages', name: 'api_languages', methods: ['GET'])]
    public function getLanguages(): JsonResponse
    {
        $languages = $this->languageRepository->findAll();
        
        // Map language codes to flag-icons CSS country codes
        $flagMap = [
            'fr' => 'fr', // France
            'en' => 'gb', // Great Britain
            'eu' => 'eu'  // European Union
        ];
        
        $languagesData = array_map(function($language) use ($flagMap) {
            $code = $language->getCode();
            return [
                'id' => $language->getId(),
                'code' => $code,
                'displayedText' => $language->getDisplayedText(),
                'flagIcon' => $flagMap[$code] ?? '🌐'
            ];
        }, $languages);

        $response = new JsonResponse(['languages' => $languagesData]);
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        return $response;
    }

    // DEPRECATED: This legacy endpoint has been replaced by CourseController::apiListCourses
    // which properly returns Session entities. This route is kept for backwards compatibility
    // but should not be used. Use /api/sessions or CourseController's /api/courses instead.
    #[Route('/api/courses/legacy', name: 'api_courses_legacy', methods: ['GET'])]
    public function listCourses(): JsonResponse
    {
        $courses = $this->courseRepository->findAll();
        
        $coursesData = [];
        foreach ($courses as $course) {
            $waypoints = [];
            foreach ($course->getBeacons() as $beacon) {
                $waypoints[] = [
                    'id' => $beacon->getId(),
                    'name' => $beacon->getName(),
                    'latitude' => (float)$beacon->getLatitude(),
                    'longitude' => (float)$beacon->getLongitude(),
                    'type' => $beacon->getType(),
                    'description' => '',
                    'qr' => $beacon->getQr()
                ];
            }
            
            $boundaryPoints = [];
            foreach ($course->getBoundariesCourses() as $boundary) {
                $boundaryPoints[] = [
                    'lat' => (float)$boundary->getLatitude(),
                    'lng' => (float)$boundary->getLongitude()
                ];
            }
            
            $coursesData[] = [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'status' => $course->getStatus(),
                'created_at' => $course->getCreateAt()?->format('Y-m-d H:i:s'),
                'waypoints' => $waypoints,
                'boundary_points' => $boundaryPoints
            ];
        }
        
        return new JsonResponse(['courses' => $coursesData]);
    }

    #[Route('/api/course/save', name: 'api_course_save', methods: ['POST'])]
    public function saveCourse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Create course entity
        $course = new Course();
        $course->setName($data['name']);
        $course->setDescription($data['description'] ?? '');
        $course->setStatus($data['status'] ?? 'draft');
        $course->setCreateAt(new \DateTime());
        $course->setUpdateAt(new \DateTime());
        $course->setPlacementCompletedAt(new \DateTime());

        $this->entityManager->persist($course);

        // Create boundary points
        if (isset($data['boundary_points']) && is_array($data['boundary_points'])) {
            foreach ($data['boundary_points'] as $point) {
                $boundary = new BoundariesCourse();
                $boundary->setLatitude((string)$point['lat']);
                $boundary->setLongitude((string)$point['lng']);
                $boundary->addIdCourse($course);
                
                $this->entityManager->persist($boundary);
            }
        }

        // Create start point beacon
        if (isset($data['startPoint']) && is_array($data['startPoint'])) {
            $startPoint = $data['startPoint'];
            if (isset($startPoint['lat']) && isset($startPoint['lng'])) {
                $beacon = new Beacon();
                $beacon->setName($startPoint['name'] ?? 'Départ');
                $beacon->setLatitude((string)$startPoint['lat']);
                $beacon->setLongitude((string)$startPoint['lng']);
                $beacon->setType('start');
                $beacon->setQr('');
                $beacon->setIsPlaced(false);
                $beacon->setCreatedAt(new \DateTime());
                $beacon->addIdCourse($course);
                
                $this->entityManager->persist($beacon);
            }
        }

        // Create waypoints/beacons
        if (isset($data['waypoints']) && is_array($data['waypoints'])) {
            foreach ($data['waypoints'] as $waypoint) {
                $beacon = new Beacon();
                $beacon->setName($waypoint['name']);
                $beacon->setLatitude((string)$waypoint['latitude']);
                $beacon->setLongitude((string)$waypoint['longitude']);
                $beacon->setType($waypoint['type'] ?? 'control');
                $beacon->setQr($waypoint['qr'] ?? '');
                $beacon->setIsPlaced(false);
                $beacon->setCreatedAt(new \DateTime());
                $beacon->addIdCourse($course);
                
                $this->entityManager->persist($beacon);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $course->getId()]);
    }

    #[Route('/api/course/{id}', name: 'api_course_update', methods: ['PUT'])]
    public function updateCourse(int $id, Request $request): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Update course basic info
        $course->setName($data['name']);
        $course->setDescription($data['description'] ?? '');
        $course->setUpdateAt(new \DateTime());

        // Remove existing boundaries
        foreach ($course->getBoundariesCourses() as $boundary) {
            $boundary->removeIdCourse($course);
            $this->entityManager->remove($boundary);
        }

        // Add new boundaries
        if (isset($data['boundaryPoints']) && is_array($data['boundaryPoints'])) {
            foreach ($data['boundaryPoints'] as $point) {
                $boundary = new BoundariesCourse();
                $boundary->setLatitude((string)$point['lat']);
                $boundary->setLongitude((string)$point['lng']);
                $boundary->addIdCourse($course);
                $this->entityManager->persist($boundary);
            }
        }

        // Remove existing beacons
        foreach ($course->getBeacons() as $beacon) {
            $beacon->removeIdCourse($course);
            $this->entityManager->remove($beacon);
        }

        // Add new beacons
        if (isset($data['waypoints']) && is_array($data['waypoints'])) {
            foreach ($data['waypoints'] as $waypoint) {
                $beacon = new Beacon();
                $beacon->setName($waypoint['name']);
                $beacon->setLatitude((string)$waypoint['latitude']);
                $beacon->setLongitude((string)$waypoint['longitude']);
                $beacon->setType($waypoint['type'] ?? 'control');
                $beacon->setQr($waypoint['qr'] ?? '');
                $beacon->setIsPlaced(false);
                $beacon->setCreatedAt(new \DateTime());
                $beacon->addIdCourse($course);
                $this->entityManager->persist($beacon);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $course->getId()]);
    }

    #[Route('/api/course/{id}', name: 'api_course_delete', methods: ['DELETE'])]
    public function deleteCourse(int $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $this->entityManager->remove($course);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}