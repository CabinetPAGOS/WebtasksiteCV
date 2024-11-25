<?php

namespace App\Entity;

use App\Repository\TracabiliteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TracabiliteRepository::class)]
class Tracabilite
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $id_tracabilité = null;

    #[ORM\Column(length: 255)]
    private ?string $mot_cle = null;



    

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdTracabilité(): ?string
    {
        return $this->id_tracabilité;
    }

    public function setIdTracabilité(string $id_tracabilité): static
    {
        $this->id_tracabilité = $id_tracabilité;

        return $this;
    }

    public function getMotCle(): ?string
    {
        return $this->mot_cle;
    }

    public function setMotCle(string $mot_cle): static
    {
        $this->mot_cle = $mot_cle;

        return $this;
    }

   

   

}
