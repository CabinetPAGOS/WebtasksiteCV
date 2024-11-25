<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $visible = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelleWebtask = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)] // champ titreWebtask
    private ?string $titreWebtask = null;

    #[ORM\Column(length: 255, nullable: true)] // Ajoutez cette ligne pour codeWebtask
    private ?string $codeWebtask = null; // Ajoutez cette ligne

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getLibelleWebtask(): ?string
    {
        return $this->libelleWebtask;
    }

    public function setLibelleWebtask(string $libelleWebtask): self
    {
        $this->libelleWebtask = $libelleWebtask;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getTitreWebtask(): ?string // Getter pour titreWebtask
    {
        return $this->titreWebtask;
    }

    public function setTitreWebtask(?string $titreWebtask): self // Setter pour titreWebtask
    {
        $this->titreWebtask = $titreWebtask;

        return $this;
    }

    public function getCodeWebtask(): ?string // Ajoutez ce getter
    {
        return $this->codeWebtask;
    }

    public function setCodeWebtask(?string $codeWebtask): self // Ajoutez ce setter
    {
        $this->codeWebtask = $codeWebtask;

        return $this;
    }
}
