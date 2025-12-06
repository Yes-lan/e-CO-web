<?php

namespace App\Command;

use App\Entity\Beacon;
use App\Entity\BoundariesCourse;
use App\Entity\Course;
use App\Entity\LogSession;
use App\Entity\Runner;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:add-test-data',
    description: 'Add comprehensive test data for authorization testing',
)]
class AddTestDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Adding Test Data');

        try {
            // Get existing user (test@test.fr)
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@test.fr']);
            if (!$existingUser) {
                $io->error('User test@test.fr not found. Please create it first.');
                return Command::FAILURE;
            }

            // Add additional test users
            $io->section('Creating additional users...');
            $users = [];
            $teacherNames = [
                ['first' => 'Marie', 'last' => 'Dupont'],
                ['first' => 'Jean', 'last' => 'Martin'],
                ['first' => 'Sophie', 'last' => 'Bernard'],
            ];
            
            for ($i = 1; $i <= 3; $i++) {
                $email = "teacher{$i}@school.fr";
                $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $users[] = $existing;
                    $io->writeln("User {$email} already exists, skipping...");
                    continue;
                }

                $user = new User();
                $user->setEmail($email);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
                $user->setRoles(['ROLE_USER']);
                $user->setFirstName($teacherNames[$i - 1]['first']);
                $user->setLastName($teacherNames[$i - 1]['last']);
                $this->entityManager->persist($user);
                $users[] = $user;
                $io->writeln("Created user: {$email}");
            }
            $this->entityManager->flush();

            // Create courses for existing user (test@test.fr)
            $io->section('Creating courses...');
            $courses = [];
            $courseData = [
                ['name' => 'Central Park Circuit', 'description' => 'Beginner-friendly urban orienteering course', 'center' => [48.8566, 2.3522], 'status' => 'finished'],
                ['name' => 'Forest Trail Advanced', 'description' => 'Challenging forest orienteering with elevation', 'center' => [48.8700, 2.3400], 'status' => 'finished'],
                ['name' => 'University Campus Route', 'description' => 'Educational orienteering around campus landmarks', 'center' => [48.8450, 2.3650], 'status' => 'finished'],
                ['name' => 'Historic District Tour', 'description' => 'Cultural orienteering through historical sites', 'center' => [48.8600, 2.3300], 'status' => 'in_progress'],
                ['name' => 'Riverside Challenge', 'description' => 'Advanced riverside orienteering course', 'center' => [48.8500, 2.3700], 'status' => 'draft'],
            ];

            foreach ($courseData as $index => $data) {
                $course = new Course();
                $course->setName($data['name']);
                $course->setDescription($data['description']);
                $course->setStatus($data['status']);
                $course->setUser($existingUser);
                $course->setCreateAt(new \DateTime("-" . (30 - $index * 5) . " days"));
                $course->setUpdateAt(new \DateTime("-" . (25 - $index * 5) . " days"));
                $course->setPlacementCompletedAt(new \DateTime("-" . (27 - $index * 5) . " days"));
                $this->entityManager->persist($course);
                $courses[] = ['entity' => $course, 'center' => $data['center']];
                $io->writeln("Created course: {$data['name']}");
            }
            $this->entityManager->flush();

            // Add beacons and boundaries to each course
            $io->section('Creating beacons and boundaries...');
            foreach ($courses as $courseData) {
                $course = $courseData['entity'];
                $center = $courseData['center'];
                
                // Create 5-8 beacons per course
                $beaconCount = rand(5, 8);
                for ($i = 1; $i <= $beaconCount; $i++) {
                    $beacon = new Beacon();
                    $beacon->setName("Beacon {$i}");
                    $beacon->setLatitude($center[0] + (rand(-50, 50) / 10000));
                    $beacon->setLongitude($center[1] + (rand(-50, 50) / 10000));
                    $beacon->setType('waypoint');
                    $beacon->setIsPlaced(true);
                    $beacon->setPlacedAt(new \DateTime("-" . (25 - $i) . " days"));
                    $beacon->setCreatedAt(new \DateTime("-" . (28 - $i) . " days"));
                    $beacon->setQr("QR_CODE_DATA_{$course->getId()}_{$i}");
                    $beacon->setDescription("Waypoint {$i} for {$course->getName()}");
                    $beacon->addCourse($course);
                    $this->entityManager->persist($beacon);
                }

                // Create boundary polygon (4 corners)
                $corners = [
                    [$center[0] + 0.005, $center[1] - 0.005], // NW
                    [$center[0] + 0.005, $center[1] + 0.005], // NE
                    [$center[0] - 0.005, $center[1] + 0.005], // SE
                    [$center[0] - 0.005, $center[1] - 0.005], // SW
                ];
                foreach ($corners as $index => $corner) {
                    $boundary = new BoundariesCourse();
                    $boundary->setLatitude($corner[0]);
                    $boundary->setLongitude($corner[1]);
                    $boundary->setCourse($course);
                    $this->entityManager->persist($boundary);
                }
                
                $io->writeln("Added {$beaconCount} beacons and 4 boundary points to {$course->getName()}");
            }
            $this->entityManager->flush();

            // Create sessions for each course (3 courses get sessions)
            $io->section('Creating sessions...');
            $sessions = [];
            for ($i = 0; $i < 3; $i++) {
                $course = $courses[$i]['entity'];
                
                $session = new Session();
                $session->setSessionName("Session " . ($i + 1) . " - " . $course->getName());
                $session->setCourse($course);
                $session->setSessionStart(new \DateTimeImmutable("-" . (20 - $i * 5) . " days 09:00:00"));
                $session->setSessionEnd(new \DateTimeImmutable("-" . (20 - $i * 5) . " days 11:30:00"));
                $session->setNbRunner(0); // Will be updated when runners are added
                $this->entityManager->persist($session);
                $sessions[] = $session;
                $io->writeln("Created session: {$session->getSessionName()}");
            }
            $this->entityManager->flush();

            // Add runners to each session
            $io->section('Creating runners and GPS logs...');
            $runnerNames = [
                'Alice Smith', 'Bob Johnson', 'Charlie Brown', 'Diana Davis', 'Eva Wilson', 
                'Frank Moore', 'Grace Taylor', 'Henry Anderson', 'Iris Thomas', 'Jack Jackson',
                'Kate White', 'Leo Harris', 'Mia Martin', 'Noah Garcia', 'Olivia Martinez', 
                'Paul Robinson', 'Quinn Clark', 'Rose Rodriguez', 'Sam Lewis', 'Tina Lee'
            ];

            foreach ($sessions as $sessionIndex => $session) {
                $runnerCount = rand(18, 20);
                for ($i = 0; $i < $runnerCount; $i++) {
                    $runner = new Runner();
                    $runner->setName($runnerNames[$i % count($runnerNames)]);
                    $runner->setSession($session);
                    
                    // Set departure and arrival times
                    $startTime = $session->getSessionStart();
                    $runner->setDeparture(new \DateTime($startTime->format('Y-m-d H:i:s')));
                    $runner->setArrival((new \DateTime($startTime->format('Y-m-d H:i:s')))->modify('+' . rand(60, 120) . ' minutes'));
                    
                    $this->entityManager->persist($runner);

                    // Add 10-15 GPS logs per runner
                    $logCount = rand(10, 15);
                    $center = $courses[$sessionIndex]['center'];
                    
                    for ($j = 0; $j < $logCount; $j++) {
                        $log = new LogSession();
                        $log->setRunner($runner);
                        $log->setType('gps_tracking');
                        $log->setLatitude($center[0] + (rand(-40, 40) / 10000));
                        $log->setLongitude($center[1] + (rand(-40, 40) / 10000));
                        $timestamp = (new \DateTime($startTime->format('Y-m-d H:i:s')))->modify("+{$j} minutes");
                        $log->setTime($timestamp);
                        $log->setAdditionalData(json_encode(['accuracy' => rand(5, 15), 'speed' => rand(0, 5)]));
                        $this->entityManager->persist($log);
                    }
                }
                
                // Update nbRunner count
                $session->setNbRunner($runnerCount);
                $io->writeln("Added {$runnerCount} runners with GPS logs to session: {$session->getSessionName()}");
            }
            $this->entityManager->flush();

            $io->success('Test data added successfully!');
            $io->section('Summary');
            $io->listing([
                'Users: ' . (count($users) + 1) . ' (including test@test.fr)',
                'Courses: ' . count($courses),
                'Sessions: ' . count($sessions),
                'Beacons: ' . ($this->entityManager->getRepository(Beacon::class)->count([])),
                'Boundary Points: ' . ($this->entityManager->getRepository(BoundariesCourse::class)->count([])),
                'Runners: ' . ($this->entityManager->getRepository(Runner::class)->count([])),
                'GPS Logs: ' . ($this->entityManager->getRepository(LogSession::class)->count([])),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error adding test data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
