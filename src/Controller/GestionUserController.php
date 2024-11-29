<?php
// src/Controller/GestionUserController.php

namespace App\Controller;

use App\Repository\WebtaskRepository;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Webtask;
use App\Entity\Settings; // Ajout pour gérer la maintenance
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GestionUserController extends AbstractController
{
    private $webTaskRepository;
    private $entityManager;
    private $notificationRepository;

    public function __construct(
        WebtaskRepository $webTaskRepository,
        EntityManagerInterface $entityManager, 
        NotificationRepository $notificationRepository
    ) {
        $this->webTaskRepository = $webTaskRepository;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/gestionutilisateur', name: 'app_gestionuser')]
    public function gestionUtilisateur(EntityManagerInterface $entityManager, WebtaskRepository $webtaskRepository, NotificationRepository $notificationRepository): Response
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

        // Récupérer les utilisateurs associés
        $users = $entityManager->createQueryBuilder()
            ->select('u, c')
            ->from(User::class, 'u')
            ->innerJoin('u.idclient', 'c') // Ajustez le nom de la relation
            ->orderBy('c.raison_sociale', 'ASC')
            ->addOrderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();

        // Créer un tableau pour stocker les couleurs par raison sociale
        $colorMap = [];

        // Assigner une couleur à chaque raison sociale
        foreach ($users as $user) {
            $clientRaisonSociale = $user->getIdclient()->getRaisonSociale();

            // Vérifiez si la couleur a déjà été générée pour cette raison sociale
            if (!isset($colorMap[$clientRaisonSociale])) {
                $colorMap[$clientRaisonSociale] = $this->generateColorFromString($clientRaisonSociale);
            }
        }

        // Maintenant, assigner la couleur aux utilisateurs
        foreach ($users as $user) {
            $clientRaisonSociale = $user->getIdclient()->getRaisonSociale();
            // Assignez la couleur au user en ajoutant une propriété temporaire
            $user->color = $colorMap[$clientRaisonSociale];
        }

        // Vérifier si chaque utilisateur est lié à des webtasks
        foreach ($users as $user) {
            $linkedWebtasks = $entityManager->getRepository(Webtask::class)
                ->createQueryBuilder('w')
                ->where('w.iddemandeur = :userId OR w.responsable = :userId OR w.Piloteid = :userId')
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getResult();

            // Ajoutez un attribut pour savoir si l'utilisateur peut être supprimé
            $user->canBeDeleted = count($linkedWebtasks) === 0; // true si pas de webtasks
            $user->linkedWebtasksCount = count($linkedWebtasks); // Compte des webtasks pour le message d'erreur
        }

        return $this->render('Admin/GestionUser.html.twig', [
            'users' => $users,
            'currentUser' => $user,
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
    
    #[Route('/reset-password/{id}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, string $id): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Gestion de la soumission du formulaire
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');

            if (!empty($newPassword)) {
                try {
                    $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($encodedPassword);
                    $user->setMustResetPassword(true); // Si vous avez besoin de cette logique

                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_gestionuser'); // Rediriger après la réussite
                } catch (\Exception $e) {
                    // Gestion des exceptions (par exemple, logging)
                    $this->addFlash('error', 'Erreur lors de la réinitialisation du mot de passe.');
                }
            }
        }

        return $this->render('Admin/reset_password.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update-access/{id}', name: 'app_update_access', methods: ['POST'])]
    public function updateAccess(Request $request, EntityManagerInterface $entityManager, string $id): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Récupérer l'état actuel
        $currentAccess = $user->getWebtaskOuvertureContact(); // 0 ou 1

        // Inverser la logique :
        $newAccess = ($currentAccess === 1) ? 0 : 1; // Inverser l'accès

        // Mettre à jour le champ webtaskOuvertureContact
        $user->setWebtaskOuvertureContact($newAccess);

        // Sauvegarde dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_gestionuser');
    }

    #[Route('/user/delete/{id}', name: 'app_delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, string $id): Response
    {
        // Récupérer l'utilisateur à supprimer
        $user = $entityManager->getRepository(User::class)->find($id);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Vérifier si l'utilisateur est lié à une webtask
        $linkedWebtasks = $entityManager->getRepository(Webtask::class)
            ->createQueryBuilder('w')
            ->where('w.iddemandeur = :userId OR w.responsable = :userId OR w.Piloteid = :userId')
            ->setParameter('userId', $id)
            ->getQuery()
            ->getResult();

        // Si l'utilisateur est lié à une webtask, ne pas le supprimer
        if (count($linkedWebtasks) > 0) {
            $this->addFlash('warning', 'Cet utilisateur ne peut pas être supprimé car il est associé à une webtask.');
            return $this->redirectToRoute('app_gestionuser');
        }

        // Si l'utilisateur n'est pas lié à une webtask, le supprimer
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_gestionuser');
    }

    #[Route('/toggle-maintenance', name: 'toggle_maintenance', methods: ['POST'])]
    public function toggleMaintenance(Request $request, EntityManagerInterface $entityManager): Response
    {
        $settings = $entityManager->getRepository(Settings::class)->find(1);
        if (!$settings) {
            $settings = new Settings();
        }

        // Inverser l'état de maintenance
        $currentStatus = $settings->getMaintenanceMode();
        $settings->setMaintenanceMode(!$currentStatus);

        $entityManager->persist($settings);
        $entityManager->flush();

        // Stocker le message de maintenance dans la session
        $message = $currentStatus ? 'Le mode maintenance a été activé.' : 'Le mode maintenance a été désactivé.';
        $request->getSession()->set('maintenance_message', $message);

        // Retourner une réponse JSON
        return $this->json(['maintenance' => !$currentStatus]);
    }

    #[Route('/user/update-role/{id}/{role}', name: 'app_update_user_role', methods: ['POST'])]
    public function updateUserRole(string $id, string $role, EntityManagerInterface $entityManager): Response
    {
        // Trouver l'utilisateur
        $user = $entityManager->getRepository(User::class)->find($id);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Mettre à jour le champ roleWX avec le rôle fourni ("lecteur" ou "créateur")
        $user->setRoleWX($role);

        // Enregistrer les modifications dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        // Rediriger vers la gestion des utilisateurs
        return $this->redirectToRoute('app_gestionuser');
    }
    
    // Méthode pour générer une couleur hexadécimale à partir d'une chaîne
    private function generateColorFromString(string $string): string
    {
        $hash = md5($string);
        return '#' . substr($hash, 0, 6);
    }
}