<?php

namespace App\Entity;

use App\Repository\RunnerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RunnerRepository::class)]
class Runner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $departure = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $arrival = null;

    #[ORM\ManyToOne(inversedBy: 'runners')]
    private ?Session $idSession = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDeparture(): ?\DateTime
    {
        return $this->departure;
    }

    public function setDeparture(\DateTime $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getArrival(): ?\DateTime
    {
        return $this->arrival;
    }

    public function setArrival(\DateTime $arrival): static
    {
        $this->arrival = $arrival;

        return $this;
    }

    public function getIdSession(): ?Session
    {
        return $this->idSession;
    }

    public function setIdSession(?Session $idSession): static
    {
        $this->idSession = $idSession;

        return $this;
    }
}
