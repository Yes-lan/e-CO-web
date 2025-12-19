<?php

namespace App\DataFixtures;

use App\Entity\Beacon;
use App\Entity\Course;
use App\Entity\Establishment;
use App\Entity\Language;
use App\Entity\LogSession;
use App\Entity\Runner;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Establishment
        $establishment = new Establishment();
        $establishment->setName('test establishment');
        $manager->persist($establishment);

        // 2. Languages
        $languages = [
            'fr' => 'Français',
            'en' => 'English',
            'eu' => 'Euskara'
        ];

        foreach ($languages as $code => $text) {
            $lang = new Language();
            $lang->setCode($code);
            $lang->setDisplayedText($text);
            $manager->persist($lang);
        }

        // 3. Users
        // Admin (Seb Lecornu)
        $admin = new User();
        $admin->setEstablishment($establishment);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setEmail('seb@admin.fr');
        $admin->setFirstName('Seb');
        $admin->setLastName('Lecornu');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'motdepasseADMIN2000#'));
        $manager->persist($admin);

        // User (Sandra Doe)
        $sandra = new User();
        $sandra->setEstablishment($establishment);
        $sandra->setRoles(['ROLE_USER']);
        $sandra->setEmail('sandra@user.fr');
        $sandra->setFirstName('Sandra');
        $sandra->setLastName('Doe');
        // Setting password to "motdepasseUSER2000##" as per previous context
        $sandra->setPassword($this->passwordHasher->hashPassword($sandra, 'motdepasseUSER2000##'));
        $manager->persist($sandra);

        // Additional Users
        $users = [$admin, $sandra];
        $extraUsersData = [
            ['Michel', 'Dupont', 'michel@user.fr'],
            ['Jean', 'Martin', 'jean@user.fr'],
            ['Paul', 'Durand', 'paul@user.fr'],
            ['Pierre', 'Petit', 'pierre@user.fr'],
            ['Marie', 'Leroy', 'marie@user.fr'],
            ['Claire', 'Moreau', 'claire@user.fr'],
        ];

        foreach ($extraUsersData as $userData) {
            $user = new User();
            $user->setEstablishment($establishment);
            $user->setRoles(['ROLE_USER']);
            $user->setFirstName($userData[0]);
            $user->setLastName($userData[1]);
            $user->setEmail($userData[2]);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'motdepasseUSER2000##'));
            $manager->persist($user);
            $users[] = $user;
        }

        // 4. Courses (Linked to Sandra)
        $coursesDetails = [
            [
                'name' => 'test',
                'created' => '2025-12-07 20:28:21',
                // Jardin de l'Évêché (Loop)
                'beacons' => [
                    ['Balise 1', 1.2660, 45.8285, 'control', true],
                    ['Balise 2', 1.2670, 45.8285, 'control', true],
                    ['Balise 3', 1.2670, 45.8275, 'control', true],
                    ['Balise 4', 1.2660, 45.8275, 'control', true],
                    ['Balise 5', 1.2665, 45.8280, 'control', true],
                    ['Départ', 1.2655, 45.8290, 'start', true],
                    ['Arrivée', 1.2655, 45.8290, 'finish', true],
                ]
            ],
            [
                'name' => 'azerty',
                'created' => '2025-12-08 21:58:43',
                // Champ de Juillet (Zig-Zag)
                'beacons' => [
                    ['Balise 1', 1.2665, 45.8360, 'control', false],
                    ['Balise 2', 1.2670, 45.8365, 'control', false],
                    ['Balise 3', 1.2675, 45.8360, 'control', false],
                    ['Balise 4', 1.2680, 45.8365, 'control', false],
                    ['Balise 5', 1.2685, 45.8360, 'control', false],
                    ['Départ', 1.2660, 45.8355, 'start', false],
                    ['Arrivée', 1.2690, 45.8370, 'finish', false],
                ]
            ],
            [
                'name' => 'te',
                'created' => '2025-12-08 22:06:41',
                // Bords de Vienne (Line)
                'beacons' => [
                    ['bite', 1.2640, 45.8250, 'control', false],
                    ['Départ', 1.2630, 45.8252, 'start', false],
                    ['Arrivée', 1.2650, 45.8248, 'finish', false],
                ]
            ],
            // New Courses
            [
                'name' => 'Technopole Ester',
                'created' => '2025-12-09 09:00:00',
                // Ester Technopole (North)
                'beacons' => [
                    ['Dome', 1.2850, 45.8600, 'control', false],
                    ['Parking', 1.2860, 45.8610, 'control', false],
                    ['Lac', 1.2840, 45.8590, 'control', false],
                    ['Départ', 1.2855, 45.8605, 'start', false],
                    ['Arrivée', 1.2855, 45.8605, 'finish', false],
                ]
            ],
            [
                'name' => 'Bois de la Bastide',
                'created' => '2025-12-09 09:15:00',
                // Beaubreuil / Bastide Woods
                'beacons' => [
                    ['Chêne', 1.2950, 45.8700, 'control', false],
                    ['Rocher', 1.2960, 45.8710, 'control', false],
                    ['Sentier', 1.2940, 45.8690, 'control', false],
                    ['Départ', 1.2955, 45.8705, 'start', false],
                    ['Arrivée', 1.2955, 45.8705, 'finish', false],
                ]
            ],
            [
                'name' => 'Campus Vanteaux',
                'created' => '2025-12-09 09:30:00',
                // University Vanteaux (South-West)
                'beacons' => [
                    ['BU', 1.2400, 45.8200, 'control', false],
                    ['Resto U', 1.2410, 45.8210, 'control', false],
                    ['Amphi', 1.2390, 45.8190, 'control', false],
                    ['Départ', 1.2405, 45.8205, 'start', false],
                    ['Arrivée', 1.2405, 45.8205, 'finish', false],
                ]
            ],
            [
                'name' => 'Lac d\'Uzurat',
                'created' => '2025-12-09 09:45:00',
                // Uzurat Lake (North-East) - Loop around lake
                'beacons' => [
                    ['Plage', 1.2800, 45.8500, 'control', false],
                    ['Ponton', 1.2810, 45.8510, 'control', false],
                    ['Boqueteau', 1.2790, 45.8490, 'control', false],
                    ['Départ', 1.2805, 45.8505, 'start', false],
                    ['Arrivée', 1.2805, 45.8505, 'finish', false],
                ]
            ],
            [
                'name' => 'Centre Historique',
                'created' => '2025-12-09 10:00:00',
                // City Center / Halles
                'beacons' => [
                    ['Halles', 1.2580, 45.8290, 'control', false],
                    ['Place Motte', 1.2590, 45.8300, 'control', false],
                    ['Eglise St Michel', 1.2570, 45.8280, 'control', false],
                    ['Départ', 1.2585, 45.8295, 'start', false],
                    ['Arrivée', 1.2585, 45.8295, 'finish', false],
                ]
            ],
            // Courses from app.sql
            [
                'name' => 'test3',
                'created' => '2025-12-08 20:44:17',
                'beacons' => [
                    ['Balise 1', 1.2768559, 45.8419284, 'control', true],
                    ['Balise 2', 1.2766791, 45.8419697, 'control', true],
                    ['Balise 3', 1.276532, 45.8419712, 'control', true],
                    ['Balise 4', 1.276532, 45.8419712, 'control', true],
                    ['Départ', 1.2766512, 45.8418114, 'start', true],
                    ['Arrivée', 1.276838, 45.8419331, 'finish', true],
                ]
            ],
            [
                'name' => 'test4',
                'created' => '2025-12-08 23:46:49',
                'beacons' => [
                    ['Balise 1', 1.2768628, 45.8419241, 'control', true],
                    ['Balise 2', 1.2766296, 45.8419817, 'control', true],
                    ['Départ', 1.2768628, 45.8419462, 'start', true],
                    ['Arrivée', 1.2768654, 45.8419501, 'finish', true],
                ]
            ],
            [
                'name' => 'test5',
                'created' => '2025-12-08 23:55:22',
                'beacons' => [
                    ['Balise 1', 1.276851, 45.841917, 'control', true],
                    ['Balise 2', 1.2768722, 45.8418729, 'control', true],
                    ['Balise 3', 1.2768594, 45.8419544, 'control', true],
                    ['Départ', 1.2768428, 45.8419365, 'start', true],
                    ['Arrivée', 1.2768879, 45.8419552, 'finish', true],
                ]
            ],
            [
                'name' => 'test6',
                'created' => '2025-12-09 00:00:34',
                'beacons' => [
                    ['Balise 1', 1.276861, 45.841933, 'control', true],
                    ['Balise 2', 1.2768349, 45.8420639, 'control', true],
                    ['Balise 3', 1.2766573, 45.8419905, 'control', true],
                    ['Balise 4', 1.276933, 45.8420349, 'control', true],
                    ['Balise 5', 1.2771313, 45.842101, 'control', true],
                    ['Départ', 1.2768591, 45.8419371, 'start', true],
                    ['Arrivée', 1.2768663, 45.841925, 'finish', true],
                ]
            ],
            [
                'name' => 'test7',
                'created' => '2025-12-09 00:10:47',
                'beacons' => [
                    ['Balise 1', 0, 0, 'control', false],
                    ['Balise 2', 0, 0, 'control', false],
                    ['Balise 3', 1.2569988, 45.8277154, 'control', true],
                    ['Balise 4', 1.257169, 45.8276912, 'control', true],
                    ['Balise 5', 1.2768612, 45.8419217, 'control', true],
                    ['Balise 6', 1.2567761, 45.8275716, 'control', true],
                    ['Balise 7', 0, 0, 'control', false],
                    ['Départ', 1.2572079, 45.8276795, 'start', true],
                    ['Arrivée', 0, 0, 'finish', false],
                ]
            ]
        ];

        $courseEntities = [];

        foreach ($coursesDetails as $cData) {
            $course = new Course();
            // Assign random user to course
            $randomUser = $users[array_rand($users)];
            $course->setUser($randomUser);
            $course->setName($cData['name']);
            $course->setDescription('');
            $course->setStatus('draft');
            $date = new \DateTime($cData['created']);
            $course->setCreateAt($date);
            $course->setPlacementCompletedAt($date);
            $course->setUpdateAt($date);
            $course->setSameStartFinish(false);
            $manager->persist($course);

            $courseEntities[$cData['name']] = $course;

            foreach ($cData['beacons'] as $index => $bData) {
                $beacon = new Beacon();
                $beacon->setName($bData[0]);
                $beacon->setLongitude($bData[1]);
                $beacon->setLatitude($bData[2]);
                $beacon->setType($bData[3]);
                $beacon->setIsPlaced($bData[4]);
                $beacon->setCreatedAt($date);
                if ($bData[4]) {
                    $beacon->setPlacedAt(new \DateTime('2025-12-07 21:18:34'));
                }

                // Construct QR JSON based on SQL logic
                $qrType = match ($bData[3]) {
                    'start' => 'START',
                    'finish' => 'FINISH',
                    default => 'WAYPOINT'
                };
                // Simulating the UUID/ID logic from SQL roughly
                $waypointId = $index + 1; // logical ID
                $qr = json_encode([
                    "type" => $qrType,
                    "courseId" => null, // Would be DB ID, simplified here
                    "courseName" => $cData['name'],
                    "waypointId" => $waypointId,
                    "waypointName" => $bData[0],
                    "courseCreated" => $cData['created']
                ]);
                $beacon->setQr($qr);

                $beacon->addCourse($course);
                $manager->persist($beacon);
            }
        }

        // 5. Sessions and Runners
        $runnerNames = ['Alice', 'Bob', 'Charlie', 'David', 'Eve', 'Frank', 'Grace', 'Heidi', 'Ivan', 'Judy', 'Mallory', 'Oscar'];

        // Specific sessions from app.sql
        $specificSessions = [
            'test3' => [
                ['name' => 'course 1', 'start' => '2025-12-09 00:42:07', 'end' => '2025-12-09 01:01:09'],
                ['name' => 'course2', 'start' => '2025-12-09 00:54:21', 'end' => '2025-12-09 01:01:28'],
            ],
            'test4' => [
                ['name' => 'course 1', 'start' => '2025-12-09 00:53:53', 'end' => '2025-12-09 01:01:24'],
            ],
            'test5' => [
                ['name' => 'test admin', 'start' => '2025-12-09 08:13:48', 'end' => '2025-12-09 09:48:46'],
            ],
        ];

        foreach ($courseEntities as $courseName => $courseEntity) {

            // Check if there are specific sessions for this course
            if (array_key_exists($courseName, $specificSessions)) {
                $sessionsToCreate = $specificSessions[$courseName];
                foreach ($sessionsToCreate as $sessData) {
                    $session = new Session();
                    $session->setCourse($courseEntity);
                    $session->setSessionName($sessData['name']);
                    
                    // Create runners for specific sessions
                    $nbRunners = rand(3, 6);
                    $session->setNbRunner($nbRunners);
                    $session->setSessionStart(new \DateTimeImmutable($sessData['start']));
                    $session->setSessionEnd(new \DateTimeImmutable($sessData['end']));
                    $manager->persist($session);
                    
                    // Generate runners and logs for specific sessions
                    $baseDate = new \DateTimeImmutable($sessData['start']);
                    $this->createRunnersWithLogs($manager, $session, $courseEntity, $baseDate, $nbRunners, $runnerNames);
                }
                continue;
            }

            // Determine how many sessions to generate (1 to 3)
            $sessionCount = ($courseName === 'test') ? 1 : rand(1, 3);

            for ($s = 1; $s <= $sessionCount; $s++) {
                $session = new Session();
                $session->setCourse($courseEntity);

                // Keep specific name for 'test' course to match original intent if needed, else generic
                if ($courseName === 'test' && $s === 1) {
                    $session->setSessionName('machin');
                    $baseDate = new \DateTimeImmutable('2025-12-24 10:00:00');
                } else {
                    $session->setSessionName(sprintf('Session %s %d', $courseName, $s));
                    // Random date in Dec 2025
                    $day = rand(10, 28);
                    $baseDate = new \DateTimeImmutable("2025-12-$day 10:00:00");
                }

                $nbRunners = rand(3, 8);
                $session->setNbRunner($nbRunners);
                $session->setSessionStart($baseDate);
                $session->setSessionEnd($baseDate->modify('+4 hours'));
                $manager->persist($session);

                // Create Runners for this session
                $this->createRunnersWithLogs($manager, $session, $courseEntity, $baseDate, $nbRunners, $runnerNames);
            }
        }

        $manager->flush();
    }

    /**
     * Create runners with GPS logs and beacon scans for a session
     */
    private function createRunnersWithLogs(
        ObjectManager $manager,
        Session $session,
        Course $courseEntity,
        \DateTimeImmutable $baseDate,
        int $nbRunners,
        array $runnerNames
    ): void {
        for ($r = 0; $r < $nbRunners; $r++) {
            $runner = new Runner();
            $runner->setSession($session);
            $rName = $runnerNames[array_rand($runnerNames)] . ' ' . $r;
            $runner->setName($rName);

            $departureImmutable = $baseDate->modify('+' . rand(0, 30) . ' minutes');
            $departure = \DateTime::createFromImmutable($departureImmutable);
            $runner->setDeparture($departure);

            $arrivalImmutable = $departureImmutable->modify('+' . rand(20, 90) . ' minutes');
            $arrival = \DateTime::createFromImmutable($arrivalImmutable);
            $runner->setArrival($arrival);

            $manager->persist($runner);

            // Generate GPS logs and beacon scans for realistic tracking
            $beaconsArray = $courseEntity->getBeacons()->toArray();
            $numBeacons = count($beaconsArray);
            
            if ($numBeacons > 0) {
                $runDuration = ($arrival->getTimestamp() - $departure->getTimestamp()) / 60; // minutes
                $timePerBeacon = ($runDuration * 60) / $numBeacons; // seconds per beacon
                
                $currentTime = clone $departure;
                
                // Iterate through beacons
                foreach ($beaconsArray as $idx => $beacon) {
                    // Beacon scan at this beacon
                    $scanLog = new LogSession();
                    $scanLog->setRunner($runner);
                    $scanLog->setType('beacon_scan');
                    $scanLog->setTime(clone $currentTime);
                    $scanLog->setLatitude($beacon->getLatitude());
                    $scanLog->setLongitude($beacon->getLongitude());
                    $scanLog->setAdditionalData((string)($beacon->getId() ?? 0));
                    $manager->persist($scanLog);
                    
                    // GPS logs between this beacon and next (every 10 seconds)
                    if ($idx < $numBeacons - 1) {
                        $nextBeacon = $beaconsArray[$idx + 1];
                        $steps = floor($timePerBeacon / 10);
                        
                        for ($s = 0; $s < $steps; $s++) {
                            $progress = $s / max($steps, 1);
                            $gpsLat = $beacon->getLatitude() + (($nextBeacon->getLatitude() - $beacon->getLatitude()) * $progress);
                            $gpsLon = $beacon->getLongitude() + (($nextBeacon->getLongitude() - $beacon->getLongitude()) * $progress);
                            
                            // Add GPS drift
                            $gpsLat += (rand(-3, 3) / 100000);
                            $gpsLon += (rand(-3, 3) / 100000);
                            
                            $gpsTime = (clone $currentTime)->modify('+' . ($s * 10) . ' seconds');
                            
                            $logGps = new LogSession();
                            $logGps->setRunner($runner);
                            $logGps->setType('gps');
                            $logGps->setTime($gpsTime);
                            $logGps->setLatitude($gpsLat);
                            $logGps->setLongitude($gpsLon);
                            $manager->persist($logGps);
                        }
                    }
                    
                    $currentTime->modify('+' . (int)$timePerBeacon . ' seconds');
                }
            }
        }
    }
}
