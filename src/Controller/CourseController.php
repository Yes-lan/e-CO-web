<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manages Sessions (running sessions with runners)
 * Note: Despite the class name "CourseController", this manages Session entities
 * Routes: /courses/* for sessions (UI displays as "Sessions")
 */
class CourseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SessionRepository $sessionRepository,
        private CourseRepository $courseRepository
    ) {}

    #[Route('/courses', name: 'app_courses_list')]
    public function listCourses(): Response
    {
        return $this->render('sessions/list.html.twig');
    }

    #[Route('/courses/create', name: 'app_courses_create')]
    public function createCourse(): Response
    {
        return $this->render('sessions/create.html.twig');
    }

    #[Route('/api/sessions', name: 'api_sessions_list', methods: ['GET'])]
    public function apiListCourses(): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get all sessions
        $allSessions = $this->sessionRepository->findAll();
        error_log("📋 Total sessions in DB: " . count($allSessions));
        
        // Filter sessions to only include those connected to courses created by the current user
        $sessions = array_filter($allSessions, function($session) use ($currentUser) {
            $course = $session->getCourse();
            $hasValidCourse = $course && $course->getUser() && $course->getUser()->getId() === $currentUser->getId();
            
            if (!$hasValidCourse) {
                error_log("❌ Session #{$session->getId()} rejected - Course: " . ($course ? $course->getId() : 'null') . 
                    ", User: " . ($course && $course->getUser() ? $course->getUser()->getId() : 'null'));
            }
            
            return $hasValidCourse;
        });
        
        error_log("✅ Filtered sessions for user #{$currentUser->getId()}: " . count($sessions));
        
        // Re-index array to avoid JSON object instead of array
        $sessions = array_values($sessions);
        
        $coursesData = array_map(function($session) {
            $parcours = $session->getCourse();
            return [
                'id' => $session->getId(),
                'name' => $session->getSessionName(),
                'nbRunners' => $session->getNbRunner(),
                'startDate' => $session->getSessionStart()?->format('Y-m-d H:i:s'),
                'endDate' => $session->getSessionEnd()?->format('Y-m-d H:i:s'),
                'parcours' => $parcours ? [
                    'id' => $parcours->getId(),
                    'name' => $parcours->getName(),
                    'description' => $parcours->getDescription()
                ] : null,
                'runners' => array_map(function($runner) {
                    return [
                        'id' => $runner->getId(),
                        'name' => $runner->getName(),
                        'departure' => $runner->getDeparture()?->format('Y-m-d H:i:s'),
                        'arrival' => $runner->getArrival()?->format('Y-m-d H:i:s'),
                        'logSessions' => array_map(function($log) {
                            return [
                                'id' => $log->getId(),
                                'type' => $log->getType(),
                                'time' => $log->getTime()?->format('Y-m-d H:i:s'),
                                'latitude' => $log->getLatitude(),
                                'longitude' => $log->getLongitude(),
                                'additionalData' => $log->getAdditionalData()
                            ];
                        }, $runner->getLogSessions()->toArray())
                    ];
                }, $session->getRunners()->toArray())
            ];
        }, $sessions);

        return new JsonResponse(['courses' => $coursesData]);
    }

    #[Route('/api/sessions/active', name: 'api_sessions_active', methods: ['GET'])]
    public function apiListActiveCourses(): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get only active sessions (sessionStart != null AND sessionEnd == null)
        $allActiveSessions = $this->sessionRepository->createQueryBuilder('s')
            ->where('s.sessionStart IS NOT NULL')
            ->andWhere('s.sessionEnd IS NULL')
            ->getQuery()
            ->getResult();
        
        error_log("🟢 Active sessions in DB: " . count($allActiveSessions));
        
        // Filter by user's courses
        $sessions = array_filter($allActiveSessions, function($session) use ($currentUser) {
            $course = $session->getCourse();
            return $course && $course->getUser() && $course->getUser()->getId() === $currentUser->getId();
        });
        
        error_log("✅ Active sessions for user #{$currentUser->getId()}: " . count($sessions));
        
        $sessions = array_values($sessions);
        
        $coursesData = array_map(function($session) {
            $parcours = $session->getCourse();
            return [
                'id' => $session->getId(),
                'name' => $session->getSessionName(),
                'nbRunners' => $session->getNbRunner(),
                'startDate' => $session->getSessionStart()?->format('Y-m-d H:i:s'),
                'endDate' => $session->getSessionEnd()?->format('Y-m-d H:i:s'),
                'parcours' => $parcours ? [
                    'id' => $parcours->getId(),
                    'name' => $parcours->getName(),
                    'description' => $parcours->getDescription()
                ] : null,
                'runners' => array_map(function($runner) {
                    return [
                        'id' => $runner->getId(),
                        'name' => $runner->getName(),
                        'departure' => $runner->getDeparture()?->format('Y-m-d H:i:s'),
                        'arrival' => $runner->getArrival()?->format('Y-m-d H:i:s'),
                    ];
                }, $session->getRunners()->toArray())
            ];
        }, $sessions);

        return new JsonResponse(['courses' => $coursesData]);
    }

    #[Route('/api/sessions/{id}', name: 'api_sessions_get', methods: ['GET'])]
    public function getCourse(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Session not found'], 404);
        }

        // Check if the session's course belongs to the current user
        $course = $session->getCourse();
        if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $parcours = $session->getCourse();
        
        $sessionData = [
            'id' => $session->getId(),
            'name' => $session->getSessionName(),
            'nbRunners' => $session->getNbRunner(),
            'sessionStart' => $session->getSessionStart()?->format('Y-m-d H:i:s'),
            'sessionEnd' => $session->getSessionEnd()?->format('Y-m-d H:i:s'),
            'course' => $parcours ? [
                'id' => $parcours->getId(),
                'name' => $parcours->getName(),
                'description' => $parcours->getDescription()
            ] : null,
            'runners' => array_map(function($runner) {
                return [
                    'id' => $runner->getId(),
                    'name' => $runner->getName(),
                    'departure' => $runner->getDeparture()?->format('Y-m-d H:i:s'),
                    'arrival' => $runner->getArrival()?->format('Y-m-d H:i:s'),
                    'logSessions' => array_map(function($log) {
                        return [
                            'id' => $log->getId(),
                            'type' => $log->getType(),
                            'time' => $log->getTime()?->format('Y-m-d H:i:s'),
                            'latitude' => $log->getLatitude(),
                            'longitude' => $log->getLongitude(),
                            'additionalData' => $log->getAdditionalData()
                        ];
                    }, $runner->getLogSessions()->toArray())
                ];
            }, $session->getRunners()->toArray())
        ];

        return new JsonResponse($sessionData);
    }

    #[Route('/api/sessions/save', name: 'api_sessions_save', methods: ['POST'])]
    public function saveCourse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['sessionName'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $session = new Session();
        $session->setSessionName($data['sessionName']);
        $session->setNbRunner($data['nbRunners'] ?? 0);
        
        // Auto-start session if requested (default: true)
        $autoStart = $data['autoStart'] ?? true;
        if ($autoStart) {
            // Utiliser le timezone Europe/Paris
            $session->setSessionStart(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        }

        // Link to parcours if provided
        if (!empty($data['parcoursId'])) {
            $parcours = $this->courseRepository->find($data['parcoursId']);
            if ($parcours) {
                // Verify the course belongs to the current user
                $currentUser = $this->getUser();
                if (!$currentUser || !$parcours->getUser() || $parcours->getUser()->getId() !== $currentUser->getId()) {
                    return new JsonResponse(['error' => 'Cannot create session with a course you do not own'], 403);
                }
                $session->setCourse($parcours);
            }
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $session->getId()]);
    }

    #[Route('/api/sessions/{id}/end', name: 'api_sessions_end', methods: ['PATCH'])]
    public function endSession(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Session not found'], 404);
        }

        // Vérifier que la session appartient à l'utilisateur
        $course = $session->getCourse();
        if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        // Terminer la session avec le timezone Europe/Paris
        $session->setSessionEnd(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/sessions/{id}', name: 'api_sessions_update', methods: ['PUT'])]
    public function updateCourse(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Check ownership via the session's course
        $course = $session->getCourse();
        if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        if (isset($data['sessionName'])) {
            $session->setSessionName($data['sessionName']);
        }
        if (isset($data['nbRunners'])) {
            $session->setNbRunner($data['nbRunners']);
        }

        // Update parcours link if provided
        if (isset($data['parcoursId'])) {
            if ($data['parcoursId']) {
                $parcours = $this->courseRepository->find($data['parcoursId']);
                if ($parcours) {
                    $session->setCourse($parcours);
                }
            } else {
                $session->setCourse(null);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $session->getId()]);
    }

    #[Route('/api/sessions/{id}', name: 'api_sessions_delete', methods: ['DELETE'])]
    public function deleteCourse(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Check ownership via the session's course
        $course = $session->getCourse();
        if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $this->entityManager->remove($session);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
