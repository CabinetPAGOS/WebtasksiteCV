<?php

namespace App\Entity;

use App\Repository\ResponsableRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableRepository::class)]
class Responsable
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $id = null;


    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'responsable')]
    private Collection $webtask;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'responsable')]
    private Collection $webtasks;

    public function __construct()
    {
        $this->webtask = new ArrayCollection();
        $this->webtasks = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }


    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * @return Collection<int, Webtask>
     */
    public function getWebtask(): Collection
    {
        return $this->webtask;
    }

    /**
     * @return Collection<int, Webtask>
     */
    public function getWebtasks(): Collection
    {
        return $this->webtasks;
    }

    public function addWebtask(Webtask $webtask): static
    {
        if (!$this->webtasks->contains($webtask)) {
            $this->webtasks->add($webtask);
            $webtask->setResponsable($this);
        }

        return $this;
    }

    public function removeWebtask(Webtask $webtask): static
    {
        if ($this->webtasks->removeElement($webtask)) {
            // set the owning side to null (unless already changed)
            if ($webtask->getResponsable() === $this) {
                $webtask->setResponsable(null);
            }
        }

        return $this;
    }

    
}
