<?php

namespace App\Entity;

use App\Repository\RunnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column]
    private ?\DateTime $departure = null;

    #[ORM\Column]
    private ?\DateTime $arrival = null;

    /**
     * @var Collection<int, LogSession>
     */
    #[ORM\OneToMany(targetEntity: LogSession::class, mappedBy: 'runner')]
    private Collection $logSessions;

    #[ORM\ManyToOne(inversedBy: 'runners')]
    private ?Session $session = null;

    public function __construct()
    {
        $this->logSessions = new ArrayCollection();
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

    /**
     * @return Collection<int, LogSession>
     */
    public function getLogSessions(): Collection
    {
        return $this->logSessions;
    }

    public function addLogSession(LogSession $logSession): static
    {
        if (!$this->logSessions->contains($logSession)) {
            $this->logSessions->add($logSession);
            $logSession->setRunner($this);
        }

        return $this;
    }

    public function removeLogSession(LogSession $logSession): static
    {
        if ($this->logSessions->removeElement($logSession)) {
            // set the owning side to null (unless already changed)
            if ($logSession->getRunner() === $this) {
                $logSession->setRunner(null);
            }
        }

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }
}
