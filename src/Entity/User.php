<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', columns: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var array<string> The user roles
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $webtaskOuvertureContact = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?bool $depart_entreprise = null;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'ledemandeur')]
    private Collection $webtasks;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'Piloteid')]
    private Collection $webtaskpilote;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Client $idclient = null;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'iddemandeur')]
    private Collection $demandeur;

    /**
     * @var Collection<int, Webtask>
     */
    #[ORM\OneToMany(targetEntity: Webtask::class, mappedBy: 'Piloteid')]
    private Collection $pilote;

    #[ORM\Column(nullable: true)]
    private ?bool $mustResetPassword = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $roleWX = 'createur'; // valeur par défaut "lecteur"

    public function __construct()
    {
        $this->webtaskpilote = new ArrayCollection();
        $this->demandeur = new ArrayCollection();
        $this->pilote = new ArrayCollection();
    }

    public function getroleWX(): ?string
    {
        return $this->roleWX;
    }

    public function setroleWX(string $roleWX): self
    {
        if (!in_array($roleWX, ['lecteur', 'createur'])) {
            throw new \InvalidArgumentException("Le rôle utilisateur doit être 'lecteur' ou 'createur'.");
        }
        $this->roleWX = $roleWX;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }
  
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getWebtaskOuvertureContact(): int
    {
        return $this->webtaskOuvertureContact;
    }

    public function setWebtaskOuvertureContact(int $value): self
    {
        $this->webtaskOuvertureContact = $value;
        return $this; // Permet le chaînage
    }

    public function getDepartEntreprise(): ?string
    {
        return $this->depart_entreprise;
    }

    public function setDepartEntreprise(?string $depart_entreprise): static
    {
        $this->depart_entreprise = $depart_entreprise;

        return $this;
    }

    public function getIdclient(): ?Client
    {
        return $this->idclient;
    }

    public function setIdclient(?Client $idclient): static
    {
        $this->idclient = $idclient;

        return $this;
    }

    /**
     * @return Collection<int, Webtask>
     */
    public function getDemandeur(): Collection
    {
        return $this->demandeur;
    }

    public function addDemandeur(Webtask $demandeur): static
    {
        if (!$this->demandeur->contains($demandeur)) {
            $this->demandeur->add($demandeur);
            $demandeur->setIddemandeur($this);
        }

        return $this;
    }

    public function removeDemandeur(Webtask $demandeur): static
    {
        if ($this->demandeur->removeElement($demandeur)) {
            // set the owning side to null (unless already changed)
            if ($demandeur->getIddemandeur() === $this) {
                $demandeur->setIddemandeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Webtask>
     */
    public function getPilote(): Collection
    {
        return $this->pilote;
    }

    public function addPilote(Webtask $pilote): static
    {
        if (!$this->pilote->contains($pilote)) {
            $this->pilote->add($pilote);
            $pilote->setPiloteid($this);
        }

        return $this;
    }

    public function removePilote(Webtask $pilote): static
    {
        if ($this->pilote->removeElement($pilote)) {
            // set the owning side to null (unless already changed)
            if ($pilote->getPiloteid() === $this) {
                $pilote->setPiloteid(null);
            }
        }

        return $this;
    }

    public function isMustResetPassword(): ?bool
    {
        return $this->mustResetPassword;
    }

    public function setMustResetPassword(?bool $mustResetPassword): static
    {
        $this->mustResetPassword = $mustResetPassword;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }
}