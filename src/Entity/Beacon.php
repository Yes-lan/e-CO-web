<?php

namespace App\Entity;

use App\Repository\BeaconRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: BeaconRepository::class)]
class Beacon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $isPlaced = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $placedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $qr = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'beacons')]
    private Collection $course;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->course = new ArrayCollection();
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

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
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
    public function getCourse(): Collection
    {
        return $this->course;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->course->contains($course)) {
            $this->course->add($course);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        $this->course->removeElement($course);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
