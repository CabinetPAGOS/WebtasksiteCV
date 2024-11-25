<?php

namespace App\Controller;

use App\Entity\Webtask;
use App\Entity\Notification;
use App\Services\TextTransformer;
use App\Services\VersionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ClientRepository;


class ReponseController extends AbstractController
{
    private $textTransformer;
    private $versionService;
    private $clientRepository;


    public function __construct(TextTransformer $textTransformer, VersionService $versionService, ClientRepository $clientRepository)
    {
        $this->textTransformer = $textTransformer;
        $this->versionService = $versionService;
        $this->clientRepository = $clientRepository;

    }

    #[Route('/reponsetaches/{id}', name: 'app_reponsetaches')]
    public function Reponse($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifiez si $id est bien un entier
        if (!is_numeric($id) || intval($id) <= 0) {
            throw $this->createNotFoundException('L\'ID de la tâche n\'est pas valide');
        }

        // Convertir $id en entier
        $id = (int) $id;

        // Récupérer la tâche depuis la base de données
        $tache = $entityManager->getRepository(Webtask::class)->find($id);

        // Si la tâche n'existe pas, renvoyer une erreur 404
        if (!$tache) {
            throw $this->createNotFoundException('La tâche n\'existe pas');
        }

        $client = $tache->getIdclient();
        $googleDriveLink = $client ? $client->getGoogleDriveWebtask() : null;

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($client->getLogo()) {
            $logo = base64_encode(stream_get_contents($client->getLogo()));
        }
        
        if ($request->isMethod('POST')) {
            // Créer une nouvelle tâche
            $newTache = new Webtask();

            // Générer le code automatique pour les tâches commençant par 'SWT-'
            $lastTaskWithSWTCode = $entityManager->getRepository(Webtask::class)->createQueryBuilder('w')
            ->where('w.code LIKE :prefix')
            ->setParameter('prefix', 'SWT-%')
            ->orderBy('w.code', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

            if ($lastTaskWithSWTCode) {
                // On extrait le numéro à partir de 'SWT-' et on l'incrémente
                $newCodeNumber = (int) substr($lastTaskWithSWTCode->getCode(), 6) + 1;
            } else {
                // Si aucune tâche avec un code 'SWT-' n'existe, on commence à 1
                $newCodeNumber = 1;
            }
            $newTache->setCode(sprintf('SWT-%06d', $newCodeNumber));

            for ($i = 1; $i <= 3; $i++) {
                $fileLink = $request->request->get("fileLink{$i}", "");   // lien de fichier
                $fileTitle = $request->request->get("fileTitle{$i}", ""); // titre de fichier
            
                // Enregistrer uniquement le titre si le lien est vide, sinon les deux
                $liendrive = !empty($fileTitle) ? "{$fileLink}?" . urlencode($fileTitle) : "";
                
                // Si seulement le titre est fourni sans lien
                if (empty($fileLink) && !empty($fileTitle)) {
                    $liendrive = urlencode($fileTitle);
                }
            
                // Appel dynamique du setter
                $setter = "setLienDrive{$i}";
                $newTache->$setter($liendrive);
            }

            $areDocumentsAttaches = !empty($documentsAttaches[0]) || !empty($documentsAttaches[1]) || !empty($documentsAttaches[2]);
            $newTache->setDocumentsAttaches($areDocumentsAttaches);

            // Convertir la date en chaîne
            $dueDate = $request->request->get('due_date');
            $newTache->setDateFinDemandee($dueDate);

            // Date et Heure de création et Créateur
            $currentDateTime = new \DateTime();
            $newTache->setCreeLe($currentDateTime->format('d/m/Y - H:i:s'));
            $newTache->setCreerPar($user->getPrenom() . ' ' . $user->getNom());

            // Copie des autres champs
            $newTache->setEtatDeLaWebtask('ON'); // Le nouvel état
            $tache->setEtatDeLaWebtask('OFF'); // L'ancien état devient OFF
            $newTache->setLibelle($tache->getLibelle());
            $newTache->setIdclient($tache->getIdclient());
            $newTache->setTitre($tache->getTitre() ?? '');
            $newTache->setWebtask($tache->getWebtask());
            $newTache->setDescription($tache->getDescription());
            $newTache->setEntite($tache->getEntite());
            $newTache->setTag($tache->getTag());
            $newTache->setIddemandeur($tache->getIddemandeur());
            $newTache->setResponsable($tache->getResponsable());
            $newTache->setPiloteid($tache->getPiloteid());
            $newTache->setEstimationTemps($tache->getEstimationTemps());
            // Date de fin demandée enregistrée dans le code jute au dessus
            $newTache->setAvancementDeLaTache($tache->getAvancementDeLaTache());
            $newTache->setDemandeDeRecettage($tache->getDemandeDeRecettage());
            $newTache->setCommentaireWebtaskClient($request->request->get('nouveau_commentaire'));
            // Pas de commentaire interne PAGOS
            // Documents attachés s'enregistre dans le code au-dessus
            // État de la webtask se met à jour à la première ligne de ce bloc
            $newTache->setArchiver($tache->getArchiver());
            $newTache->setOrdre($tache->getOrdre());
            $newTache->setOrdonnele($tache->getOrdonnele());
            $newTache->setRecommandations($tache->getRecommandations());
            $newTache->setIdversion($tache->getIdversion());
            $newTache->setetatVersion($tache->getetatVersion());
            $newTache->setIdtracabilite($tache->getIdtracabilite());
            $newTache->setWebtaskMere($tache->getWebtaskMere());
            $newTache->setBaseclient($tache->getBaseclient());
            $newTache->setsylob5('0');
            $newTache->setNomDocExport('');

            // Le filtre s'enregistre juste en-dessous

            // Récupérer le libellé de la version
            $idVersion = $tache->getIdversion();
            $versionLibelle = $idVersion !== null ? $this->versionService->getLibelleById($idVersion) : 'Version non disponible';

            // Générer le filtre
            $currentDateTime = new \DateTime();
            $filter = sprintf(
                '%s_%s_%s',
                $currentDateTime->format('Ymd'),
                $currentDateTime->format('H:i'),
                $versionLibelle
            );
            $newTache->setFiltre($filter); // Assurez-vous que la méthode setFiltre existe dans l'entité Webtask

            // Persister les entités
            $entityManager->persist($newTache);
            $entityManager->flush();

            // Création de la notification
            $notification = new Notification();
            $notification->setMessage('Réponse à la WebTask : ' . $newTache->getLibelle());
            $notification->setLibelleWebtask($newTache->getLibelle());
            $notification->setDateCreation(new \DateTime()); // Date et heure courante
            $notification->setVisible(true); // 1 pour visible
            $notification->setClient($newTache->getIdclient()); // ID du client
            $notification->setTitreWebtask($newTache->getTitre()); // Titre de la WebTask
            $notification->setCodeWebtask($newTache->getCode());

            // Persist notification
            $entityManager->persist($notification);
            $entityManager->flush(); // Sauvegarder la notification en base de données

            return $this->redirectToRoute('app_taches');
        }

        // Ajouter le libellé de la version
        $idVersion = $tache->getIdversion();
        $versionLibelle = $idVersion !== null ? $this->versionService->getLibelleById($idVersion) : 'Version non disponible';
        $tache->versionLibelle = $versionLibelle;

        // Transformer la description
        $tache->setDescription($this->textTransformer->transformCrToNewLine($tache->getDescription()));

        // Mapper les valeurs
        $mappedTag = $this->mapTag($tache->getTag());
        $tagClass = $this->getTagClass($tache->getTag());
        $mappedAvancement = $this->mapAvancementDeLaTache($tache->getAvancementdelatache());

        return $this->render('Client/reponsetaches.html.twig', [
            'webtask' => $tache,
            'mappedTag' => $mappedTag,
            'tagClass' => $tagClass,
            'mappedAvancement' => $mappedAvancement,
            'googleDriveLink' => $googleDriveLink,
            'logo' => $logo,

        ]);
    }

    private function mapTag(?int $tag): string
    {
        $tags = [
            0 => '(1) Mineur',
            1 => '(2) Grave',
            2 => '(3) Bloquant'
        ];

        return isset($tags[$tag]) ? $tags[$tag] : 'Inconnu';
    }

    private function getTagClass(?int $tag): string
    {
        $classes = [
            0 => 'tag-minor',
            1 => 'tag-serious',
            2 => 'tag-blocking'
        ];

        return isset($classes[$tag]) ? $classes[$tag] : 'tag-unknown';
    }

    private function mapAvancementDeLaTache(?int $avancement): array
    {
        $avancements = [
            0 => ['label' => 'Non Prise en Compte', 'class' => 'text-npc'],
            1 => ['label' => 'Prise en Compte', 'class' => 'text-pc'],
            2 => ['label' => 'Terminée', 'class' => 'text-t'],
            3 => ['label' => '❇️ Amélioration ❇️', 'class' => 'text-a'],
            4 => ['label' => '⛔️ Refusée ⛔️', 'class' => 'text-r'],
            5 => ['label' => '✅ Validée', 'class' => 'text-v'],
            6 => ['label' => '❌ Stop Client ❌', 'class' => 'text-sc'],
            7 => ['label' => '😃 Go Client 😃', 'class' => 'text-gc']
        ];

        return isset($avancements[$avancement]) ? $avancements[$avancement] : ['label' => 'Inconnu', 'class' => 'text-muted'];
    }
}