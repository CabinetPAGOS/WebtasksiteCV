<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $id = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $raison_sociale = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $google_drive_webtask = null;


    #[ORM\Column(nullable: true)]
    private ?bool $webtaskOuvertureContact = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $logo = null;

    // Relation OneToMany vers Forum (résumés de réunion)
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Forum::class, cascade: ['persist', 'remove'])]
    private Collection $forums;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'client')]
    private Collection $webtasks;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'idclient')]
    private Collection $users;

    public function __construct()
    {
        $this->webtasks = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->forums = new ArrayCollection(); // Initialisation de la collection forums

    }

    // Gestion de la relation avec Forum
    /**
     * @return Collection<int, Forum>
     */
    public function getForums(): Collection
    {
        return $this->forums;
    }


    public function addForum(Forum $forum): self
    {
        if (!$this->forums->contains($forum)) {
            $this->forums->add($forum);
            $forum->setClient($this);
        }

        return $this;
    }
    
    public function removeForum(Forum $forum): self
    {
        if ($this->forums->removeElement($forum)) {
            if ($forum->getClient() === $this) {
                $forum->setClient(null);
            }
        }

        return $this;
    }


    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->raison_sociale;
    }

    public function setRaisonSociale(string $raison_sociale): static
    {
        $this->raison_sociale = $raison_sociale;

        return $this;
    }


    public function getGoogleDriveWebtask(): ?string
    {
        return $this->google_drive_webtask;
    }

    public function setGoogleDriveWebtask(string $google_drive_webtask): static
    {
        $this->google_drive_webtask = $google_drive_webtask;

        return $this;
    }

    
    

    

    public function iswebtaskOuvertureContact(): ?bool
    {
        return $this->webtaskOuvertureContact;
    }

    public function setwebtaskOuvertureContact(?bool $webtaskOuvertureContact): static
    {
        $this->webtaskOuvertureContact = $webtaskOuvertureContact;

        return $this;
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function setLogo($logo): static
    {
        $this->logo = $logo;

        return $this;
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
            $webtask->setIdclient($this);
        }

        return $this;
    }

    public function removeWebtask(Webtask $webtask): static
    {
        if ($this->webtasks->removeElement($webtask)) {
            // set the owning side to null (unless already changed)
            if ($webtask->getIdclient() === $this) {
                $webtask->setIdclient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setIdclient($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getIdclient() === $this) {
                $user->setIdclient(null);
            }
        }

        return $this;
    }

}
