<?php

namespace App\Entity;

use App\Repository\BeaconRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeaconRepository::class)]
class Beacon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $isPlaced = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $placedAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $qr = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'beacons')]
    private Collection $idCourse;

    public function __construct()
    {
        $this->idCourse = new ArrayCollection();
    }

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

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
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

    public function isPlaced(): ?bool
    {
        return $this->isPlaced;
    }

    public function setIsPlaced(bool $isPlaced): static
    {
        $this->isPlaced = $isPlaced;

        return $this;
    }

    public function getPlacedAt(): ?\DateTime
    {
        return $this->placedAt;
    }

    public function setPlacedAt(?\DateTime $placedAt): static
    {
        $this->placedAt = $placedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getQr(): ?string
    {
        return $this->qr;
    }

    public function setQr(string $qr): static
    {
        $this->qr = $qr;

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getIdCourse(): Collection
    {
        return $this->idCourse;
    }

    public function addIdCourse(Course $idCourse): static
    {
        if (!$this->idCourse->contains($idCourse)) {
            $this->idCourse->add($idCourse);
        }

        return $this;
    }

    public function removeIdCourse(Course $idCourse): static
    {
        $this->idCourse->removeElement($idCourse);

        return $this;
    }
}
