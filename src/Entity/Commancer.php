<?php

namespace App\Entity;

use App\Repository\CommancerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommancerRepository::class)]
class Commancer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $id_cours = null;

    #[ORM\Column]
    private ?int $progres = null;

    #[ORM\Column]
    private ?int $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCours(): ?int
    {
        return $this->id_cours;
    }

    public function setIdCours(int $id_cours): self
    {
        $this->id_cours = $id_cours;

        return $this;
    }

    public function getProgres(): ?int
    {
        return $this->progres;
    }

    public function setProgres(int $progres): self
    {
        $this->progres = $progres;

        return $this;
    }

    public function getUser(): ?int
    {
        return $this->user;
    }

    public function setUser(int $user): self
    {
        $this->user = $user;

        return $this;
    }
}
