<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\WebtaskRepository;
use App\Repository\NotificationRepository;
use App\Services\VersionService;
use App\Services\TextTransformer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;

class ConsulterTachesController extends AbstractController
{
    private $clientRepository;
    private $webTaskRepository;
    private $notificationRepository;
    private $userRepository;
    private $versionService;
    private $textTransformer;
    private $entityManager;

    public function __construct(
        ClientRepository $clientRepository,
        WebtaskRepository $webTaskRepository,
        NotificationRepository $notificationRepository,
        UserRepository $userRepository, 
        VersionService $versionService, 
        TextTransformer $textTransformer, 
        EntityManagerInterface $entityManager,
    ) {
        $this->clientRepository = $clientRepository;
        $this->webTaskRepository = $webTaskRepository;
        $this->notificationRepository = $notificationRepository;
        $this->userRepository = $userRepository;
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->entityManager = $entityManager;
    }

    #[Route('/consultertaches', name: 'app_consultertaches', methods: ['GET'])]
    public function consultertaches(Request $request, WebtaskRepository $webtaskRepository, NotificationRepository $notificationRepository): Response       
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'ID du client associé à l'utilisateur connecté
        $idclient = $user->getIdclient(); 

        // Vérifier si un client est associé à l'utilisateur
        if (!$idclient) {
            throw $this->createNotFoundException('Aucun client associé à cet utilisateur.');
        }

        $client = $this->clientRepository->find($idclient);


        // Récupérer le logo du client
        $logo = null;
        if ($idclient->getLogo()) {
            $logo = base64_encode(stream_get_contents($idclient->getLogo()));
        }

        // Récupérer les notifications visibles de l'utilisateur connecté
        $notifications = $notificationRepository->findBy([
            'user' => $user->getId(),
            'visible' => true
        ]);

        // Créer un tableau pour lier codeWebtask à id
        $idWebtaskMap = [];
        foreach ($notifications as $notification) {
            $idWebtask = $this->webTaskRepository->findIdByCodeWebtask($notification->getCodeWebtask());
            if ($idWebtask !== null) {
                $idWebtaskMap[$notification->getCodeWebtask()] = $idWebtask;
            }
        }
        
        $id = $request->query->get('id');
        $webtask = $this->webTaskRepository->findOneBy(['code' => $id]);
    
        if (!$webtask) {
            throw $this->createNotFoundException('Aucune tâche disponible');
        }

        // Récupérer le client associé à la webtask via son idclient
        $client = $this->entityManager->getRepository(Client::class)->find($webtask->getIdclient());

        $raisonSociale = $client ? $client->getRaisonSociale() : 'Client inconnu';
        
        $versionLibelle = $this->versionService->getLibelleById($webtask->getIdversion());
        $webtask->setVersionLibelle($versionLibelle);
    
        // Transformer les champs de texte
        $webtask->setDescription($this->textTransformer->transformCrToNewLine($webtask->getDescription()));
        $webtask->setCommentaireWebtaskClient($this->textTransformer->transformCrToNewLine($webtask->getCommentaireWebtaskClient()));
        $webtask->setCommentaireInternePagos($this->textTransformer->transformCrToNewLine($webtask->getCommentaireInternePagos()));
    
        $anciennesWebtasks = $this->webTaskRepository->findBy(['Webtask' => $webtask->getWebtask()]);

        usort($anciennesWebtasks, function ($a, $b) {
            // Comparaison sur le champ 'idversion' en décroissant
            $idversionComparison = strcmp($a->getIdversion(), $b->getIdversion());
        
            if ($idversionComparison === 0) {
                // Si 'idversion' est identique, on trie par 'filtre' en décroissant
                return strcmp($a->getFiltre(), $b->getFiltre());
            }
        
            return $idversionComparison;
        });

        // Récupérer la première webtask dans la liste triée
        $premiereWebtask = reset($anciennesWebtasks);

        // Collecter les liens de documents en fonction des conditions
        $documentsLiensNonExtraits = [];
        if ($user->getIdclient() && $user->getIdclient()->getRaisonSociale() === 'CABINET PAGOS') {
            // Tous les documents pour les utilisateurs du client PAGOS
            foreach ($anciennesWebtasks as $task) {
                if ($task->getLiendrive1()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive1();
                }
                if ($task->getLiendrive2()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive2();
                }
                if ($task->getLiendrive3()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive3();
                }
            }
        } else {
            // Autres utilisateurs, filtrer par Webtask avec un commentaire client renseigné
            foreach ($anciennesWebtasks as $task) {
                if ($task->getCommentaireWebtaskClient() && ($task->getLiendrive1() || $task->getLiendrive2() || $task->getLiendrive3())) {
                    if ($task->getLiendrive1()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive1();
                    }
                    if ($task->getLiendrive2()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive2();
                    }
                    if ($task->getLiendrive3()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive3();
                    }
                }
            }
        }

