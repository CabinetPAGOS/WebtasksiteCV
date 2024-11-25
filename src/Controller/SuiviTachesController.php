<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\WebtaskRepository;
use App\Services\VersionService;
use App\Services\TextTransformer;
use App\Entity\User;
use App\Entity\Webtask;
use Doctrine\ORM\EntityManagerInterface;

class SuiviTachesController extends AbstractController
{
    private $webTaskRepository;
    private $versionService;
    private $textTransformer;
    private $entityManager;

    public function __construct(WebtaskRepository $webTaskRepository, VersionService $versionService, TextTransformer $textTransformer, EntityManagerInterface $entityManager)
    {
        $this->webTaskRepository = $webTaskRepository;
        $this->versionService = $versionService;
        $this->textTransformer = $textTransformer;
        $this->entityManager = $entityManager;
    }

    #[Route('/suivitaches/{id}', name: 'app_suivitaches')]
    public function suivitaches(Request $request, $id): Response       
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer la Webtask avec l'ID spécifié
        $webtask = $this->entityManager->getRepository(Webtask::class)->find($id);

        // Si aucune Webtask n'est trouvée, retourner une 404
        if (!$webtask) {
            throw $this->createNotFoundException('Tâche non trouvée');
        }
        
        $versionLibelle = $this->versionService->getLibelleById($webtask->getIdversion());
        $webtask->setVersionLibelle($versionLibelle);
    
        // Transformer les champs de texte
        $webtask->setDescription($this->textTransformer->transformCrToNewLine($webtask->getDescription()));
        $webtask->setCommentaireWebtaskClient($this->textTransformer->transformCrToNewLine($webtask->getCommentaireWebtaskClient()));
        $webtask->setCommentaireInternePagos($this->textTransformer->transformCrToNewLine($webtask->getCommentaireInternePagos()));
    
        $anciennesWebtasks = $this->webTaskRepository->findBy(['Webtask' => $webtask->getWebtask()]);

        usort($anciennesWebtasks, function ($a, $b) {
            // Comparaison sur le champ 'idversion' en décroissant
            $idversionComparison = strcmp($b->getIdversion(), $a->getIdversion());
        
            if ($idversionComparison === 0) {
                // Si 'idversion' est identique, on trie par 'filtre' en décroissant
                return strcmp($b->getFiltre(), $a->getFiltre());
            }
        
            return $idversionComparison;
        });

        // Récupérer la première webtask dans la liste triée
        $premiereWebtask = reset($anciennesWebtasks);

        // Collecter les liens de documents en fonction des conditions
        $documentsLiensNonExtraits = [];
        if ($user->getIdclient() && $user->getIdclient()->getRaisonSociale() === 'CABINET PAGOS') {
            // Tous les documents pour les utilisateurs du client PAGOS
            foreach ($anciennesWebtasks as $task) {
                if ($task->getLiendrive1()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive1();
                }
                if ($task->getLiendrive2()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive2();
                }
                if ($task->getLiendrive3()) {
                    $documentsLiensNonExtraits[] = $task->getLiendrive3();
                }
            }
        } else {
            // Autres utilisateurs, filtrer par Webtask avec un commentaire client renseigné
            foreach ($anciennesWebtasks as $task) {
                if ($task->getCommentaireWebtaskClient() && ($task->getLiendrive1() || $task->getLiendrive2() || $task->getLiendrive3())) {
                    if ($task->getLiendrive1()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive1();
                    }
                    if ($task->getLiendrive2()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive2();
                    }
                    if ($task->getLiendrive3()) {
                        $documentsLiensNonExtraits[] = $task->getLiendrive3();
                    }
                }
            }
        }

        // Si aucun document n'est trouvé et qu'une première webtask existe, récupérez les liens de cette première webtask
        if (empty($documentsLiensNonExtraits) && $premiereWebtask) {
            if ($premiereWebtask->getLiendrive1()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive1();
            }
            if ($premiereWebtask->getLiendrive2()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive2();
            }
            if ($premiereWebtask->getLiendrive3()) {
                $documentsLiensNonExtraits[] = $premiereWebtask->getLiendrive3();
            }
        }

