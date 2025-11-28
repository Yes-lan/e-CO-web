<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionName = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $nbRunner = null;

    /**
     * @var Collection<int, Runner>
     */
    #[ORM\OneToMany(targetEntity: Runner::class, mappedBy: 'idSession')]
    private Collection $runners;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    private ?Course $idCourse = null;

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

    public function getNbRunner(): ?string
    {
        return $this->nbRunner;
    }

    public function setNbRunner(string $nbRunner): static
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
            $runner->setIdSession($this);
        }

        return $this;
    }

    public function removeRunner(Runner $runner): static
    {
        if ($this->runners->removeElement($runner)) {
            // set the owning side to null (unless already changed)
            if ($runner->getIdSession() === $this) {
                $runner->setIdSession(null);
            }
        }

        return $this;
    }

    public function getIdCourse(): ?Course
    {
        return $this->idCourse;
    }

    public function setIdCourse(?Course $idCourse): static
    {
        $this->idCourse = $idCourse;

        return $this;
    }
}
