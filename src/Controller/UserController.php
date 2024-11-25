<?php
// src/Controller/UserController.php
namespace App\Controller;

use App\Entity\User; // Assurez-vous d'importer l'entité User
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface; // Importez l'interface de l'Entity Manager
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // Import correct de la classe Request
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response; // Import correct de la classe Response
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/user/{id}', name: 'app_user_details', methods: ['GET'])]
    public function userDetails($id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->getId(),
            'name' => $user->getNom() . ' ' . $user->getPrenom(),
            'email' => $user->getEmail(),
            // Ajoutez d'autres détails si nécessaire
        ];

        return new JsonResponse($data);
    }

    public function updateAccess(Request $request, $id): Response
    {
        // Récupérez l'utilisateur par ID
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Récupérez la nouvelle valeur de l'accès
        $newAccess = $request->request->get('webtaskOuvertureContact');

        // Mettez à jour l'accès de l'utilisateur
        $user->setWebtaskOuvertureContact($newAccess);
        $this->getDoctrine()->getManager()->flush();

        // Rediriger vers la page de gestion des utilisateurs
        return $this->redirectToRoute('app_gestionuser');
    }
    
}

