<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
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

    #[Route('/admin/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, ClientRepository $clientRepository, NotificationRepository $notificationRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $utilisateur = $this->getUser();

        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'ID du client associé à l'utilisateur connecté
        $idclient = $utilisateur->getIdclient(); 

        // Vérifier si un client est associé à l'utilisateur
        if (!$idclient) {
            throw $this->createNotFoundException('Aucun client associé à cet utilisateur.');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($idclient->getLogo()) {
            $logo = base64_encode(stream_get_contents($idclient->getLogo()));
        }

        // Récupérer la liste des clients disponibles
        $clients = $clientRepository->findAll();

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
        
            // Assigner les rôles sélectionnés à l'utilisateur
            $roles = $form->get('roles')->getData();
            $user->setRoles($roles);
        
            // Assigner le client sélectionné à l'utilisateur
            $client = $form->get('idclient')->getData();
            $user->setIdclient($client);
        
            // Persist l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();
        
            return $this->redirectToRoute('app_register');
        }
        
        // Récupérer les notifications visibles de l'utilisateur connecté
        $notifications = $notificationRepository->findBy([
            'user' => $utilisateur->getId(),
            'visible' => true
        ]);

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'clients' => $clients,
            'logo' => $logo,
            'notifications' => $notifications,
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
