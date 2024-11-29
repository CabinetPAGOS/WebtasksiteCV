<?php

// src/Controller/CreerTachesController.php
namespace App\Controller;

use App\Entity\Webtask;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\TextTransformer;
use App\Services\VersionService;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Responsable;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ClientRepository;
use App\Repository\NotificationRepository;
use App\Repository\WebtaskRepository;

class CreerTachesController extends AbstractController
{
    private $webTaskRepository;
    private $textTransformer;
    private $versionService;
    private $clientRepository;
    private $notificationRepository;

    public function __construct(
        WebtaskRepository $webTaskRepository,
        TextTransformer $textTransformer, 
        VersionService $versionService, 
        ClientRepository $clientRepository, 
        NotificationRepository $notificationRepository
    ) {
        $this->webTaskRepository = $webTaskRepository;
        $this->textTransformer = $textTransformer;
        $this->versionService = $versionService;
        $this->clientRepository = $clientRepository;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/creertaches', name: 'app_creertaches')]
    public function creertaches(Request $request, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le client associé à l'utilisateur
        $client = $user->getIdclient();
        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Récupérer le logo du client
        $logo = null;
        if ($client->getLogo()) {
            $logo = base64_encode(stream_get_contents($client->getLogo()));
        }

        // Récupérer le lien Google Drive du client
        $googleDriveLink = $client->getGoogleDriveWebtask();

        if ($request->isMethod('POST')) {
            // Récupérer le titre de la nouvelle tâche
            $newTitle = $request->request->get('title');

            // Vérifier si une tâche existe déjà avec un titre similaire
            $existingTask = $entityManager->getRepository(Webtask::class)
                ->createQueryBuilder('w')
                ->where('w.titre = :title') // Correspondance exacte
                ->setParameter('title', $newTitle)
                ->getQuery()
                ->getOneOrNullResult(); // Récupère une tâche existante ou null

            // Créer une nouvelle tâche
            $newTache = new Webtask();

            for ($i = 1; $i <= 3; $i++) {
                $fileLink = $request->request->get("fileLink{$i}", "");   // lien de fichier
                $fileTitle = $request->request->get("fileTitle{$i}", ""); // titre de fichier
            
                // Enregistrer uniquement le titre si le lien est vide, sinon les deux
                $liendrive = !empty($fileTitle) ? "{$fileLink}?" . urlencode($fileTitle) : "";
                
                // Si seulement le titre est fourni sans lien
                if (empty($fileLink) && !empty($fileTitle)) {
                    $liendrive = urlencode($fileTitle);
                }
            
                // Appel dynamique du setter
                $setter = "setLienDrive{$i}";
                $newTache->$setter($liendrive);
            }

            // Vérifier si au moins un lien est renseigné
            $hasDocuments = !empty($liendrive1) || !empty($liendrive2) || !empty($liendrive3);
            $newTache->setDocumentsAttaches($hasDocuments ? '1' : '0');  // Stockage en tant que chaîne

            // Vérifier si une tâche existe déjà avec un titre similaire
            $existingTask = $entityManager->getRepository(Webtask::class)
                ->createQueryBuilder('w')
                ->where('w.titre = :title') // Correspondance exacte
                ->setParameter('title', $newTitle)
                ->getQuery()
                ->getOneOrNullResult(); // Récupère une tâche existante ou null

            // Logic for generating the code
            $lastTaskWithSWTCode = $entityManager->getRepository(Webtask::class)
                ->createQueryBuilder('w')
                ->where('w.code LIKE :prefix')
                ->setParameter('prefix', 'SWT-%')
                ->orderBy('w.code', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($lastTaskWithSWTCode) {
                // On extrait le numéro à partir de 'SWT-' et on l'incrémente
                $newCodeNumber = (int) substr($lastTaskWithSWTCode->getCode(), 6) + 1;
            } else {
                // Si aucune tâche avec un code 'SWT-' n'existe, on commence à 1
                $newCodeNumber = 1;
            }
            $newTache->setCode(sprintf('SWT-%06d', $newCodeNumber));
        
            // Générer le libellé et le code automatique pour les webtasks en fonction du champ libellé
            $lastTaskWithLabel = $entityManager->getRepository(Webtask::class)->createQueryBuilder('w')
                ->where('w.libelle LIKE :prefix')
                ->setParameter('prefix', 'SWTK%')
                ->orderBy('w.libelle', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($lastTaskWithLabel) {
                // On extrait le numéro à partir du libellé 'SWTK' et on l'incrémente
                $newTaskNumber = (int) substr($lastTaskWithLabel->getLibelle(), 6) + 1;
            } else {
                // Si aucune tâche avec un libellé 'SWTK' n'existe, on commence à 1
                $newTaskNumber = 1;
            }

            // Générer le nouveau libellé et webtask
            $newLibelleEtWebtask = sprintf('SWTK%06d', $newTaskNumber);
            $newTache->setLibelle($newLibelleEtWebtask);
            $newTache->setWebtask($newLibelleEtWebtask);

            // Récupérer le responsable par ID
            $responsableId = 'e4e0858e6cdaf9a4016ce33ebe5b008e'; // ID correct pour le responsable
            $responsable = $entityManager->getRepository(Responsable::class)->find($responsableId);

            if (!$responsable) {
                // Gérer l'erreur si le responsable n'est pas trouvé
                $this->addFlash('error', 'Responsable not found with ID: ' . $responsableId);
            } else {
                $newTache->setResponsable($responsable); // Assignation du responsable
            }

            // Récupérer le pilote par ID
            $piloteId = 'e4e087a587fba4670187fbbfa23e0021'; // ID du pilote
            $pilote = $entityManager->getRepository(User::class)->find($piloteId);

            if (!$pilote) {
                // Gérer l'erreur si le pilote n'est pas trouvé
                $this->addFlash('error', 'Pilote not found with ID: ' . $piloteId);
            } else {
                $newTache->setPiloteid($pilote); // Assignation du pilote
            }

            // Vérifier les documents attachés
            $documentsAttaches = [
                $request->request->get('lien_drive_1', ""),
                $request->request->get('lien_drive_2', ""),
                $request->request->get('lien_drive_3', ""),
            ];
            
            // Vérifiez si au moins un lien contient une valeur
            $areDocumentsAttaches = !empty(array_filter($documentsAttaches, fn($link) => $link !== ""));
            $newTache->setDocumentsAttaches($areDocumentsAttaches ? '1' : '0'); // Stocker comme chaîne

            // Récupérer la date d'échéance ou définir la date par défaut
            $dueDate = $request->request->get('due_date');
            if ($dueDate) {
                $dateObject = \DateTime::createFromFormat('Y-m-d', $dueDate);
                if ($dateObject) {
                    // Formater la date dans le format souhaité
                    $formattedDate = $dateObject->format('d/m/Y'); // Format 00/00/0000
                    $newTache->setDateFinDemandee($formattedDate);
                } else {
                    // Gérer l'erreur si le format est invalide
                    $newTache->setDateFinDemandee('00/00/0000'); // Valeur par défaut si le format est invalide
                }
            } else {
                // Si aucune date n'est fournie, définir une date par défaut
                $newTache->setDateFinDemandee('00/00/0000');
            }

            // Date et Heure de création et Créateur
            $currentDateTime = new \DateTime();
            $newTache->setCreeLe($currentDateTime->format('d/m/Y - H:i:s'));
            $newTache->setCreerPar($user->getPrenom() . ' ' . $user->getNom());
            $newTache->setEtatDeLaWebtask('ON');
            $newTache->setIdclient($client);
            $newTache->setTitre($request->request->get('title'));
            $newTache->setDescription($request->request->get('description'));
            $newTache->setTag($request->request->get('tag'));
            $newTache->setIddemandeur($user);
            $newTache->setAvancementDeLaTache('0');
            $newTache->setIdversion('e4e097dc8e55dcdf018e55fbc97500b1');
            $newTache->setetatVersion('FAISABILITÉ');
            $newTache->setBaseclient('SANS BASE');
            $newTache->setsylob5('0');
            $newTache->setOrdonnele(''); // ou une valeur par défaut spécifique
            $newTache->setNomDocExport('');

            // Générer le filtre
            $filter = sprintf(
                '%s_%s_%s',
                $currentDateTime->format('Ymd'),
                $currentDateTime->format('H:i'),
                'V00.00'
            );
            $newTache->setFiltre($filter);

            // Persister les entités
            $entityManager->persist($newTache);
            $entityManager->flush();

            // Récupérer tous les utilisateurs du client CABINET PAGOS
            $cabinetPagosClient = $entityManager->getRepository(Client::class)
            ->findOneBy(['raison_sociale' => 'CABINET PAGOS']);

            $usersCabinetPagos = $cabinetPagosClient
            ? $entityManager->getRepository(User::class)
                ->findBy(['idclient' => $cabinetPagosClient])
            : [];

            // Récupérer tous les utilisateurs du client actuel
            $usersCurrentClient = $entityManager->getRepository(User::class)
            ->findBy(['idclient' => $client]);

            // Fusionner les listes d'utilisateurs sans doublons
            $allUsers = array_unique(array_merge($usersCabinetPagos, $usersCurrentClient), SORT_REGULAR);

            // Créer une notification pour chaque utilisateur, sauf l'utilisateur connecté
            foreach ($allUsers as $userNotification) {
            if ($userNotification === $user) {
                continue; // Ignorer l'utilisateur connecté
            }

            $notification = new Notification();
            $notification->setMessage('Création de la WebTask : ' . $newTache->getLibelle());
            $notification->setLibelleWebtask($newTache->getLibelle());
            $notification->setDateCreation(new \DateTime());
            $notification->setVisible(true);
            $notification->setClient($newTache->getIdclient());
            $notification->setTitreWebtask($newTache->getTitre());
            $notification->setCodeWebtask($newTache->getCode());
            $notification->setUser($userNotification);

            $entityManager->persist($notification);
            }

            // Sauvegarder les notifications en base de données
            $entityManager->flush();

            return $this->redirectToRoute('app_taches');
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

        // Passer le lien Google Drive à la vue Twig
        return $this->render('Client/creertaches.html.twig', [
            'googleDriveLink' => $googleDriveLink,
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

    #[Route('/check-title', name: 'check_task_title', methods: ['GET'])]
    public function checkTaskTitle(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $title = $request->query->get('title');

        // Compter les tâches existantes avec un titre similaire
        $count = $entityManager->getRepository(Webtask::class)
            ->createQueryBuilder('w')
            ->select('COUNT(w.id)') // Compter le nombre d'entrées
            ->where('w.titre LIKE :title')
            ->setParameter('title', '%' . $title . '%')
            ->getQuery()
            ->getSingleScalarResult(); // Récupère un seul résultat scalaire

        return new JsonResponse([
            'exists' => $count > 0, // Renvoie true si le compte est supérieur à 0
        ]);
    }

    #[Route('/create-task', name: 'create_task')]
    public function createTask(Request $request): Response
    {
        // Créez une nouvelle instance de l'entité Webtask
        $webtask = new Webtask();

        // Récupérez la valeur du champ caché
        $documentsAttachesValue = $request->request->get('documents_attaches');

        // Mettez à jour l'entité
        $webtask->setDocumentsAttaches($documentsAttachesValue === "1");

        // Enregistrement de l'entité
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($webtask);
        $entityManager->flush();

        // Redirigez ou retournez une réponse
        return $this->redirectToRoute('success_page');
    }
}