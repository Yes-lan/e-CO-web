<?php

namespace App\Entity;

use App\Repository\LogSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogSessionRepository::class)]
class LogSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?\DateTime $time = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalData = null;

    #[ORM\ManyToOne(inversedBy: 'logSessions')]
    private ?Runner $runner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAdditionalData(): ?string
    {
        return $this->additionalData;
    }

    public function setAdditionalData(?string $additionalData): static
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    public function getRunner(): ?Runner
    {
        return $this->runner;
    }

    public function setRunner(?Runner $runner): static
    {
        $this->runner = $runner;

        return $this;
    }
}
