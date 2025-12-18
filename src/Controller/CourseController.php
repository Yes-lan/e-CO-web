<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\Course;
use App\Entity\Runner;
use App\Repository\SessionRepository;
use App\Repository\CourseRepository;
use App\Repository\RunnerRepository;
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
        private CourseRepository $courseRepository,
        private RunnerRepository $runnerRepository
    ) {}

    #[Route('/sessions', name: 'app_sessions_list')]
    public function listSessions(SessionRepository $sessionRepository): Response
    {
        $currentUser = $this->getUser();
        
        // Get all sessions
        $allSessions = $sessionRepository->findAll();
        
        // Filter sessions to only show those whose course belongs to the current user
        $userSessions = array_filter($allSessions, function(Session $session) use ($currentUser) {
            $course = $session->getCourse();
            return $course && $course->getUser() && $course->getUser()->getId() === $currentUser->getId();
        });
        
        return $this->render('sessions/list.html.twig', [
            'sessions' => $userSessions,
        ]);
    }

    #[Route('/sessions/create', name: 'app_sessions_create')]
    public function createSession(): Response
    {
        return $this->render('sessions/create.html.twig');
    }

    #[Route('/sessions/{id}/view', name: 'app_sessions_view')]
    public function viewSession(int $id): Response
    {
        $currentUser = $this->getUser();
        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Check authorization: only the owner (or admin) can view
        $course = $session->getCourse();
        if (!$course || !$course->getUser() || $course->getUser()->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException('You do not have permission to view this session.');
        }

        return $this->render('sessions/view.html.twig', [
            'session' => $session,
        ]);
    }


    // Note: Session deletion endpoint moved to /sessions/{id}/delete to use session auth instead of JWT
    // This is necessary for cleanup, but editing is disabled
    #[Route('/sessions/{id}/delete', name: 'app_sessions_delete', methods: ['DELETE'])]
    public function deleteCourse(int $id): JsonResponse
    {
        // FIRST - log that we even reached the controller
        error_log("=== DELETE CONTROLLER REACHED === Session ID: {$id}");
        
        $currentUser = $this->getUser();
        error_log("Current user: " . ($currentUser ? $currentUser->getId() . ' (' . $currentUser->getEmail() . ')' : 'NULL'));
        
        if (!$currentUser) {
            error_log("No user logged in - returning 401");
            return new JsonResponse(['error' => 'Unauthorized - No user logged in'], 401);
        }

        $session = $this->sessionRepository->find($id);
        
        if (!$session) {
            error_log("Session not found - returning 404");
            return new JsonResponse(['error' => 'Session not found'], 404);
        }

        // Check ownership via the session's course
        $course = $session->getCourse();
        error_log("Session found: " . $session->getSessionName());
        error_log("Course: " . ($course ? $course->getId() . ' (' . $course->getName() . ')' : 'NULL'));
        
        if (!$course) {
            error_log("No course found - returning 400");
            return new JsonResponse(['error' => 'Session has no associated course'], 400);
        }
        
        $courseUser = $course->getUser();
        error_log("Course owner: " . ($courseUser ? $courseUser->getId() . ' (' . $courseUser->getEmail() . ')' : 'NULL'));
        
        if (!$courseUser || $courseUser->getId() !== $currentUser->getId()) {
            error_log("OWNERSHIP MISMATCH - Current: " . $currentUser->getId() . ", Owner: " . ($courseUser ? $courseUser->getId() : 'NULL'));
            return new JsonResponse([
                'error' => 'Forbidden - You do not own this session\'s course',
                'debug' => [
                    'currentUserId' => $currentUser->getId(),
                    'courseUserId' => $courseUser ? $courseUser->getId() : null,
                ]
            ], 403);
        }

        error_log("Ownership OK - Deleting session");
        
        // TODO: to change if we want to archive the sessions instead of deleting them
        // Delete all runners associated with this session first (cascade delete)
        $runners = $session->getRunners();
        foreach ($runners as $runner) {
            error_log("Deleting runner: " . $runner->getId() . ' - ' . $runner->getName());
            $this->entityManager->remove($runner);
        }
        
        // Now delete the session
        $this->entityManager->remove($session);
        $this->entityManager->flush();
        error_log("Session deleted successfully");

        return new JsonResponse(['success' => true]);
    }

    /**
     * API endpoint to get runner GPS logs and waypoint validations
     * Using /runners/ path instead of /api/ to work with session authentication
     */
    #[Route('/runners/{id}/logs', name: 'runner_logs', methods: ['GET'])]
    public function getRunnerLogs(int $id): JsonResponse
    {
        $runner = $this->runnerRepository->find($id);
        
        if (!$runner) {
            return new JsonResponse(['error' => 'Runner not found'], 404);
        }

        // Get the session and course to access beacons
        $session = $runner->getSession();
        $course = $session ? $session->getCourse() : null;
        $courseBeacons = [];
        
        if ($course) {
            foreach ($course->getBeacons() as $beacon) {
                $courseBeacons[$beacon->getId()] = [
                    'id' => $beacon->getId(),
                    'name' => $beacon->getName(),
                    'latitude' => $beacon->getLatitude(),
                    'longitude' => $beacon->getLongitude(),
                ];
            }
        }

        // Get all log sessions for this runner (GPS tracking)
        $logSessions = $runner->getLogSessions();
        
        $logs = [];
        $waypoints = [];
        
        foreach ($logSessions as $log) {
            // GPS tracking logs (type: 'gps' or 'location')
            if ($log->getType() === 'gps' || $log->getType() === 'location') {
                $logs[] = [
                    'latitude' => $log->getLatitude(),
                    'longitude' => $log->getLongitude(),
                    'timestamp' => $log->getTime()?->format('c'),
                ];
            }
            
            // Beacon validation logs (type: 'beacon_scan')
            if ($log->getType() === 'beacon_scan') {
                // Get beacon ID from additional_data (stored as integer string)
                $additionalData = $log->getAdditionalData();
                $beaconId = $additionalData ? (int)$additionalData : null;
                
                $scannedLat = $log->getLatitude();
                $scannedLng = $log->getLongitude();
                
                // Calculate distance and validation
                $distance = null;
                $isValid = false;
                $beaconName = 'Unknown';
                
                if ($beaconId && isset($courseBeacons[$beaconId]) && $scannedLat && $scannedLng) {
                    $trueLat = $courseBeacons[$beaconId]['latitude'];
                    $trueLng = $courseBeacons[$beaconId]['longitude'];
                    $beaconName = $courseBeacons[$beaconId]['name'];
                    
                    // Calculate distance in meters using Haversine formula
                    $distance = $this->calculateDistance($trueLat, $trueLng, $scannedLat, $scannedLng);
                    
                    // Beacon is valid if within 20 meters (adjustable threshold)
                    $isValid = $distance <= 20;
                }
                
                $waypoints[] = [
                    'beaconId' => $beaconId,
                    'beaconName' => $beaconName,
                    'timestamp' => $log->getTime()?->format('c'),
                    'isValid' => $isValid,
                    'distance' => $distance,
                    'latitude' => $scannedLat,
                    'longitude' => $scannedLng,
                ];
            }
        }

        return new JsonResponse([
            'logs' => $logs,
            'waypoints' => $waypoints,
        ]);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * Returns distance in meters
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
