<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/redirect', name: 'app_redirect')]
    public function redirectAfterLogin(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();

        // Vérification des conditions d'accès
        if ($user) {
            // Vérifiez le champ "depart_entreprise"
            if ($user->getDepartEntreprise() == '1') {
                $this->addFlash('error', 'Votre accès est refusé. Contactez l\'administrateur.');
                return $this->redirectToRoute('app_login');
            }

            // Vérifiez le champ "webtaskOuvertureContact"
            if ($user->getWebtaskOuvertureContact() == '0') {
                $this->addFlash('error', 'Votre accès est refusé. Contactez l\'administrateur.');
                return $this->redirectToRoute('app_login');
            }

            // Vérifiez le champ "roleWX"
            if (empty($user->getRoleWX())) {
                $this->addFlash('error', 'Votre accès est refusé. Contactez l\'administrateur.');
                return $this->redirectToRoute('app_login');
            }

            // Vérifiez le rôle de l'utilisateur
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('app_homeadmin');
            } else {
                return $this->redirectToRoute('app_homeclient');
            }
        }

        throw new AccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette ressource.');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}