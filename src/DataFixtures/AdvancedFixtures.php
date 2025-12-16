<?php

namespace App\DataFixtures;

use App\Entity\Beacon;
use App\Entity\Course;
use App\Entity\LogSession;
use App\Entity\Runner;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AdvancedFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Retrieve a user to assign these courses to (e.g., 'seb@admin.fr' or 'sandra@user.fr')
        // We'll use Sandra as she is the main user in the context contextfixtures.md seems to imply (or just a user)
        $sandra = $manager->getRepository(User::class)->findOneBy(['email' => 'sandra@user.fr']);
        if (!$sandra) {
            // Fallback if Sandra is missing, though AppFixtures should have created her.
            $sandra = $manager->getRepository(User::class)->findOneBy([]) ?: new User();
        }

        // 2. Define Courses
        $coursesDetails = [
            [
                'name' => 'Bois de la Bastide 2',
                'description' => 'Un parcours composé de 12 balises situées au bois de la bastide à Limoges.',
                'baseLat' => 45.8700,
                'baseLon' => 1.2950,
                'numBeacons' => 12,
            ],
            [
                'name' => 'Centre Ville 2',
                'description' => 'Un parcours composé de 12 balises situées au centre ville de Limoges.',
                'baseLat' => 45.8300,
                'baseLon' => 1.2600,
                'numBeacons' => 12,
            ]
        ];

        foreach ($coursesDetails as $cData) {
            $course = new Course();
            $course->setName($cData['name']);
            $course->setDescription($cData['description']);
            $course->setStatus('published');
            $course->setUser($sandra); // Assign to Sandra
            $now = new \DateTimeImmutable();
            $course->setCreateAt(\DateTime::createFromImmutable($now));
            $course->setUpdateAt(\DateTime::createFromImmutable($now));
            $course->setPlacementCompletedAt(\DateTime::createFromImmutable($now));
            $course->setSameStartFinish(false);
            $manager->persist($course);

            // Generate Beacons
            $beacons = [];

            // Start
            $startBeacon = $this->createBeacon('Départ', $cData['baseLat'], $cData['baseLon'], 'start', $course, $now);
            $manager->persist($startBeacon);
            $beacons[] = $startBeacon;

            // 10 Controls (waypoints)
            for ($i = 1; $i <= 10; $i++) {
                // Random offset for coordinates (~200-500m)
                $latOffset = (rand(-50, 50) / 10000);
                $lonOffset = (rand(-50, 50) / 10000);
                $bName = "Balise $i";
                $beacon = $this->createBeacon($bName, $cData['baseLat'] + $latOffset, $cData['baseLon'] + $lonOffset, 'control', $course, $now);
                $manager->persist($beacon);
                $beacons[] = $beacon;
            }

            // Finish
            // Place finish slightly away from start
            $finishBeacon = $this->createBeacon('Arrivée', $cData['baseLat'] + 0.001, $cData['baseLon'] + 0.001, 'finish', $course, $now);
            $manager->persist($finishBeacon);
            $beacons[] = $finishBeacon;

            // Create Session
            $session = new Session();
            $session->setCourse($course);
            $session->setSessionName('Session ' . $cData['name']);
            $session->setNbRunner(2);
            $sessionStart = new \DateTimeImmutable('2025-12-19 14:00:00');
            $session->setSessionStart($sessionStart);
            $session->setSessionEnd($sessionStart->modify('+2 hours'));
            $manager->persist($session);

            // Create Runners
            // Lilou: 1.5x faster. 
            // Chris: Normal speed.
            // Let's say Chris takes 60 mins. Lilou takes 40 mins.

            $runnersData = [
                ['name' => 'Chris', 'duration_minutes' => 60],
                ['name' => 'Lilou', 'duration_minutes' => 40],
            ];

            foreach ($runnersData as $rData) {
                $runner = new Runner();
                $runner->setSession($session);
                $runner->setName($rData['name']);

                $departure = \DateTime::createFromImmutable($sessionStart->modify('+5 minutes'));
                $runner->setDeparture($departure);

                $arrival = (clone $departure)->modify('+' . $rData['duration_minutes'] . ' minutes');
                $runner->setArrival($arrival);
                $manager->persist($runner);

                // Checkpoints for path interpolation
                // Start -> B1 -> B2 ... -> B10 -> Finish
                // Simplify: Just loop through beacons in order.
                $pathBeacons = $beacons;

                $this->simulateRun($manager, $runner, $pathBeacons, $rData['duration_minutes']);
            }
        }

        $manager->flush();
    }

    private function createBeacon(string $name, float $lat, float $lon, string $type, Course $course, \DateTimeImmutable $date): Beacon
    {
        $beacon = new Beacon();
        $beacon->setName($name);
        $beacon->setLatitude($lat);
        $beacon->setLongitude($lon);
        $beacon->setType($type);
        $beacon->setIsPlaced(true);
        $beacon->setCreatedAt(new \DateTime($date->format('Y-m-d H:i:s')));
        $beacon->setPlacedAt(new \DateTime($date->format('Y-m-d H:i:s')));

        $qrType = match ($type) {
            'start' => 'START',
            'finish' => 'FINISH',
            default => 'WAYPOINT'
        };

        $qr = json_encode([
            "type" => $qrType,
            "courseName" => $course->getName(),
            "waypointName" => $name,
        ]);
        $beacon->setQr($qr);
        $beacon->addCourse($course);

        return $beacon;
    }

    /**
     * Simulates GPS logs and beacon scans for a runner along a path of beacons.
     */
    private function simulateRun(ObjectManager $manager, Runner $runner, array $beacons, int $durationMinutes): void
    {
        $startTime = $runner->getDeparture();
        $totalSeconds = $durationMinutes * 60;

        // We have count($beacons) points to visit.
        // Segments = count - 1.
        // Time per segment (simplified: uniform speed between beacons)
        $segmentCount = count($beacons) - 1;
        if ($segmentCount < 1)
            return;

        $secondsPerSegment = $totalSeconds / $segmentCount;

        // Iterate through segments
        for ($i = 0; $i < $segmentCount; $i++) {
            $startBeacon = $beacons[$i];
            $endBeacon = $beacons[$i + 1];

            $segmentStartTime = (clone $startTime)->modify('+' . (int) ($i * $secondsPerSegment) . ' seconds');

            // Record Beacon Scan at the start of the segment (arriving at a beacon)
            // (Except maybe finish, which is recorded at end of last segment - handled by structure)
            // Logic: Scan $startBeacon
            $scan = new LogSession();
            $scan->setRunner($runner);
            $scan->setType('beacon_scan');
            $scan->setTime(clone $segmentStartTime);
            $scan->setLatitude($startBeacon->getLatitude());
            $scan->setLongitude($startBeacon->getLongitude());
            $scan->setAdditionalData('beacon_scan_' . $startBeacon->getName());
            $manager->persist($scan);

            // Interpolate GPS logs between startBeacon and endBeacon
            // Log every 10 seconds
            $steps = floor($secondsPerSegment / 10);

            $latDiff = $endBeacon->getLatitude() - $startBeacon->getLatitude();
            $lonDiff = $endBeacon->getLongitude() - $startBeacon->getLongitude();

            for ($k = 0; $k < $steps; $k++) {
                $progress = $k / $steps; // 0 to just under 1
                $currentLat = $startBeacon->getLatitude() + ($latDiff * $progress);
                $currentLon = $startBeacon->getLongitude() + ($lonDiff * $progress);

                $logTime = (clone $segmentStartTime)->modify('+' . (int) ($k * 10) . ' seconds');

                $gps = new LogSession();
                $gps->setRunner($runner);
                $gps->setType('gps');
                $gps->setTime($logTime);
                $gps->setLatitude($currentLat);
                $gps->setLongitude($currentLon);
                $manager->persist($gps);
            }
        }

        // Scan Last Beacon (Finish)
        $lastBeacon = $beacons[count($beacons) - 1];
        $finishTime = (clone $startTime)->modify('+' . $totalSeconds . ' seconds');

        $scanFinish = new LogSession();
        $scanFinish->setRunner($runner);
        $scanFinish->setType('beacon_scan');
        $scanFinish->setTime($finishTime);
        $scanFinish->setLatitude($lastBeacon->getLatitude());
        $scanFinish->setLongitude($lastBeacon->getLongitude());
        $scanFinish->setAdditionalData('beacon_scan_' . $lastBeacon->getName());
        $manager->persist($scanFinish);
    }
}
