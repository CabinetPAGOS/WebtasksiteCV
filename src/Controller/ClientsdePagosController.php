<?php
// src/Controller/ClientsdePagosAdminController.php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\WebtaskRepository;
use App\Services\TextTransformer;
use App\Services\VersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository; // Assurez-vous que ce repository existe
use App\Entity\Forum;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;



class ClientsdePagosController extends AbstractController
{
    private $versionService;
    private $textTransformer;
    private $userRepository; // Ajout de UserRepository

    public function __construct(VersionService $versionService, TextTransformer $textTransformer, UserRepository $userRepository)
    {
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->userRepository = $userRepository;
    }

    #[Route('/clientsdepagos', name: 'app_clientsdepagos')]
    public function clientsdepagos(ClientRepository $clientRepository, Request $request): Response
    {
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

        // Passer $clientsData et $users à la vue
        return $this->render('Client/ClientsdePagos.html.twig', [
            'clients' => $clientsData,  // Ici on passe le tableau $clientsData
            'users' => $users,          // Passer la liste des utilisateurs à la vue
            'query' => $query,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
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
            // Ajoutez ici les informations supplémentaires à afficher
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

    #[Route('/client/{id}/forum', name: 'app_client_add_forum', methods: ['POST'])]
public function addForum(Request $request, Client $client, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (isset($data['content']) && !empty($data['content'])) {
        $forum = new Forum();
        $forum->setContent($data['content']);
        $forum->setClient($client);
        $forum->setDate(new \DateTime());

        $entityManager->persist($forum);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Résumé ajouté avec succès'], Response::HTTP_CREATED);
    }

    return new JsonResponse(['error' => 'Le contenu du résumé est vide'], Response::HTTP_BAD_REQUEST);
}


#[Route('/client/{id}/forums', name: 'app_client_forums', methods: ['GET'])]
public function getClientForums(Client $client): JsonResponse
{
    $forums = $client->getForums(); // Récupère tous les résumés pour ce client

    $data = [];
    foreach ($forums as $forum) {
        $data[] = [
            'id' => $forum->getId(),
            'content' => $forum->getContent(),
            'date' => $forum->getDate()->format('Y-m-d H:i:s'),
        ];
    }

    return new JsonResponse($data);
}


}