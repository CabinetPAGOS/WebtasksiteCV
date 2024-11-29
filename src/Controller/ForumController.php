<?php

namespace App\Controller;

use App\Repository\WebtaskRepository;
use App\Repository\ForumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use App\Entity\Forum;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ForumType;
use App\Entity\Notification;
use App\Repository\NotificationRepository;

class ForumController extends AbstractController
{
    private $forumRepository;
    private $webTaskRepository;
    private $notificationRepository;

    public function __construct(ForumRepository $forumRepository, NotificationRepository $notificationRepository, WebtaskRepository $webTaskRepository)
    {
        $this->webTaskRepository = $webTaskRepository;
        $this->forumRepository = $forumRepository;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/admin/forum', name: 'app_adminforum', methods: ['GET'])]
    public function forumadmin(Request $request, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifiez si l'utilisateur est connecté
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

        // Récupérer l'ID du client depuis les paramètres d'URL
        $clientId = $request->query->get('id'); // Changer 'client_id' en 'id'
        
        // Vérifiez si l'ID du client est fourni
        if ($clientId) {
            // Récupérer le client
            $client = $entityManager->getRepository(Client::class)->find($clientId);
            // Récupérer les forums du client
            $forums = $this->forumRepository->findBy(['client' => $clientId]); 
        } else {
            throw $this->createNotFoundException('Aucun ID de client fourni.');
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

        return $this->render('Admin/forum.html.twig', [
            'forums' => $forums,
            'client' => $client,
            'client_id' => $clientId,
            'logo' => $logo,
            'notifications' => $notifications,
            'idWebtaskMap' => $idWebtaskMap,
        ]);
    }

    #[Route('/admin/forum/edit/{id}', name: 'app_adminforum_edit', methods: ['GET', 'POST'])]
    public function edit(Forum $forum, Request $request, EntityManagerInterface $em): Response
    {
        // Créez le formulaire
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

         // Récupérer l'ID du client associé à l'utilisateur connecté
         $idclient = $user->getIdclient(); 

         // Vérifier si un client est associé à l'utilisateur
         if (!$idclient) {
             throw $this->createNotFoundException('Aucun client associé à cet utilisateur.');
         }

        $logo = null;
        if ($idclient->getLogo()) {
            $logo = base64_encode(stream_get_contents($idclient->getLogo()));
        }

        // Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush(); // Sauvegarder les modifications dans la base de données
            return $this->redirectToRoute('app_adminforum', ['id' => $forum->getClient()->getId()]);
        }

        return $this->render('Admin/forum_edit.html.twig', [
            'form' => $form->createView(),
            'forum' => $forum,
            'logo' => $logo,

        ]);
    }

    #[Route('/admin/forum/delete/{id}', name: 'app_adminforum_delete', methods: ['POST'])]
    public function delete(Forum $forum, EntityManagerInterface $entityManager): Response
    {
        // Supprimer le forum
        $entityManager->remove($forum);
        $entityManager->flush();

        // Rediriger vers la page des forums après suppression
        $clientId = $forum->getClient()->getId();
        return $this->redirectToRoute('app_adminforum', ['id' => $clientId]);
    }
    
    #[Route('/client/{id}/forum', name: 'app_client_add_forum', methods: ['POST'])]
    public function addForum(Request $request, Client $client, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['content']) && !empty($data['content'])) {
            $forum = new Forum();
            $forum->setContent($data['content']);
            $forum->setClient($client);
            $forum->setDate(new \DateTime()); // Assurez-vous que la date est bien définie

            $entityManager->persist($forum);
            $entityManager->flush();

            // Renvoyer la réponse avec le contenu et la date formatée
            return new JsonResponse([
                'message' => 'Résumé ajouté avec succès',
                'content' => $forum->getContent(),
                'date' => $forum->getDate()->format('Y-m-d H:i:s') // Formatez la date pour l'envoi
            ], Response::HTTP_CREATED);
        }

        return new JsonResponse(['error' => 'Le contenu du résumé est vide'], Response::HTTP_BAD_REQUEST);
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