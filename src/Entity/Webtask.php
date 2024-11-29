<?php

namespace App\Entity;

use App\Repository\WebtaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebtaskRepository::class)]
class Webtask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 2500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tag = null; // Type string pour tag

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $date_fin_demandee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avancement_de_la_tache = null;

    #[ORM\Column(length: 2500, nullable: true)]
    private ?string $commentaire_webtask_client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etat_de_la_webtask = null;

    #[ORM\Column(type: 'boolean',)]
    private bool $documents_attaches = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $sylob5 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lien_drive_1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lien_drive_2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lien_drive_3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filtre = null;

    #[ORM\ManyToOne(inversedBy: 'webtasks')]
    private ?Client $idclient = null;

    #[ORM\ManyToOne(inversedBy: 'webtasks')]
    private ?Responsable $responsable = null;

    #[ORM\Column(length: 20, nullable: false)]
    private ?string $Webtask = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entite = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $estimation_temps = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $demande_de_recettage = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $ordonnele = '';

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $archiver = null;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $ordre = null;

    #[ORM\Column(length: 2500, nullable: true)]
    private ?string $recommandations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webtask_mere = null;

    #[ORM\Column(length: 2500, nullable: true)]
    private ?string $commentaireinternepagos = null;
    
    #[ORM\ManyToOne(inversedBy: 'demandeur')]
    private ?User $iddemandeur = null;

    #[ORM\ManyToOne(inversedBy: 'pilote')]
    private ?User $Piloteid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $baseclient = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idtracabilite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idversion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatVersion	 = null;
    
    public function __construct()
    {
        // Initialiser les champs non obligatoires à des valeurs vides par défaut
        $this->titre = '';
        $this->description = '';
        $this->tag = '';
        $this->date_fin_demandee = '';
        $this->avancement_de_la_tache = '';
        $this->commentaire_webtask_client = '';
        $this->etat_de_la_webtask = '';
        $this->documents_attaches = '';
        $this->lien_drive_1 = '';
        $this->lien_drive_2 = '';
        $this->lien_drive_3 = '';
        $this->filtre = '';
        $this->entite = '';
        $this->estimation_temps = '';
        $this->demande_de_recettage = '0';
        $this->archiver = '0';
        $this->ordre = '';
        $this->recommandations = '';
        $this->webtask_mere = '';
        $this->commentaireinternepagos = '';
        $this->baseclient = '';
        $this->sylob5 = '';
        $this->idtracabilite = '';
        $this->idversion = '';
        $this->etatVersion = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
{
    $this->titre = strtoupper($titre);
    return $this;
}

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function gettag(): ?string // Modifier le type de retour à string
    {
        return $this->tag;
    }

    public function settag(?string $tag): self // Modifier le type de paramètre à string
    {
        $this->tag = $tag;
        return $this;
    }

    public function getDateFinDemandee(): ?string
    {
        return $this->date_fin_demandee;
    }

    public function setDateFinDemandee(?string $date_fin_demandee): static
    {
        $this->date_fin_demandee = $date_fin_demandee;

        return $this;
    }

    public function getAvancementDeLaTache(): ?string
    {
        return $this->avancement_de_la_tache;
    }

    public function setAvancementDeLaTache(?string $avancement_de_la_tache): static
    {
        $this->avancement_de_la_tache = $avancement_de_la_tache;

        return $this;
    }

    public function getCommentaireWebtaskClient(): ?string
    {
        return $this->commentaire_webtask_client;
    }

    public function setCommentaireWebtaskClient(?string $commentaire_webtask_client): static
    {
        $this->commentaire_webtask_client = $commentaire_webtask_client;

        return $this;
    }
 
    public function getEtatDeLaWebtask(): ?string
    {
        return $this->etat_de_la_webtask;
    }

    public function setEtatDeLaWebtask(?string $etat_de_la_webtask): static
    {
        $this->etat_de_la_webtask = $etat_de_la_webtask;

        return $this;
    }

    public function getDocumentsAttaches(): bool
    {
        return $this->documents_attaches;
    }
    
    public function setDocumentsAttaches(bool $documents_attaches): static
    {
        $this->documents_attaches = $documents_attaches;
    
        return $this;
    }

    public function getLienDrive1(): ?string
    {
        return $this->lien_drive_1;
    }

    public function setLienDrive1(?string $lien_drive_1): static
    {
        $this->lien_drive_1 = $lien_drive_1;

        return $this;
    }

    public function getLienDrive2(): ?string
    {
        return $this->lien_drive_2;
    }

    public function setLienDrive2(?string $lien_drive_2): static
    {
        $this->lien_drive_2 = $lien_drive_2;

        return $this;
    }

    public function getLienDrive3(): ?string
    {
        return $this->lien_drive_3;
    }

    public function setLienDrive3(?string $lien_drive_3): static
    {
        $this->lien_drive_3 = $lien_drive_3;

        return $this;
    }

    public function getFiltre(): ?string
    {
        return $this->filtre;
    }

    public function setFiltre(?string $filtre): static
    {
        $this->filtre = $filtre;

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

    public function getResponsable(): ?Responsable
    {
        return $this->responsable;
    }

    public function setResponsable(?Responsable $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getWebtask(): ?string
    {
        return $this->Webtask;
    }

    public function setWebtask(?string $webtask): static
    {
        $this->Webtask = $webtask;

        return $this;
    }

    public function getEntite(): ?string
    {
        return $this->entite;
    }

    public function setEntite(?string $entite): static
    {
        $this->entite = $entite;

        return $this;
    }

    public function getEstimationTemps(): ?string
    {
        return $this->estimation_temps;
    }

    public function setEstimationTemps(?string $string): static
    {
        $this->estimation_temps = $string;

        return $this;
    }

    public function getDemandeDeRecettage(): ?string
    {
        return $this->demande_de_recettage;
    }

    public function setDemandeDeRecettage(?string $demande_de_recettage): static
    {
        $this->demande_de_recettage = $demande_de_recettage;

        return $this;
    }

    public function setOrdonnele(?string $ordonnele): self
    {
        $this->ordonnele = $ordonnele;
        return $this;
    }

    public function getOrdonnele(): ?string
    {
        return $this->ordonnele;
    }

    public function getArchiver(): ?string
    {
        return $this->archiver;
    }

    public function setArchiver(?string $archiver): static
    {
        $this->archiver = $archiver;

        return $this;
    }

    public function getOrdre(): ?string
    {
        return $this->ordre;
    }

    public function setOrdre(?string $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getRecommandations(): ?string
    {
        return $this->recommandations;
    }

    public function setRecommandations(?string $recommandations): static
    {
        $this->recommandations = $recommandations;

        return $this;
    }

    public function getWebtaskMere(): ?string
    {
        return $this->webtask_mere;
    }

    public function setWebtaskMere(?string $webtask_mere): static
    {
        $this->webtask_mere = $webtask_mere;

        return $this;
    }

    public function getCommentaireinternepagos(): ?string
    {
        return $this->commentaireinternepagos;  
    }

    public function setCommentaireinternepagos(?string $commentaireinternepagos): static
    {
        $this->commentaireinternepagos = $commentaireinternepagos;

        return $this;
    }

    public function getIddemandeur(): ?user
    {
        return $this->iddemandeur;
    }

    public function setIddemandeur(?user $iddemandeur): static
    {
        $this->iddemandeur = $iddemandeur;

        return $this;
    }

    public function getPiloteid(): ?user
    {
        return $this->Piloteid;
    }

    public function setPiloteid(?user $Piloteid): static
    {
        $this->Piloteid = $Piloteid;

        return $this;
    }

    public function getBaseclient(): ?string
    {
        return $this->baseclient;
    }

    public function setBaseclient(?string $baseclient): static
    {
        $this->baseclient = $baseclient;

        return $this;
    }

    public function getsylob5(): ?bool
    {
        return $this->sylob5;
    }

    public function setsylob5(?bool $sylob5): static
    {
        $this->sylob5 = $sylob5;

        return $this;
    }

    public function getIdtracabilite(): ?string
    {
        return $this->idtracabilite;
    }

    public function setIdtracabilite(?string $idtracabilite): static
    {
        $this->idtracabilite = $idtracabilite;

        return $this;
    }

    public function getIdversion(): ?string
    {
        return $this->idversion;
    }

    public function setIdversion(?string $idversion): static
    {
        $this->idversion = $idversion;

        return $this;
    }
    public $versionLibelle;

    #[ORM\Column(length: 255)]
    private ?string $Cree_Le = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Creer_Par = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomDocExport = null;
    public function getVersionLibelle() {
        return $this->versionLibelle;
    }
    
    public function setVersionLibelle($libelle) {
        $this->versionLibelle = $libelle;
    }

    public function getetatVersion	(): ?string
    {
        return $this->etatVersion;
    }

    public function setetatVersion	(?string $etatVersion	): static
    {
        $this->etatVersion	 = $etatVersion	;

        return $this;
    }

    public function getCreeLe(): ?string
    {
        return $this->Cree_Le;
    }

    public function setCreeLe(string $Cree_Le): static
    {
        $this->Cree_Le = $Cree_Le;

        return $this;
    }

    public function getCreerPar(): ?string
    {
        return $this->Creer_Par;
    }

    public function setCreerPar(?string $Creer_Par): static
    {
        $this->Creer_Par = $Creer_Par;

        return $this;
    }

    public function getNomDocExport(): ?string
    {
        return $this->nomDocExport;
    }

    public function setNomDocExport(?string $nomDocExport): static
    {
        $this->nomDocExport = $nomDocExport;

        return $this;
    }
}