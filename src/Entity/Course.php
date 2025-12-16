<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/parcours',
            security: "is_granted('ROLE_USER')",
            provider: 'App\State\UserCoursesProvider'
        ),
        new Get(
            uriTemplate: '/parcours/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user"
        ),
        new Post(
            uriTemplate: '/parcours',
            security: "is_granted('ROLE_USER')",
            processor: 'App\State\CourseProcessor'
        ),
        new Patch(
            uriTemplate: '/parcours/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            processor: 'App\State\CourseProcessor'
        ),
        new Delete(
            uriTemplate: '/parcours/{id}',
            security: "is_granted('ROLE_USER') and object.getUser() == user"
        ),
    ],
    normalizationContext: ['groups' => ['course:read']],
    denormalizationContext: ['groups' => ['course:write']],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?\DateTime $createAt = null;

    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?\DateTime $placementCompletedAt = null;

    #[ORM\Column]
    #[Groups(['course:read'])]
    private ?\DateTime $updateAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['course:read', 'course:write'])]
    private bool $sameStartFinish = false;

    /**
     * @var Collection<int, Beacon>
     */
    #[ORM\ManyToMany(targetEntity: Beacon::class, mappedBy: 'course')]
    #[Groups(['course:read'])]
    private Collection $beacons;

    /**
     * @var Collection<int, Session>
     */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'course')]
    private Collection $sessions;

    #[ORM\ManyToOne(inversedBy: 'course')]
    #[Groups(['course:read'])]
    private ?User $user = null;

    public function __construct()
    {
        $this->beacons = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->sameStartFinish = false;
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
     * Get the start beacon by filtering beacons with type 'start'
     */
    public function getStartBeacon(): ?Beacon
    {
        foreach ($this->beacons as $beacon) {
            if ($beacon->getType() === 'start') {
                return $beacon;
            }
        }
        return null;
    }

    /**
     * Get the finish beacon by filtering beacons with type 'finish'
     * If sameStartFinish is true, returns the start beacon
     */
    public function getFinishBeacon(): ?Beacon
    {
        if ($this->sameStartFinish) {
            return $this->getStartBeacon();
        }
        
        foreach ($this->beacons as $beacon) {
            if ($beacon->getType() === 'finish') {
                return $beacon;
            }
        }
        return null;
    }

    public function isSameStartFinish(): bool
    {
        return $this->sameStartFinish;
    }

    public function setSameStartFinish(bool $sameStartFinish): static
    {
        $this->sameStartFinish = $sameStartFinish;
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
            $beacon->addCourse($this);
        }

        return $this;
    }

    public function removeBeacon(Beacon $beacon): static
    {
        if ($this->beacons->removeElement($beacon)) {
            $beacon->removeCourse($this);
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
            $session->setCourse($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getCourse() === $this) {
                $session->setCourse(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    //Get count of control beacons (excluding start/finish)
    public function getBeaconsCount(): int
    {
        return $this->beacons->filter(function(Beacon $beacon) {
            return $beacon->getType() === 'control';
        })->count();
    }

    // Get count of all beacons (including start/finish)
    public function getTotalBeaconsCount(): int
    {
        return $this->beacons->count();
    }
}
