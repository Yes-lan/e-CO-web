<?php

namespace App\Entity;

use App\Repository\BoundariesCourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoundariesCourseRepository::class)]
class BoundariesCourse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $latitude = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'boundariesCourses')]
    private Collection $idCourse;

    public function __construct()
    {
        $this->idCourse = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
