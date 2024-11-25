<?php
// src/Controller/MaintenanceController.php

namespace App\Controller; // Vérifiez que c'est bien 'App\Controller'

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Settings;

class MaintenanceController extends AbstractController
{
    #[Route('/admin/toggle-maintenance', name: 'admin_toggle_maintenance', methods: ['POST'])]
    public function toggleMaintenance()
    {
        // Vérifie si l'utilisateur a le droit d'activer la maintenance
        // (ajoute ta logique d'autorisation ici)

        // Récupère les paramètres de maintenance
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);
        
        // Active ou désactive le mode maintenance
        if ($settings) {
            $settings->setMaintenanceMode(!$settings->getMaintenanceMode());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($settings);
            $entityManager->flush();
        }

        // Envoie une notification à tous les utilisateurs connectés
        // (tu devras mettre en place un système pour cela, par exemple via WebSocket ou Mercure)

        $this->addFlash('success', 'Tous les utilisateurs ont été avertis de la maintenance.');

        return $this->redirectToRoute('app_gestionuser'); // Redirige vers la page d'administration
    }

    // src/Controller/MaintenanceController.php

// Ajoutez cette méthode à votre MaintenanceController
#[Route('/maintenance', name: 'maintenance')]
public function maintenance(): Response
{
    return $this->render('maintenance/maintenance.html.twig');
}

}
