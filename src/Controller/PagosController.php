<?php
// src/Controller/PagosController.php

namespace App\Controller;

use App\Repository\WebtaskRepository;
use App\Services\TextTransformer;
use App\Services\VersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ClientRepository;
use App\Repository\NotificationRepository;


class PagosController extends AbstractController
{
    private $webTaskRepository;
    private $versionService;
    private $textTransformer;
    private $clientRepository;
    private $notificationRepository;

    public function __construct(
        WebtaskRepository $webTaskRepository, 
        VersionService $versionService, 
        TextTransformer $textTransformer, 
        ClientRepository $clientRepository,
        NotificationRepository $notificationRepository
    ) {
        $this->webTaskRepository = $webTaskRepository;
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->clientRepository = $clientRepository;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/taches', name: 'app_taches')]
    public function taches(Request $request, NotificationRepository $notificationRepository): Response
    {
        $query = $request->query->get('query');
        $selectedAvancement = $request->query->get('filter', 'all'); // Remplace `filter` par `selectedAvancement`
        $filterByPilote = $request->query->get('filterByPilote', '');
        $webtasks = [];

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user) {
            $idclient = $user->getIdclient();
            $webtasks = $this->webTaskRepository->findByClient($idclient);
        }

        $client = $this->clientRepository->find($idclient);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($client->getLogo()) {
            $logo = base64_encode(stream_get_contents($client->getLogo()));
        }

        // Filtrer les tâches avec un état d'avancement 'ON'
        $webtasksON = array_filter($webtasks, function($webtask) {
            return $webtask->getEtatDeLaWebtask() === 'ON';
        });

        // Extraire les pilotes avec initiales (format: Initiale. NOM)
        $pilotes = [];
        foreach ($webtasksON as $webtask) {
            $piloteId = $webtask->getPiloteid();
            if ($piloteId) {
                $piloteKey = $piloteId->getId();
                if (!isset($pilotes[$piloteKey])) {
                    $prenom = $piloteId->getPrenom();
                    $nom = $piloteId->getNom();

                    $pilotes[$piloteKey] = [
                        'prenom' => $piloteId->getPrenom(),
                        'initiale' => strtoupper(mb_substr($prenom, 0, 1)) . '.', // Convertit l'initiale en majuscule
                        'nom' => strtoupper($nom) // Convertit le nom complet en majuscule

                    ];
                }
            }
        }

        // Trier les pilotes par nom et prénom dans l'ordre croissant
        uasort($pilotes, function($a, $b) {
            // Comparer d'abord par le nom, puis par le prénom
            $nomComparison = strcmp($a['nom'], $b['nom']);
            if ($nomComparison === 0) {
                // Si les noms sont identiques, trier par prénom
                return strcmp($a['prenom'], $b['prenom']);
            }
            return $nomComparison;
        });

        // Appliquer au filtre
        if (!empty($filterByPilote)) {
            $webtasksON = array_filter($webtasksON, function ($webtask) use ($filterByPilote) {
                return $webtask->getPiloteid() && $webtask->getPiloteid()->getId() == $filterByPilote;
            });
        }

        // Appliquer le filtre par statut d'avancement si spécifié
        if ($selectedAvancement !== 'all') {
            $webtasksON = array_filter($webtasksON, function($webtask) use ($selectedAvancement) {
                switch ($selectedAvancement) {
                    case 'nonPriseEnCompte':
                        return $webtask->getAvancementDeLaTache() === '0';
                    case 'priseEnCompte':
                        return $webtask->getAvancementDeLaTache() === '1';
                    case 'terminee':
                        return $webtask->getAvancementDeLaTache() === '2';
                    case 'amelioration':
                        return $webtask->getAvancementDeLaTache() === '3'; 
                    case 'refusee':
                        return $webtask->getAvancementDeLaTache() === '4';
                    case 'validee':
                        return $webtask->getAvancementDeLaTache() === '5';
                    case 'stopClient':
                        return $webtask->getAvancementDeLaTache() === '6';
                    case 'goClient':
                        return $webtask->getAvancementDeLaTache() === '7';
                    default:
                        return true; // Renvoie toutes les tâches si le filtre ne correspond à rien
                }
            });
        }

        // Tri des tâches par date de fin demandée
        usort($webtasksON, function($a, $b) {
            $dateA = \DateTime::createFromFormat('d/m/Y', $a->getDateFinDemandee());
            $dateB = \DateTime::createFromFormat('d/m/Y', $b->getDateFinDemandee());
            return $dateA <=> $dateB;
        });

        // Préparation des données pour chaque tâche
        $webtasksON = array_map(function($webtask) {
            $idVersion = $webtask->getIdversion();

            if ($idVersion === null) {
                $webtask->versionLibelle = 'Version non disponible';
            } else {
                $webtask->versionLibelle = $this->versionService->getLibelleById($idVersion);
            }

            $webtask->setDescription($this->textTransformer->transformCrToNewLine($webtask->getDescription()));
            
            $webtask->mappedTag = $this->mapTag($webtask->getTag());
            $webtask->tagClass = $this->getTagClass($webtask->getTag());
            $webtask->mappedAvancement = $this->mapAvancementDeLaTache($webtask->getAvancementdelatache());

            return $webtask;
        }, $webtasksON);

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

        return $this->render('Client/taches.html.twig', [
            'webtasks' => $webtasksON, // Renvoie les webtasks filtrés
            'query' => $query,
            'selectedAvancement' => $selectedAvancement, // Passe `selectedAvancement` au template
            'pilotes' => $pilotes,
            'filterByPilote' => $filterByPilote,
            'logo' => $logo,
            'notifications' => $notifications,
            'idWebtaskMap' => $idWebtaskMap,
        ]);
    }

    #[Route('/notifications', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return $this->json([
                'count' => 0,
                'notifications' => [],
                'message' => 'Utilisateur non connecté',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer l'ID de l'utilisateur
        $userId = $user->getId();

        // Récupérer les notifications visibles pour l'utilisateur connecté
        $notifications = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.visible = :visible')
            ->andWhere('n.user = :userId')
            ->setParameter('visible', true)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return $this->json([
            'count' => count($notifications),
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-as-read/{id}', name: 'app_mark_as_read', methods: ['POST'])]
    public function markAsRead($id): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['status' => 'unauthorized'], 401);
        }

        // Récupérer la notification par son ID
        $notification = $this->notificationRepository->find($id);

        if (!$notification) {
            return new JsonResponse(['status' => 'not_found'], 404);
        }

        // Vérifier que la notification appartient à l'utilisateur connecté
        if ($notification->getUser() !== $user->getId()) {
            return new JsonResponse(['status' => 'forbidden'], 403);
        }

        // Mettre à jour le champ visible à 0
        $notification->setVisible(false); // Assurez-vous que cette méthode existe dans l'entité Notification

        // Enregistrer les modifications
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    private function mapTag(?int $tag): string
    {
        $tags = [
            0 => '(1) Mineur',
            1 => '(2) Grave',
            2 => '(3) Bloquant'
        ];

        return $tags[$tag] ?? 'Inconnu';
    }

    private function getTagClass(?int $tag): string
    {
        $classes = [
            0 => 'tag-minor',
            1 => 'tag-serious',
            2 => 'tag-blocking'
        ];

        return $classes[$tag] ?? 'tag-unknown';
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

        return $avancements[$avancement] ?? ['label' => 'Inconnu', 'class' => 'text-muted'];
    }
}
