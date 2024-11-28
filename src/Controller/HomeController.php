<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Forum;
use App\Repository\ClientRepository;
use App\Repository\ForumRepository;
use App\Repository\NotificationRepository;
use App\Repository\SettingsRepository;
use App\Repository\WebtaskRepository;
use App\Services\TextTransformer;
use App\Services\VersionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $webTaskRepository;
    private $versionService;
    private $textTransformer;
    private $entityManager;
    private $notificationRepository;
    private $settingsRepository;
    private $clientRepository;
    private $forumRepository;

    public function __construct(
        WebtaskRepository $webTaskRepository,
        VersionService $versionService,
        TextTransformer $textTransformer,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        SettingsRepository $settingsRepository,
        ForumRepository $forumRepository,
        ClientRepository $clientRepository
    ) {
        $this->webTaskRepository = $webTaskRepository;
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->settingsRepository = $settingsRepository;
        $this->forumRepository = $forumRepository;
        $this->clientRepository = $clientRepository;
    }

    #[Route('/home', name: 'app_homeclient')]
    public function base(WebtaskRepository $webtaskRepository, SessionInterface $session, NotificationRepository $notificationRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les webtasks de l'utilisateur connecté
        $webtasks = $webtaskRepository->findBy(['Piloteid' => $user]);

        // Vérifier si l'utilisateur est le pilote de l'une des webtasks
        $isPilote = count($webtasks) > 0;

        // Récupérer l'ID du client associé à l'utilisateur connecté
        $idclient = $user->getIdclient();

        // Vérifier si un client est associé à l'utilisateur
        if (!$idclient) {
            throw $this->createNotFoundException('Aucun client associé à cet utilisateur.');
        }

        // Récupérer le client à partir de l'ID
        $client = $this->clientRepository->find($idclient);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($client->getLogo()) {
            $logo = base64_encode(stream_get_contents($client->getLogo()));
        }
        
        // Récupérer les Webtasks associées à cet ID client
        $webtasks = $this->webTaskRepository->findBy(['idclient' => $idclient]);

        // Convertir les dates en objets DateTime pour le tri
        usort($webtasks, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('d/m/Y', $a->getDateFinDemandee());
            $dateB = \DateTime::createFromFormat('d/m/Y', $b->getDateFinDemandee());
            return $dateA <=> $dateB;
        });

        // Mapper les tags et l'avancement des tâches
        $webtasks = array_map(function ($webtask) {
            $tagValue = $webtask->getTag();
            $mappedTag = $this->mapTag($tagValue);
            $webtask->setTag($mappedTag);

            $avancementValue = $webtask->getAvancementDeLaTache();
            $mappedAvancement = $this->mapAvancementDeLaTache($avancementValue);
            $webtask->setAvancementDeLaTache($mappedAvancement);

            $idVersion = $webtask->getIdversion();
            if ($idVersion === null) {
                $webtask->versionLibelle = 'Version non disponible';
            } else {
                $webtask->versionLibelle = $this->versionService->getLibelleById($idVersion);
            }

            $webtask->setDescription($this->textTransformer->transformCrToNewLine($webtask->getDescription()));

            return $webtask;
        }, $webtasks);

        // Initialiser les statistiques
        $nonPrisesEnCompte = 0;
        $priseEnCompte = 0;
        $terminee = 0;
        $refusee = 0;
        $validee = 0;
        $stopClient = 0;
        $totalTaches = 0;

        $totalPriseEnCompteEtAmelioration = 0;

        foreach ($webtasks as $webtask) {
            $etatWebtask = $webtask->getEtatDeLaWebtask();

            if ($etatWebtask == 'ON') {
                $totalTaches++;
                $avancement = $webtask->getAvancementDeLaTache();
                switch ($avancement) {
                    case 'Non Prise en Compte':
                        $nonPrisesEnCompte++;
                        break;

                    case 'Prise en Compte':
                        $priseEnCompte++;
                        $totalPriseEnCompteEtAmelioration++;  // Ajouter "Prise en Compte" au total
                        break;

                    case 'Terminée':
                        $terminee++;
                        break;

                    case '⛔️ Refusée ⛔':
                        $refusee++;
                        break;

                    case '✅ Validée':
                        $validee++;
                        break;

                    case '❌ Stop Client ❌':
                        $stopClient++;
                        break;

                    case '❇️ Amélioration ❇️':  // Ajouter les tâches en "Amélioration" au total
                        $totalPriseEnCompteEtAmelioration++;
                        break;
                }
            }
        }

        $lastModifiedStopClientWebtasks = array_filter($webtasks, function ($webtask) {
            return $webtask->getAvancementDeLaTache() === '❌ Stop Client ❌';
        });

        usort($lastModifiedStopClientWebtasks, function ($a, $b) {
            return $a->getDateFinDemandee() <=> $b->getDateFinDemandee();
        });

        $lastModifiedStopClientWebtasks = array_slice($lastModifiedStopClientWebtasks, 0, 3);

        // Récupérer les notifications visibles de l'utilisateur connecté
        $notifications = $notificationRepository->findBy([
            'user' => $user->getId(),
            'visible' => true
        ]);

        // Vérifier le mode maintenance
        $settings = $this->settingsRepository->find(1);
        $maintenanceMode = $settings ? $settings->getMaintenanceMode() : false;

        // Créer un tableau pour lier codeWebtask à id
        $idWebtaskMap = [];
        foreach ($notifications as $notification) {
            $idWebtask = $this->webTaskRepository->findIdByCodeWebtask($notification->getCodeWebtask());
            if ($idWebtask !== null) {
                $idWebtaskMap[$notification->getCodeWebtask()] = $idWebtask;
            }
        }

        return $this->render('Client/home.html.twig', [
            'webtasks' => $webtasks,
            'nonPrisesEnCompte' => $nonPrisesEnCompte,
            'priseEnCompte' => $priseEnCompte,
            'totalPriseEnCompteEtAmelioration' => $totalPriseEnCompteEtAmelioration,
            'terminee' => $terminee,
            'refusee' => $refusee,
            'validee' => $validee,
            'stopClient' => $stopClient,
            'totalTaches' => $totalTaches,
            'lastModifiedStopClientWebtasks' => $lastModifiedStopClientWebtasks,
            'notifications' => $notifications,
            'idWebtaskMap' => $idWebtaskMap,
            'maintenance_mode' => $maintenanceMode,
            'logo' => $logo,
            'client' => $client,
            'isPilote' => $isPilote,
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

    #[Route('/client/forum/{id}', name: 'app_forum', methods: ['GET'])]
    public function forum($id): Response
    {
        // Récupérer le client par son ID
        $client = $this->clientRepository->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer les forums associés à ce client
        $forums = $this->forumRepository->findBy(['client' => $client]);
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si le client est trouvé (ceci est généralement déjà garanti grâce à la route)
        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($client->getLogo()) {
            $logo = base64_encode(stream_get_contents($client->getLogo()));
        }


        return $this->render('Client/forum.html.twig', [
            'forums' => $forums,
            'client' => $client,
            'logo' => $logo, // Passer le logo du client encodé en base64


        ]);
    }
    private function mapTag(?string $tag): string
    {
        $tags = [
            0 => '(1) Mineur',
            1 => '(2) Grave',
            2 => '(3) Bloquant'
        ];

        return isset($tags[$tag]) ? $tags[$tag] : 'Inconnu';
    }

    private function mapAvancementDeLaTache(?string $avancement): string
    {
        $avancements = [
            0 => 'Non Prise en Compte',
            1 => 'Prise en Compte',
            2 => 'Terminée',
            3 => '❇️ Amélioration ❇️',
            4 => '⛔️ Refusée ⛔',
            5 => '✅ Validée',
            6 => '❌ Stop Client ❌',
            7 => '😃 Go Client 😃'
        ];

        return isset($avancements[$avancement]) ? $avancements[$avancement] : 'Inconnu';
    }
}