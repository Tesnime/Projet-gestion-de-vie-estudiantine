<?php

namespace App\Entity;

use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
class Favoris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $id_cours = null;

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
}
