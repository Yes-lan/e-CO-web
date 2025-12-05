<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionName = null;

    #[ORM\Column]
    private ?int $nbRunner = null;

    /**
     * @var Collection<int, Runner>
     */
    #[ORM\OneToMany(targetEntity: Runner::class, mappedBy: 'session')]
    private Collection $runners;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    private ?Course $course = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sessionStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sessionEnd = null;

    public function __construct()
    {
        $this->runners = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionName(): ?string
    {
        return $this->sessionName;
    }

    public function setSessionName(string $sessionName): static
    {
        $this->sessionName = $sessionName;

        return $this;
    }

    public function getNbRunner(): ?int
    {
        return $this->nbRunner;
    }

    public function setNbRunner(int $nbRunner): static
    {
        $this->nbRunner = $nbRunner;

        return $this;
    }

    /**
     * @return Collection<int, Runner>
     */
    public function getRunners(): Collection
    {
        return $this->runners;
    }

    public function addRunner(Runner $runner): static
    {
        if (!$this->runners->contains($runner)) {
            $this->runners->add($runner);
            $runner->setSession($this);
        }

        return $this;
    }

    public function removeRunner(Runner $runner): static
    {
        if ($this->runners->removeElement($runner)) {
            // set the owning side to null (unless already changed)
            if ($runner->getSession() === $this) {
                $runner->setSession(null);
            }
        }

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getSessionStart(): ?\DateTimeImmutable
    {
        return $this->sessionStart;
    }

    public function setSessionStart(?\DateTimeImmutable $sessionStart): static
    {
        $this->sessionStart = $sessionStart;

        return $this;
    }

    public function getSessionEnd(): ?\DateTimeImmutable
    {
        return $this->sessionEnd;
    }

    public function setSessionEnd(?\DateTimeImmutable $sessionEnd): static
    {
        $this->sessionEnd = $sessionEnd;

        return $this;
    }
}
