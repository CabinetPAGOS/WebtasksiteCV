<?php

// src/Controller/ClientsdePagosAdminController.php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Repository\WebtaskRepository;
use App\Services\TextTransformer;
use App\Services\VersionService;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ClientsdePagosAdminController extends AbstractController
{
    private $versionService;
    private $textTransformer;
    private $userRepository;
    private $entityManager;
    private $notificationRepository;

    public function __construct(
        VersionService $versionService, 
        TextTransformer $textTransformer, 
        UserRepository $userRepository,
        EntityManagerInterface $entityManager, 
        NotificationRepository $notificationRepository
    ) {
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/admin/clientsdepagos', name: 'app_clientsdepagosadmin')]
    public function clientsdepagosadmin(ClientRepository $clientRepository, NotificationRepository $notificationRepository, Request $request): Response
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

        // Récupérer le logo du client
        $logo = null;
        if ($idclient->getLogo()) {
            $logo = base64_encode(stream_get_contents($idclient->getLogo()));
        }

        $excludedId = 'e4e080b3758761bd01758f5fcfed03d9';
        $clients = $clientRepository->findAll();
 
        // Filtrer les clients par leur ID exclu
        $filteredClients = array_filter($clients, function ($client) use ($excludedId) {
            return $client->getId() !== $excludedId;
        });
 
        // Récupérer la requête de recherche (query)
        $query = $request->query->get('query');
 
        // Si une requête de recherche est envoyée, filtrer les clients
        if ($query) {
            $filteredClients = array_filter($filteredClients, function($client) use ($query) {
                return stripos($client->getRaisonSociale(), $query) !== false;
            });
        }
 
        // Récupérer le critère de tri (tri par défaut : raisonSociale)
        $sortBy = $request->query->get('sort_by', 'raisonSociale');
        $sortOrder = $request->query->get('sort_order', 'asc'); // 'asc' pour croissant, 'desc' pour décroissant
 
        // Trier les clients
        usort($filteredClients, function($a, $b) use ($sortBy, $sortOrder) {
            $valueA = $a->{'get' . ucfirst($sortBy)}();
            $valueB = $b->{'get' . ucfirst($sortBy)}();
 
            if ($sortOrder === 'asc') {
                return $valueA <=> $valueB;
            } else {
                return $valueB <=> $valueA;
            }
        });
 
        // Créer un tableau associatif avec les informations du client, y compris le logo encodé
        $clientsData = [];
        foreach ($filteredClients as $client) {
            $logoBase64 = null;
 
            // Si un logo est disponible, on l'encode en base64
            if ($client->getLogo()) {
                $logoBase64 = base64_encode(stream_get_contents($client->getLogo()));
            }
 
            $clientsData[] = [
                'id' => $client->getId(),
                'raisonSociale' => $client->getRaisonSociale(),
                'logoBase64' => $logoBase64, // Ajouter le logo encodé ici
            ];
        }

        // Récupérer les utilisateurs depuis le UserRepository
        $users = $this->userRepository->findAll();  // Récupération de tous les utilisateurs

        // Récupérer les notifications visibles de l'utilisateur connecté
        $notifications = $notificationRepository->findBy([
            'user' => $user->getId(),
            'visible' => true
        ]);

        // Passer $clientsData et $users à la vue
        return $this->render('Admin/ClientsdePagosadmin.html.twig', [
            'clients' => $clientsData,  // Ici on passe le tableau $clientsData
            'users' => $users,          // Passer la liste des utilisateurs à la vue
            'query' => $query,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'logo' => $logo,
            'notifications' => $notifications,
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_details', methods: ['GET'])]
    public function userDetails($id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/client/{id}', name: 'app_client_details', methods: ['GET'])]
    public function clientDetails($id, ClientRepository $clientRepository): JsonResponse
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], 404);
        }

        $data = [
            'id' => $client->getId(),
            'raisonSociale' => $client->getRaisonSociale(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/client/{id}/name', name: 'app_client_name', methods: ['GET'])]
    public function clientName($id, ClientRepository $clientRepository): JsonResponse
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], 404);
        }

        return new JsonResponse([
            'name' => $client->getRaisonSociale()
        ]);
    }

    #[Route('/client/{id}/webtasks', name: 'app_client_webtasks', methods: ['GET'])]
    public function clientWebTasks($id, WebtaskRepository $webtaskRepository, Request $request): JsonResponse
    {
        $webTasks = $webtaskRepository->findByClient($id);

        if (empty($webTasks)) {
            return new JsonResponse(['error' => 'No webtasks found'], 404);
        }

        // Récupérer la requête de recherche (query)
        $query = $request->query->get('query');

        // Si une requête de recherche est envoyée, filtrer les webtasks sur le champ `titre`
        if ($query) {
            $query = strtolower($query); // Convertir en minuscule pour une recherche insensible à la casse
            $webTasks = array_filter($webTasks, function($webTask) use ($query) {
                return stripos(strtolower($webTask->getTitre()), $query) !== false ||
                    stripos(strtolower($webTask->getWebtask()), $query) !== false ||
                    stripos(strtolower($webTask->getCode()), $query) !== false;
            });
        }

        // Trier les webtasks par le champ `webtask` dans l'ordre croissant
        usort($webTasks, function($a, $b) {
            return strcmp($a->getWebtask(), $b->getWebtask());
        });

        $data = [];
        foreach ($webTasks as $webTask) {
            $idVersion = $webTask->getIdversion();

            if ($idVersion === null) {
                $versionLibelle = 'Version non disponible';
            } else {
                $versionLibelle = $this->versionService->getLibelleById($idVersion);
            }

            $webTask->setDescription($this->textTransformer->transformCrToNewLine($webTask->getDescription()));

            $data[] = [
                'id' => $webTask->getId(),
                'title' => $webTask->getTitre(),
                'description' => $webTask->getDescription(),
                'webtask' => $webTask->getWebtask(),
                'versionLibelle' => $versionLibelle,
                'datefinDemandee' => $webTask->getDateFinDemandee(),
                'code' => $webTask->getCode(),
                'etatdelawebtask' => $webTask->getEtatDeLaWebtask(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/client/webtask/consulter', name: 'app_consulter_webtask', methods: ['GET'])]
    public function consulterWebTask(Request $request, WebtaskRepository $webtaskRepository): Response
    {
        $id = $request->query->get('id'); // Récupère l'ID de la webtask dans la requête
        $webtask = $webtaskRepository->findOneBy(['code' => $id]); // Remplace 'id' par 'code' si c'est l'identifiant utilisé

        if (!$webtask) {
            throw $this->createNotFoundException('Webtask non trouvée');
        }

        return $this->render('Admin/consulter_webtask.html.twig', [
            'webtask' => $webtask,
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
}