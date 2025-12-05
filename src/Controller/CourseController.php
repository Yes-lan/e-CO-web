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
        $sessions = $this->sessionRepository->findAll();
        
        $coursesData = array_map(function($session) {
            $parcours = $session->getCourse();
            return [
                'id' => $session->getId(),
                'name' => $session->getSessionName(),
                'nbRunners' => $session->getNbRunner(),
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

    #[Route('/api/sessions/{id}', name: 'api_sessions_get', methods: ['GET'])]
    public function getCourse(int $id): JsonResponse
    {
        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Session not found'], 404);
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

        // Link to parcours if provided
        if (!empty($data['parcoursId'])) {
            $parcours = $this->courseRepository->find($data['parcoursId']);
            if ($parcours) {
                $session->setCourse($parcours);
            }
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $session->getId()]);
    }

    #[Route('/api/sessions/{id}', name: 'api_sessions_update', methods: ['PUT'])]
    public function updateCourse(int $id, Request $request): JsonResponse
    {
        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Course not found'], 404);
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
        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $this->entityManager->remove($session);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