        // Si aucun document n'est trouvé et qu'une première webtask existe, récupérez les liens de cette première webtask
        if (empty($documentsLiensNonExtraits) && $premiereWebtask) {
            if ($premiereWebtask->getLiendrive1()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive1();
            }
            if ($premiereWebtask->getLiendrive2()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive2();
            }
            if ($premiereWebtask->getLiendrive3()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive3();
            }
        }

        // Collecter tous les liens de documents et extraire la partie après le dernier '?'
        $documentsLiens = [];
        foreach ($anciennesWebtasks as $task) {
            if ($task->getLiendrive1()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive1());
            }
            if ($task->getLiendrive2()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive2());
            }
            if ($task->getLiendrive3()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive3());
            }
        }

        // Récupérer le responsable directement
        $responsable = $webtask->getResponsable();

        if ($responsable) {
            $responsableNomPrenom = $this->formatResponsable($responsable->getPrenom(), $responsable->getNom());
        } else {
            $responsableNomPrenom = 'INCONNU';
        }

        // Récupérer le pilote par son ID
        $piloteId = $webtask->getPiloteid();
        $pilote = null;

        if ($piloteId) {
            $pilote = $this->entityManager->getRepository(User::class)->find($piloteId);
        }

        if ($pilote) {
            $piloteNomPrenom = $this->formatPilote($pilote->getPrenom(), $pilote->getNom());
        } else {
            $piloteNomPrenom = 'INCONNU';
        }

        // Transformer les champs de texte pour chaque ancienne Webtask
        $anciennesWebtasksDetails = [];
        foreach ($anciennesWebtasks as $task) {
            // Récupérer le libellé de la version pour chaque ancienne webtask
            $versionLibelleAncienne = $this->versionService->getLibelleById($task->getIdversion());
            $task->setVersionLibelle($versionLibelleAncienne);

            // Transformer les champs de texte
            $task->setDescription($this->textTransformer->transformCrToNewLine($task->getDescription()));
            $task->setCommentaireWebtaskClient($this->textTransformer->transformCrToNewLine($task->getCommentaireWebtaskClient()));
            $task->setCommentaireInternePagos($this->textTransformer->transformCrToNewLine($task->getCommentaireInternePagos()));

            // Générer les initiales basées sur le nom complet du créateur (ou "Inconnu" si non disponible)
            $formatNomPrenom = $this->formatNomPrenom($task->getCreerPar());

            // Récupérer le nom et le prénom de l'utilisateur à partir du champ 'creePar' (prénom nom)
            $creerPar = $task->getCreerPar();
            $creerParParts = explode(' ', $creerPar);

            // Trouver l'utilisateur par prénom et nom
            if (count($creerParParts) === 2) {
                $prenom = $creerParParts[0];
                $nom = $creerParParts[1];

                $user = $this->userRepository->findOneBy(['prenom' => $prenom, 'nom' => $nom]);

                // Vérifier si l'utilisateur existe
                if ($user) {
                    // Vérifier si cet utilisateur appartient à un client PAGOS
                    $idclient = $user->getIdclient();
                    $isPagosUser = ($idclient && $idclient->getRaisonSociale() === 'CABINET PAGOS');
                } else {
                    $isPagosUser = false; // Utilisateur introuvable
                }
            } else {
                $isPagosUser = false; // Si le nom et prénom ne sont pas bien formés
            }
        
            $anciennesWebtasksDetails[] = [
                'creeLe' => $task->getCreeLe(),
                'creePar' => $formatNomPrenom,
                'avancement' => $this->mapAvancementDeLaTache($task->getAvancementDeLaTache()),
                'dateFinDemandee' => $task->getDateFinDemandee(),
                'versionLibelle' => $versionLibelleAncienne,
                'baseDeDonnees' => $task->getBaseclient(),
                'commentaire_webtask_client' => $task->getCommentaireWebtaskClient(),
                'commentaire_interne_pagos' => $task->getCommentaireInternePagos(),
                'isPagosUser' => $isPagosUser,
            ];
        }
    
        // Récupération des commentaires
        $commentaires = [
            'client' => $webtask->getCommentaireWebtaskClient(),
            'interne' => $webtask->getCommentaireInternePagos(),
        ];
    
        // Initialiser les commentaires en tant que tableau
        $commentairesListe = [];
        if (!empty($commentaires['client'])) {
            $commentairesListe[] = [
                'type' => 'Client',
                'contenu' => $commentaires['client']
            ];
        }
        if (!empty($commentaires['interne'])) {
            $commentairesListe[] = [
                'type' => 'Interne',
                'contenu' => $commentaires['interne']
            ];
        }
    
        // Tri des commentaires par type (client en premier, interne ensuite)
        usort($commentairesListe, function($a, $b) {
            return strcmp($a['type'], $b['type']);
        });
    
        $mappedTag = $this->mapTag($webtask->getTag());
        $tagClass = $this->getTagClass($webtask->getTag());
        $mappedAvancement = $this->mapAvancementDeLaTache($webtask->getAvancementdelatache());
    
        return $this->render('Client/consultertaches.html.twig', [
            'webtask' => $webtask,
            'raisonSociale' => $raisonSociale,
            'mappedTag' => $mappedTag,
            'tagClass' => $tagClass,
            'mappedAvancement' => $mappedAvancement,
            'commentairesListe' => $commentairesListe,
            'anciennesWebtasksDetails' => $anciennesWebtasksDetails,
            'responsableNomPrenom' => $responsableNomPrenom,
            'piloteNomPrenom' => $piloteNomPrenom,
            'documentsLiens' => $documentsLiens,
            'documentsLiensNonExtraits' => $documentsLiensNonExtraits,
            'logo' => $logo,
            'notifications' => $notifications,
            'idWebtaskMap' => $idWebtaskMap,
        ]);
    }

    #[Route('/notifications', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        // Récupérer les notifications visibles
        $notifications = $this->notificationRepository->findVisibleNotifications();

        return $this->json([
            'count' => count($notifications), // Compte le nombre de notifications
            'notifications' => $notifications, // Renvoie les notifications
        ]);
    }

    #[Route('/mark-as-read/{id}', name: 'app_mark_as_read', methods: ['POST'])]
    public function markAsRead($id): JsonResponse
    {
        // Récupérer la notification par son ID
        $notification = $this->notificationRepository->find($id); // Utiliser le repository injecté

        if (!$notification) {
            return new JsonResponse(['status' => 'not_found'], 404);
        }

        // Mettre à jour le champ visible à 0
        $notification->setVisible(0); // Assurez-vous que vous avez une méthode pour cela

        // Enregistrer les modifications
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    // Méthode pour extraire la partie après le dernier "?" dans un lien
    private function extractAfterLastQuestionMark(string $url): string
    {
        $parts = explode('?', $url);
        return end($parts); // Renvoie la dernière partie après le dernier '?'
    }

    // Méthode pour générer le format : Initiale PRÉNOM. NOM
    private function formatNomPrenom(?string $creePar): string
    {
        if (!$creePar) {
            return 'INCONNU'; // Si aucune information n'est disponible
        }

        // Séparer les parties du nom
        $parts = explode(' ', $creePar);

        if (count($parts) === 1) {
            // Si un seul nom est donné, on retourne le nom complet en majuscules
            return strtoupper($parts[0]);
        } elseif (count($parts) > 1) {
            // Si un prénom et un nom sont donnés
            $prenom = ucfirst(strtolower($parts[0])); // Prénom avec une majuscule initiale
            $nom = strtoupper(end($parts)); // Dernière partie comme nom en majuscules
            $initialePrenom = strtoupper(substr($prenom, 0, 1)); // Initiale du prénom

            return sprintf('%s. %s', $initialePrenom, $nom);
        }

        return 'INCONNU';
    }

    // Méthode pour formater le responsable au format : Initiale PRÉNOM. NOM
    private function formatResponsable(?string $prenom, ?string $nom): string
    {
        if (!$prenom && !$nom) {
            return 'INCONNU'; // Si aucune information n'est disponible
        }

        // Initiale du prénom
        $initialePrenom = $prenom ? strtoupper(substr($prenom, 0, 1)) : '';

        // Nom en majuscules
        $nomFormatted = $nom ? strtoupper($nom) : '';

        // Concaténation du format
        return sprintf('%s. %s', $initialePrenom, $nomFormatted);
    }

    // Méthode pour formater le pilote au format : Initiale PRÉNOM. NOM
    private function formatPilote(?string $prenom, ?string $nom): string
    {
        if (!$prenom && !$nom) {
            return 'INCONNU'; // Si aucune information n'est disponible
        }

        // Initiale du prénom
        $initialePrenom = $prenom ? strtoupper(substr($prenom, 0, 1)) : '';

        // Nom en majuscules
        $nomFormatted = $nom ? strtoupper($nom) : '';

        // Concaténation du format
        return sprintf('%s. %s', $initialePrenom, $nomFormatted);
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