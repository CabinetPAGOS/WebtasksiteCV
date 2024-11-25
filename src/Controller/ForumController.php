<?php

namespace App\Controller;

use App\Repository\ForumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse; // Ajoutez cette ligne
use App\Repository\UserRepository; // Assurez-vous que ce repository existe
use App\Entity\Forum;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ForumType;

class ForumController extends AbstractController
{
    private $forumRepository;

    public function __construct(ForumRepository $forumRepository)
    {
        $this->forumRepository = $forumRepository;
    }

    #[Route('/admin/forum', name: 'app_adminforum', methods: ['GET'])]
public function forumadmin(Request $request, EntityManagerInterface $entityManager): Response
{
    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    // Vérifiez si l'utilisateur est connecté
    if (!$user) {
        return $this->redirectToRoute('app_login');
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

    return $this->render('Admin/forum.html.twig', [
        'forums' => $forums, // Passer les résumés à la vue
        'client' => $client, // Passer le client à la vue
        'client_id' => $clientId, // Passer l'ID du client à la vue si nécessaire
    ]);
}



// src/Controller/ForumController.php



#[Route('/admin/forum/edit/{id}', name: 'app_adminforum_edit', methods: ['GET', 'POST'])]
public function edit(Forum $forum, Request $request, EntityManagerInterface $em): Response
{
    // Créez le formulaire
    $form = $this->createForm(ForumType::class, $forum);
    $form->handleRequest($request);

    // Traitement de la soumission
    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush(); // Sauvegarder les modifications dans la base de données
        return $this->redirectToRoute('app_adminforum', ['id' => $forum->getClient()->getId()]);
    }

    return $this->render('Admin/forum_edit.html.twig', [
        'form' => $form->createView(),
        'forum' => $forum,
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





}