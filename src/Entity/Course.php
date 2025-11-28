<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ApiResource]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $createAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $placementCompletedAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $updateAt = null;

    /**
     * @var Collection<int, BoundariesCourse>
     */
    #[ORM\ManyToMany(targetEntity: BoundariesCourse::class, mappedBy: 'idCourse')]
    private Collection $boundariesCourses;

    /**
     * @var Collection<int, Beacon>
     */
    #[ORM\ManyToMany(targetEntity: Beacon::class, mappedBy: 'idCourse')]
    private Collection $beacons;

    /**
     * @var Collection<int, Session>
     */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'idCourse')]
    private Collection $sessions;

    public function __construct()
    {
        $this->boundariesCourses = new ArrayCollection();
        $this->beacons = new ArrayCollection();
        $this->sessions = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreateAt(): ?\DateTime
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTime $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getPlacementCompletedAt(): ?\DateTime
    {
        return $this->placementCompletedAt;
    }

    public function setPlacementCompletedAt(\DateTime $placementCompletedAt): static
    {
        $this->placementCompletedAt = $placementCompletedAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTime $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * @return Collection<int, BoundariesCourse>
     */
    public function getBoundariesCourses(): Collection
    {
        return $this->boundariesCourses;
    }

    public function addBoundariesCourse(BoundariesCourse $boundariesCourse): static
    {
        if (!$this->boundariesCourses->contains($boundariesCourse)) {
            $this->boundariesCourses->add($boundariesCourse);
            $boundariesCourse->addIdCourse($this);
        }

        return $this;
    }

    public function removeBoundariesCourse(BoundariesCourse $boundariesCourse): static
    {
        if ($this->boundariesCourses->removeElement($boundariesCourse)) {
            $boundariesCourse->removeIdCourse($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Beacon>
     */
    public function getBeacons(): Collection
    {
        return $this->beacons;
    }

    public function addBeacon(Beacon $beacon): static
    {
        if (!$this->beacons->contains($beacon)) {
            $this->beacons->add($beacon);
            $beacon->addIdCourse($this);
        }

        return $this;
    }

    public function removeBeacon(Beacon $beacon): static
    {
        if ($this->beacons->removeElement($beacon)) {
            $beacon->removeIdCourse($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setIdCourse($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getIdCourse() === $this) {
                $session->setIdCourse(null);
            }
        }

        return $this;
    }
}