        // Collecter tous les liens de documents et extraire la partie après le dernier '?'
        $documentsLiens = [];
        foreach ($anciennesWebtasks as $task) {
            if ($task->getLiendrive1()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive1());
            }
            if ($task->getLiendrive2()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive2());
            }
            if ($task->getLiendrive3()) {
                $documentsLiens[] = $this->extractAfterLastQuestionMark($task->getLiendrive3());
            }
        }

        // Récupérer le responsable directement
        $responsable = $webtask->getResponsable();

        if ($responsable) {
            $responsableNomPrenom = $responsable->getPrenom() . ' ' . $responsable->getNom();
        } else {
            $responsableNomPrenom = 'Inconnu';
        }

        // Récupérer le pilote par son ID
        $piloteId = $webtask->getPiloteid(); 
        $pilote = null;

        if ($piloteId) {
            $pilote = $this->entityManager->getRepository(User::class)->find($piloteId);
        }

        if ($pilote) {
            $piloteNomPrenom = $pilote->getPrenom() . ' ' . $pilote->getNom();
        } else {
            $piloteNomPrenom = 'Inconnu';
        }

        // Transformer les champs de texte pour chaque ancienne Webtask
        $anciennesWebtasksDetails = [];
        foreach ($anciennesWebtasks as $task) {
            // Récupérer le libellé de la version pour chaque ancienne webtask
            $versionLibelleAncienne = $this->versionService->getLibelleById($task->getIdversion());
            $task->setVersionLibelle($versionLibelleAncienne);

            // Transformer les champs de texte
            $task->setDescription($this->textTransformer->transformCrToNewLine($task->getDescription()));
            $task->setCommentaireWebtaskClient($this->textTransformer->transformCrToNewLine($task->getCommentaireWebtaskClient()));
            $task->setCommentaireInternePagos($this->textTransformer->transformCrToNewLine($task->getCommentaireInternePagos()));

            // Générer les initiales basées sur le nom complet du créateur (ou "Inconnu" si non disponible)
            $initialesCreePar = $this->generateInitiales($task->getCreerPar());
        
            $anciennesWebtasksDetails[] = [
                'creeLe' => $task->getCreeLe(),
                'creePar' => $initialesCreePar, // Utilisation des initiales ici
                'avancement' => $this->mapAvancementDeLaTache($task->getAvancementDeLaTache()),
                'dateFinDemandee' => $task->getDateFinDemandee(),
                'versionLibelle' => $versionLibelleAncienne,
                'baseDeDonnees' => $task->getBaseclient(),
                'commentaire_webtask_client' => $task->getCommentaireWebtaskClient(),
                'commentaire_interne_pagos' => $task->getCommentaireInternePagos(),
            ];
        }
    
        // Récupération des commentaires
        $commentaires = [
            'client' => $webtask->getCommentaireWebtaskClient(),
            'interne' => $webtask->getCommentaireInternePagos(),
        ];
    
        // Initialiser les commentaires en tant que tableau
        $commentairesListe = [];
        if (!empty($commentaires['client'])) {
            $commentairesListe[] = [
                'type' => 'Client',
                'contenu' => $commentaires['client']
            ];
        }
        if (!empty($commentaires['interne'])) {
            $commentairesListe[] = [
                'type' => 'Interne',
                'contenu' => $commentaires['interne']
            ];
        }
    
        // Tri des commentaires par type (client en premier, interne ensuite)
        usort($commentairesListe, function($a, $b) {
            return strcmp($a['type'], $b['type']);
        });
    
        $mappedTag = $this->mapTag($webtask->getTag());
        $tagClass = $this->getTagClass($webtask->getTag());
        $mappedAvancement = $this->mapAvancementDeLaTache($webtask->getAvancementdelatache());
    
        return $this->render('Client/suivitaches.html.twig', [
            'webtask' => $webtask,
            'mappedTag' => $mappedTag,
            'tagClass' => $tagClass,
            'mappedAvancement' => $mappedAvancement,
            'commentairesListe' => $commentairesListe,
            'anciennesWebtasksDetails' => $anciennesWebtasksDetails,
            'responsableNomPrenom' => $responsableNomPrenom,
            'piloteNomPrenom' => $piloteNomPrenom,
            'documentsLiens' => $documentsLiens,
            'documentsLiensNonExtraits' => $documentsLiensNonExtraits,
        ]);
    }

    // Méthode pour extraire la partie après le dernier "?" dans un lien
    private function extractAfterLastQuestionMark(string $url): string
    {
        $parts = explode('?', $url);
        return end($parts); // Renvoie la dernière partie après le dernier '?'
    }

    // Méthode pour générer des initiales à partir du prénom et du nom de l'utilisateur
    private function generateInitiales(?string $creePar): string
    {
        if (!$creePar) {
            return 'INCONNU'; // Si aucune information n'est disponible
        }

        // Séparer les parties du nom
        $parts = explode(' ', $creePar);

        if (count($parts) === 1) {
            // Si un seul nom est donné, on prend la première lettre de ce nom
            return strtoupper(substr($parts[0], 0, 2));
        } elseif (count($parts) > 1) {
            // Si il y a un prénom et un nom, on prend la première lettre du prénom et du nom
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return 'INCONNU';
    }

    private function mapTag(?int $tag): string
    {
        $tags = [
            0 => '(1) Mineur',
            1 => '(2) Grave',
            2 => '(3) Bloquant'
        ];

        return isset($tags[$tag]) ? $tags[$tag] : 'Inconnu';
    }

    private function getTagClass(?int $tag): string
    {
        $classes = [
            0 => 'tag-minor',
            1 => 'tag-serious',
            2 => 'tag-blocking'
        ];

        return isset($classes[$tag]) ? $classes[$tag] : 'tag-unknown';
    }

    // Fonction de mapping pour l'avancement de la tâche
    private function mapAvancementDeLaTache(?int $avancement): array
    {
        $avancements = [
            0 => ['label' => 'Non Prise en Compte', 'class' => 'text-danger'],
            1 => ['label' => 'Prise en Compte', 'class' => 'text-warning'],
            2 => ['label' => 'Terminée', 'class' => 'text-success'],
            3 => ['label' => '❇️ Amélioration ❇️', 'class' => 'text-danger bg-success'],
            4 => ['label' => '⛔️ Refusée ⛔️', 'class' => 'text-white bg-dark'],
            5 => ['label' => '✅ Validée', 'class' => 'text-info bg-dark'],
            6 => ['label' => '❌ Stop Client ❌', 'class' => 'text-white bg-danger'],
            7 => ['label' => '😃 Go Client 😃', 'class' => 'text-dark bg-success']
        ];

        return isset($avancements[$avancement]) ? $avancements[$avancement] : ['label' => 'Inconnu', 'class' => 'text-muted'];
    }
}