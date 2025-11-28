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

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $time = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $position = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $additionalData = null;

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

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
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
}
