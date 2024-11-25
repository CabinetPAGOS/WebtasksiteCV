<?php 
// src/Controller/ClientController.php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ClientAdminController extends AbstractController
{
    private $entityManager;
    private $notificationRepository;

    public function __construct(
        EntityManagerInterface $entityManager, 
        NotificationRepository $notificationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/admin/client/create', name: 'app_createclientadmin')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
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

        $client = new Client();

        // Créer le formulaire
        $form = $this->createFormBuilder($client)
            ->add('id', TextType::class, [
                'label' => 'Identifiant Interne SYLOB :',
                'required' => true,
                'mapped' => false, // Exclure de l'entité pour la persistance
            ])
            ->add('code', TextType::class, [
                'label' => 'Code du client :',
                'required' => false,
            ])
            ->add('raison_sociale', TextType::class, [
                'label' => 'Raison Sociale :',
                'required' => false,
            ])
            ->add('google_drive_webtask', TextType::class, [
                'label' => 'Lien Google Drive Webtask :',
                'required' => false,
            ])
            ->add('webtaskOuvertureContact', CheckboxType::class, [
                'label' => 'WebTask Ouverture Client :',
                'required' => false,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo :',
                'required' => false,
            ])
            ->getForm();

        // Traitement du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'id du formulaire
            $id = $form->get('id')->getData();

            // Assigner l'id à l'entité Client
            $client->setId($id);

            // Vérifier si l'id est renseigné
            if (empty($client->getId())) {
                $this->addFlash('error', 'L\'identifiant doit être renseigné.');
                return $this->render('Admin/createclient.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Vérifier si un client avec cet ID existe déjà
            if ($entityManager->getRepository(Client::class)->find($client->getId())) {
                $this->addFlash('error', 'Un client avec cet identifiant existe déjà.');
                return $this->render('Admin/createclient.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Enregistrer le client en base de données
            $entityManager->persist($client);
            $entityManager->flush();

            // Rediriger vers une page ou afficher un message de succès
            $this->addFlash('success', 'Le client a été créé avec succès.');
            return $this->redirectToRoute('app_homeadmin');
        }

        // Récupérer les notifications visibles
        $notifications = $this->notificationRepository->findVisibleNotifications();

        // Afficher le formulaire
        return $this->render('Admin/createclient.html.twig', [
            'form' => $form->createView(),
            'logo' => $logo,
            'notifications' => $notifications,
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
}