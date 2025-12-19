<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Beacon;
use Doctrine\ORM\EntityManagerInterface;

final class BeaconProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Beacon) {
            return $data;
        }

        $now = new \DateTimeImmutable();

        // Pour les nouveaux beacons
        if (!$data->getId()) {
            $data->setCreatedAt($now);
            $data->setPlacedAt($now); // correspond à placed_at
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        return $data;
    }
}
