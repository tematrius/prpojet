<?php
require_once 'auth.php';
secure_session();
$user = $_SESSION['user'];
require 'db.php';

// Compteur notifications (si secrétaire)
$notif_count = 0;
if ($user['role'] === 'secretaire') {
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM documents WHERE etat = 'en_attente'");
    $docs_to_archive = $stmt1->fetchColumn();

    try {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM demandes WHERE statut = 'en_attente'&& soumis_ag = 0");
        $demandes = $stmt2->fetchColumn();
    } catch (PDOException $e) {
        $demandes = 0;
    }

    $notif_count = $docs_to_archive + $demandes;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BNB Archives</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Dropzone CSS + JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }
        .sidebar h4 {
            font-weight: bold;
        }
        .sidebar a {
            color: white;
            display: flex;
            align-items: center;
            margin: 12px 0;
            text-decoration: none;
            padding: 8px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            position: relative;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .sidebar a .badge {
            background: red;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            position: absolute;
            top: 6px;
            right: 10px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            height: 100vh;
            overflow-y: auto;
            width: calc(100% - 250px);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4><i class="bi bi-archive-fill me-2"></i>BNB Archives</h4>
        <hr>
        <?php if ($user['role'] === 'secretaire'): ?>
            <a href="ajouter-employe.php"><i class="bi bi-person-plus"></i> Ajouter Employé</a>
            <a href="liste-employes.php"><i class="bi bi-people"></i> Liste des Employés</a>
            <a href="archiver.php"><i class="bi bi-file-earmark-arrow-up"></i> Archiver Documents</a>
            <a href="recherche.php"><i class="bi bi-search"></i> Rechercher un document</a>
            <a href="notifications.php">
              <i class="bi bi-bell"></i> Notifications
              <?php if ($notif_count > 0): ?><span class="badge"><?= $notif_count ?></span><?php endif; ?>
            </a>
            <a href="demandes.php"><i class="bi bi-inbox"></i> Demandes en cours</a>
        <?php elseif ($user['role'] === 'employe'): ?>
            <a href="envoyer.php"><i class="bi bi-send"></i> Envoyer un document</a>
            <a href="recherche.php"><i class="bi bi-search"></i> Rechercher</a>
            <a href="autorisation.php"><i class="bi bi-shield-check"></i> Demander autorisation</a>
        <?php elseif ($user['role'] === 'ag'): ?>
            <a href="envoyer-ag.php"><i class="bi bi-send-plus"></i> Envoyer Document</a>
            <a href="liste-archives.php"><i class="bi bi-archive"></i> Voir tous les fichiers</a>
            <a href="recherche-ag.php"><i class="bi bi-search"></i> Rechercher un document</a>
            <a href="autoriser-acces.php"><i class="bi bi-check2-square"></i> Autoriser accès</a>
            <a href="ajouter-a.php"><i class="bi bi-person-plus"></i> Ajouter Associé</a>
            <a href="liste-a.php"><i class="bi bi-people"></i> Liste des Associés</a>
            <a href="liste-employes.php"><i class="bi bi-people"></i> Liste des Employés</a>
        <?php endif; ?>
        <hr>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </div>

    <div class="content">
        <div class="topbar">
            <h5>Bienvenue, <?= htmlspecialchars($user['nom']) ?> (<?= $user['role'] ?>)</h5>
            <span><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></span>
        </div>
