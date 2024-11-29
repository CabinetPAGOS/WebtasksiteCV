<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Webtask;
use App\Entity\Client;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportExportController extends AbstractController
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

    #[Route('/admin/importExport', name: 'app_importexport')]
    public function importexport(Request $request, NotificationRepository $notificationRepository): Response
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

        return $this->render('Admin/importExport.html.twig', [
            'logo' => $logo,
            'notifications' => $notifications,
        ]);
    }

    #[Route('/admin/import-clients', name: 'app_import_clients', methods: ['POST'])]
    public function importClients(Request $request, Connection $conn): Response
    {
        // Début du processus d'importation
        $this->addFlash('info', 'Début de l\'importation des clients.');

        if (!$request->isMethod('POST')) {
            $this->addFlash('error', 'Méthode non autorisée. Veuillez utiliser POST pour importer des clients.');
            return $this->redirectToRoute('app_importexport');
        }

        // Dossier d'entrée et d'archive
        $inputDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/client/in/';
        $archiveDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/client/archive/';

        // Récupération de tous les fichiers CSV
        $csvFiles = glob($inputDir . '*.csv');
        $csvFiles = array_merge($csvFiles, glob($inputDir . '*.CSV'));

        if (empty($csvFiles)) {
            $this->addFlash('error', 'Aucun fichier CSV trouvé dans le dossier d\'importation.');
            return $this->redirectToRoute('app_importexport');
        }

        foreach ($csvFiles as $local_file) {
            if (filesize($local_file) == 0) {
                $this->addFlash('error', "Le fichier CSV est vide : " . basename($local_file));
                continue;
            }

            // Nettoyage du fichier CSV
            $path_info = pathinfo($local_file);
            $output_file = $path_info['dirname'] . '/' . $path_info['filename'] . '_clean.' . $path_info['extension'];

            $handle_in = fopen($local_file, 'r');
            $handle_out = fopen($output_file, 'w');

            if ($handle_in === false || $handle_out === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier CSV : ' . basename($local_file));
                continue;
            }

            while (($data = fgetcsv($handle_in, 100000, ";")) !== false) {
                if (count($data) >= 6) {
                    $data = array_slice($data, 0, 6);
                    foreach ($data as &$field) {
                        $field = str_replace('<cr/>', "\n", $field);
                        $field = str_replace('"', '', $field);
                        $field = trim($field);
                    }
                    fputcsv($handle_out, $data, ";");
                }
            }

            fclose($handle_in);
            fclose($handle_out);

            // Insertion en base de données
            $handle_clean = fopen($output_file, 'r');
            if ($handle_clean === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier nettoyé : ' . basename($output_file));
                continue;
            }

            $insertQuery = 'INSERT IGNORE INTO client (
                id, code, raison_sociale, webtask_ouverture_contact, google_drive_webtask, logo
            ) VALUES (?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($insertQuery);
            $rowCount = 0;

            while (($data = fgetcsv($handle_clean, 100000, ";")) !== false) {
                if (count($data) == 6) {
                    try {
                        $stmt->execute($data);
                        $rowCount++;
                    } catch (\Exception $e) {
                        // Erreur lors de l'insertion (exemple : ID déjà existant)
                        $this->addFlash('error', 'Erreur lors de l\'insertion du client ' . $data[1] . ' : ' . $e->getMessage());
                    }
                }
            }

            fclose($handle_clean);
            $this->addFlash('success', "$rowCount clients importés avec succès depuis le fichier " . basename($local_file));

            // Déplacement des fichiers vers le dossier d'archive
            if (!rename($local_file, $archiveDir . basename($local_file))) {
                $this->addFlash('error', 'Erreur lors du déplacement du fichier original vers l\'archive.');
            }

            if (!rename($output_file, $archiveDir . basename($output_file))) {
                $this->addFlash('error', 'Erreur lors du déplacement du fichier nettoyé vers l\'archive.');
            }
        }

        // Fin du processus d'importation
        $this->addFlash('info', 'Importation des fichiers CSV terminée.');
        return $this->redirectToRoute('app_importexport');
    }

    #[Route('/admin/import-users', name: 'app_import_users', methods: ['POST'])]
    public function importUsers(Request $request, Connection $conn): Response
    {
        // Début du processus d'importation
        $this->addFlash('info', 'Début de l\'importation des utilisateurs.');

        if (!$request->isMethod('POST')) {
            $this->addFlash('error', 'Méthode non autorisée. Veuillez utiliser POST pour importer des utilisateurs.');
            return $this->redirectToRoute('app_importexport');
        }

        // Dossier d'entrée et d'archive
        $inputDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/utilisateur/in/';
        $archiveDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/utilisateur/archive/';

        // Récupération de tous les fichiers CSV
        $csvFiles = glob($inputDir . '*.csv');
        $csvFiles = array_merge($csvFiles, glob($inputDir . '*.CSV'));

        if (empty($csvFiles)) {
            $this->addFlash('error', 'Aucun fichier CSV trouvé dans le dossier d\'importation des utilisateurs.');
            return $this->redirectToRoute('app_importexport');
        }

        foreach ($csvFiles as $local_file) {
            if (filesize($local_file) == 0) {
                $this->addFlash('error', "Le fichier CSV est vide : " . basename($local_file));
                continue;
            }

            // Nettoyage du fichier CSV
            $path_info = pathinfo($local_file);
            $output_file = $path_info['dirname'] . '/' . $path_info['filename'] . '_clean.' . $path_info['extension'];

            $handle_in = fopen($local_file, 'r');
            $handle_out = fopen($output_file, 'w');

            if ($handle_in === false || $handle_out === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier CSV : ' . basename($local_file));
                continue;
            }

            // Nettoyage des données et écriture dans un nouveau fichier
            while (($data = fgetcsv($handle_in, 100000, ";")) !== false) {
                if (count($data) >= 10) {
                    $data = array_slice($data, 0, 10); // Limiter aux 10 colonnes nécessaires
                    foreach ($data as &$field) {
                        $field = str_replace('<cr/>', "\n", $field); // Nettoyage des balises
                        $field = trim($field); // Nettoyage des espaces superflus
                    }
                    fputcsv($handle_out, $data, ";");
                }
            }

            fclose($handle_in);
            fclose($handle_out);

            // Insertion en base de données
            $handle_clean = fopen($output_file, 'r');
            if ($handle_clean === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier nettoyé : ' . basename($output_file));
                continue;
            }

            $insertQuery = 'INSERT IGNORE INTO user (
                id, nom, prenom, email, password, roles, webtask_ouverture_contact, depart_entreprise, idclient_id, must_reset_password
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($insertQuery);
            $rowCount = 0;

            while (($data = fgetcsv($handle_clean, 100000, ";")) !== false) {
                if (count($data) == 10) {
                    try {
                        $stmt->execute($data);
                        $rowCount++;
                    } catch (\Exception $e) {
                        // Erreur lors de l'insertion (exemple : ID déjà existant)
                        $this->addFlash('error', 'Erreur lors de l\'insertion de l\'utilisateur ' . $data[3] . ' : ' . $e->getMessage());
                    }
                }
            }

            fclose($handle_clean);
            $this->addFlash('success', "$rowCount utilisateurs importés avec succès depuis le fichier " . basename($local_file));

            // Déplacement des fichiers vers le dossier d'archive
            if (!rename($local_file, $archiveDir . basename($local_file))) {
                $this->addFlash('error', 'Erreur lors du déplacement du fichier original vers l\'archive.');
            }

            if (!rename($output_file, $archiveDir . basename($output_file))) {
                $this->addFlash('error', 'Erreur lors du déplacement du fichier nettoyé vers l\'archive.');
            }
        }

        // Fin du processus d'importation
        $this->addFlash('info', 'Importation des fichiers CSV terminée.');
        return $this->redirectToRoute('app_importexport');
    }
    
    #[Route('/admin/import-webtasks', name: 'app_import_webtasks', methods: ['POST'])]
    public function importWebTasks(Request $request, Connection $conn, EntityManagerInterface $entityManager): Response
    {
        if (!$request->isMethod('POST')) {
            $this->addFlash('error', 'La méthode de requête n\'est pas POST');
            return $this->redirectToRoute('app_importexport');
        }
    
        // Dossier d'entrée et d'archive
        $inputDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/webtask/in/';
        $archiveDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/webtask/archive/';
    
        // Récupérer tous les fichiers CSV dans le dossier d'entrée (inclut csv et CSV)
        $csvFiles = glob($inputDir . '*.csv');
        $csvFiles = array_merge($csvFiles, glob($inputDir . '*.CSV'));
    
        // Si aucun fichier CSV n'est trouvé
        if (empty($csvFiles)) {
            $this->addFlash('error', 'Aucun fichier CSV trouvé dans le dossier d\'entrée.');
            return $this->redirectToRoute('app_importexport');
        }
    
        // Supprimer toutes les données de la table webtask
        $conn->executeStatement('DELETE FROM webtask');

        foreach ($csvFiles as $local_file) {
            // Vérifier si le fichier est vide
            if (filesize($local_file) == 0) {
                $this->addFlash('error', "Le fichier CSV est vide : " . basename($local_file));
                continue;
            }
    
            // Nettoyage du fichier CSV
            $path_info = pathinfo($local_file);
            $output_file = $path_info['dirname'] . '/' . $path_info['filename'] . '_clean.' . $path_info['extension'];
    
            $handle_in = fopen($local_file, 'r');
            if ($handle_in === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier CSV d\'entrée : ' . basename($local_file));
                continue;
            }
    
            $handle_out = fopen($output_file, 'w');
            if ($handle_out === false) {
                $this->addFlash('error', 'Erreur lors de la création du fichier CSV de sortie : ' . basename($local_file));
                fclose($handle_in);
                continue;
            }
    
            // Parcourir chaque ligne du fichier CSV d'entrée
            while (($data = fgetcsv($handle_in, 100000, ";")) !== false) {
                $data = array_slice($data, 0, 37); // Limiter à 37 colonnes
    
                foreach ($data as &$field) {
                    $field = str_replace('<cr/>', "\n", $field);
                    $field = str_replace('"', '', $field); // Enlève les guillemets
                    $field = trim($field); // Supprime les espaces superflus

                    // Convertir les valeurs booléennes en 0 ou 1
                    if ($field === 'true' || $field === true) {
                        $field = 1;
                    } elseif ($field === 'false' || $field === false) {
                        $field = 0;
                    }
                }
    
                fputcsv($handle_out, $data, ";");
            }
    
            fclose($handle_in);
            fclose($handle_out);
    
            // ---- Insertion en base de données ----
            $handle_clean = fopen($output_file, 'r');
            if ($handle_clean === false) {
                $this->addFlash('error', 'Erreur lors de l\'ouverture du fichier CSV nettoyé : ' . basename($local_file));
                continue;
            }
    
            $insertQuery = 'INSERT INTO webtask (
                code, libelle, idclient_id, titre, webtask, description, entite, tag, iddemandeur_id,
                responsable_id, piloteid_id, estimation_temps, date_fin_demandee, avancement_de_la_tache,
                demande_de_recettage, commentaire_webtask_client, commentaireinternepagos, documents_attaches,
                etat_de_la_webtask, lien_drive_1, lien_drive_2, lien_drive_3, archiver, ordonnele, ordre,
                recommandations, idversion, etat_version, idtracabilite, webtask_mere, filtre, baseclient, sylob5, cree_le, creer_par, nom_doc_export
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($insertQuery);
            
            $rowCount = 0;
            while (($data = fgetcsv($handle_clean, 100000, ";")) !== false) {
                if (count($data) == 37) { // Vérifie que la ligne contient 37 colonnes dans le fichier CSV
                    $code = $data[1]; // Le code est la deuxième colonne du fichier CSV
                    
                    // Vérifie si le code existe déjà dans la base
                    $existingWebtask = $conn->fetchOne('SELECT COUNT(*) FROM webtask WHERE code = ?', [$code]);
                    if ($existingWebtask > 0) {
                        continue; // Si le code existe déjà, ignore cette ligne
                    }
                    
                    // Retire la première colonne (id) pour correspondre aux 36 champs restants
                    $data = array_slice($data, 1);
                    
                    if (count($data) == 36) { // Vérifie après suppression qu'il reste bien 36 colonnes
                        try {
                            $stmt->execute($data);
                            $rowCount++;
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Erreur d\'insertion pour la ligne ' . $rowCount . ' : ' . $e->getMessage());
                        }
                    } else {
                        $this->addFlash('error', 'Erreur : la ligne ne contient pas 36 colonnes après suppression de l\'id.');
                    }
                } else {
                    $this->addFlash('error', 'Erreur : la ligne ne contient pas 37 colonnes.');
                }
            }
            fclose($handle_clean);
            $this->addFlash('success', "$rowCount WebTasks importés avec succès depuis le fichier " . basename($local_file));
    
            // Déplacer les fichiers CSV vers le dossier d'archive
            if (file_exists($local_file)) {
                $localFileArchived = $archiveDir . basename($local_file);
                rename($local_file, $localFileArchived);
            }
    
            if (file_exists($output_file)) {
                $outputFileArchived = $archiveDir . basename($output_file);
                rename($output_file, $outputFileArchived);
            }

             // ---- Création de notifications excluant le CABINET PAGOS ----
            $webtasks = $entityManager->getRepository(WebTask::class)->findAll();

            foreach ($webtasks as $webtask) {
                if ($webtask->getCommentaireWebtaskClient() && 
                    !$entityManager->getRepository(Notification::class)->findOneBy(['codeWebtask' => $webtask->getCode()])
                ) {
                    $client = $webtask->getIdclient();
                    $users = $entityManager->getRepository(User::class)->findBy(['idclient' => $client]);

                    foreach ($users as $user) {
                        if (strpos($client->getRaisonSociale(), 'CABINET PAGOS') === false) { // Vérifie que le client n'est pas "CABINET PAGOS"
                            $notification = new Notification();
                            $notification->setMessage('Nouvelle WebTask : ' . $webtask->getTitre());
                            $notification->setTitreWebtask($webtask->getTitre());
                            $notification->setLibelleWebtask($webtask->getLibelle());
                            $notification->setCodeWebtask($webtask->getCode());
                            $notification->setDateCreation(new \DateTime());
                            $notification->setClient($client);
                            $notification->setUser($user);
                            $notification->setVisible(true);
                            $entityManager->persist($notification);
                        }
                    }
                }
            }
            $entityManager->flush();
        }
    
        // Fin du processus d'importation, avec un seul message de succès
        $this->addFlash('info', 'Importation des fichiers CSV terminée.');
        return $this->redirectToRoute('app_importexport');
    } 
    
    #[Route('/export/webtasks', name: 'app_export_webtasks')]
    public function exportWebtasks(): Response
    {
        // Utiliser le service entityManager injecté
        $webtaskRepository = $this->entityManager->getRepository(Webtask::class);
        
        // Trouver toutes les webtasks avec sylob5 = 0 et nomDocExport non renseigné
        $webtasks = $webtaskRepository->createQueryBuilder('w')
            ->where('w.sylob5 = :sylob5')
            ->andWhere('w.nomDocExport IS NULL OR w.nomDocExport = :empty')
            ->setParameter('sylob5', 0)
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        // Spécifier le chemin du dossier où le fichier sera enregistré
        $outputDir = $this->getParameter('kernel.project_dir') . '/public/WEBTASK/webtask/out/';

        // Obtenir la date et l'heure actuelles pour le nom du fichier
        $dateTime = new \DateTime();
        $formattedDate = $dateTime->format('Ymd_Hi'); // YYYYMMDD_HHMM
        $outputFile = $outputDir . 'WEBTASK' . $formattedDate . '.csv';

        // Créer un fichier CSV
        $handle = fopen($outputFile, 'w+');

        // Parcourir les webtasks et écrire les données dans le fichier CSV
        foreach ($webtasks as $webtask) {
            // Récupérer les données nécessaires
            $data = [
                'WEBTASK', // Afficher "WEBTASK" en chaîne de caractères
                $webtask->getCode(),
                $webtask->getLibelle(),
                $webtask->getIdclient() ? $webtask->getIdclient()->getId() : null, // ID Client
                $webtask->getTitre(),
                $webtask->getWebtask(),
                // Remplacer les sauts de ligne par <cr/>
                str_replace(["\n", "\r"], '<cr/>', $webtask->getDescription()),
                $webtask->getEntite(),
                $webtask->getTag(),
                $webtask->getIddemandeur() ? $webtask->getIddemandeur()->getId() : null, // ID Demandeur
                $webtask->getResponsable() ? $webtask->getResponsable()->getId() : null, // ID Responsable
                $webtask->getPiloteid() ? $webtask->getPiloteid()->getId() : null, // ID Pilote
                $webtask->getEstimationTemps(),
                $webtask->getDatefinDemandee(), // Date de fin demandée
                $webtask->getAvancementdelatache(),
                $webtask->getDemandedeRecettage(),
                // Remplacer les sauts de ligne par <cr/>
                str_replace(["\n", "\r"], '<cr/>', $webtask->getCommentaireWebtaskClient()),
                // Remplacer les sauts de ligne par <cr/>
                str_replace(["\n", "\r"], '<cr/>', $webtask->getCommentaireinternepagos()),
                $webtask->getDocumentsAttaches(),
                $webtask->getEtatDeLaWebtask(),
                $webtask->getLienDrive1(),
                $webtask->getLienDrive2(),
                $webtask->getLienDrive3(),
                $webtask->getArchiver(), // Si archiver est un booléen
                $webtask->getOrdonnele(),
                $webtask->getOrdre(),
                $webtask->getRecommandations(),
                $webtask->getIdversion(), // ID Version
                $webtask->getEtatVersion(),
                $webtask->getIdtracabilite(),
                $webtask->getWebtaskMere(),
                $webtask->getFiltre(),
                $webtask->getBaseclient(),
                $webtask->getSylob5(), // Remplace idsiteinternet par sylob5
                $webtask->getCreeLe(), // Date de création
                $webtask->getCreerPar(),
                'WEBTASK' . $formattedDate . '.csv' // Ajouter le nom du fichier CSV dans les données
            ];

            // Écrire les données sur une seule ligne avec le séparateur ';'
            fwrite($handle, implode(';', $data) . ';' . PHP_EOL);

            // Mettre à jour le champ nomDocExport avec le nom du fichier CSV
            $webtask->setNomDocExport('WEBTASK' . $formattedDate . '.csv');
        }

        fclose($handle);

        // Enregistrer les modifications dans la base de données
        $this->entityManager->flush();

        // Ajouter un message flash pour indiquer que l'exportation a réussi
        $this->addFlash('success', 'Les WebTask ont été exportées dans le dossier /public/WEBTASK/webtask/out/ sous le nom ' . 'WEBTASK' . $formattedDate . '.csv');

        // Rediriger vers une page après l'exportation
        return $this->redirectToRoute('app_importexport'); // Remplace 'app_importexport' par la route souhaitée
    }

    #[Route('/notifications/reset-visibility', name: 'reset_notifications_visibility', methods: ['POST'])]
    public function resetVisibility(EntityManagerInterface $entityManager): Response
    {
        // Exécutez une requête pour mettre le champ visible à 0
        $query = $entityManager->createQuery('UPDATE App\Entity\Notification n SET n.visible = 0');
        $query->execute();

        // Ajoutez un message flash ou une autre méthode de confirmation
        $this->addFlash('success', 'Toutes les notifications ont été mises à jour avec succès.');

        // Redirigez vers la page actuelle (ou une autre)
        return $this->redirectToRoute('app_importexport');
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
        $notification->setVisible(false);

        // Enregistrer les modifications
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }
}