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
            z-index: 1040;
            transition: transform 0.3s ease;
        }
        .sidebar[aria-hidden="true"] {
            transform: translateX(-100%);
        }
        .sidebar[aria-hidden="false"] {
            transform: none;
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
        .sidebar a:hover, .sidebar a.active {
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
            transition: margin-left 0.3s;
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
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #0d6efd;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            margin-right: 10px;
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .burger {
            display: none;
            background: none;
            border: none;
            color: #0d6efd;
            font-size: 2rem;
            margin-right: 10px;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1039;
        }
        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                margin-left: 0;
            }
            .sidebar[aria-hidden="false"] {
                transform: none;
            }
            .content {
                margin-left: 0;
                width: 100%;
            }
            .burger {
                display: inline-block;
            }
            .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar" aria-label="Menu principal" aria-hidden="true" tabindex="-1">
        <h4 style="color: white"><i class="bi bi-archive-fill me-2"></i>BNB Archives</h4>
        <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="bi bi-house"></i> Accueil</a>
        <hr>
        <?php if ($user['role'] === 'secretaire'): ?>
            <a href="ajouter-employe.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ajouter-employe.php' ? 'active' : '' ?>"><i class="bi bi-person-plus"></i> Ajouter Employé</a>
            <a href="liste-employes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'liste-employes.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Liste des Employés</a>
            <a href="archiver.php" class="<?= basename($_SERVER['PHP_SELF']) === 'archiver.php' ? 'active' : '' ?>"><i class="bi bi-file-earmark-arrow-up"></i> Archiver Documents</a>
            <a href="recherche.php" class="<?= basename($_SERVER['PHP_SELF']) === 'recherche.php' ? 'active' : '' ?>"><i class="bi bi-search"></i> Rechercher un document</a>
            <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
              <i class="bi bi-bell"></i> Notifications
              <?php if ($notif_count > 0): ?><span class="badge" aria-label="Notifications non lues"><?= $notif_count ?></span><?php endif; ?>
            </a>
            <a href="demandes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'demandes.php' ? 'active' : '' ?>"><i class="bi bi-inbox"></i> Demandes en cours</a>
        <?php elseif ($user['role'] === 'employe'): ?>
            <a href="envoyer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'envoyer.php' ? 'active' : '' ?>"><i class="bi bi-send"></i> Envoyer un document</a>
            <a href="recherche.php" class="<?= basename($_SERVER['PHP_SELF']) === 'recherche.php' ? 'active' : '' ?>"><i class="bi bi-search"></i> Rechercher</a>
            <a href="autorisation.php" class="<?= basename($_SERVER['PHP_SELF']) === 'autorisation.php' ? 'active' : '' ?>"><i class="bi bi-shield-check"></i> Demander autorisation</a>
        <?php elseif ($user['role'] === 'ag'): ?>
            <a href="envoyer-ag.php" class="<?= basename($_SERVER['PHP_SELF']) === 'envoyer-ag.php' ? 'active' : '' ?>"><i class="bi bi-send-plus"></i> Envoyer Document</a>
            <a href="liste-archives.php" class="<?= basename($_SERVER['PHP_SELF']) === 'liste-archives.php' ? 'active' : '' ?>"><i class="bi bi-archive"></i> Voir tous les fichiers</a>
            <a href="recherche-ag.php" class="<?= basename($_SERVER['PHP_SELF']) === 'recherche-ag.php' ? 'active' : '' ?>"><i class="bi bi-search"></i> Rechercher un document</a>
            <a href="autoriser-acces.php" class="<?= basename($_SERVER['PHP_SELF']) === 'autoriser-acces.php' ? 'active' : '' ?>"><i class="bi bi-check2-square"></i> Autoriser accès</a>

            <a href="liste-a.php" class="<?= basename($_SERVER['PHP_SELF']) === 'liste-a.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Liste des Associés</a>
            <a href="liste-employes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'liste-employes.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> Liste des Employés</a>
        <?php endif; ?>
        <hr>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" tabindex="-1" aria-hidden="true"></div>

    <div class="content">
        <div class="topbar">
            <button class="burger" id="burgerBtn" aria-label="Ouvrir le menu" tabindex="0"><i class="bi bi-list"></i></button>
            <h5 class="mb-0">Bienvenue, <?= htmlspecialchars($user['nom']) ?> (<?= $user['role'] ?>)</h5>
            <div class="topbar-user">
                <span class="avatar" aria-label="Avatar utilisateur">
                    <?php
                    $initials = '';
                    $parts = explode(' ', $user['nom']);
                    foreach($parts as $p) $initials .= mb_strtoupper(mb_substr($p,0,1));
                    echo htmlspecialchars($initials);
                    ?>
                </span>
                <span><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></span>
                <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2" title="Déconnexion"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
</head>
<script>
// Sidebar responsive mobile
document.addEventListener('DOMContentLoaded', function(){
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const burger = document.getElementById('burgerBtn');
    function openSidebar() {
        sidebar.setAttribute('aria-hidden', 'false');
        overlay.style.display = 'block';
        sidebar.focus();
    }
    function closeSidebar() {
        sidebar.setAttribute('aria-hidden', 'true');
        overlay.style.display = 'none';
    }
    function updateSidebarDisplay() {
        if(window.innerWidth > 900) {
            sidebar.setAttribute('aria-hidden', 'false');
            overlay.style.display = 'none';
        } else {
            sidebar.setAttribute('aria-hidden', 'true');
            overlay.style.display = 'none';
        }
    }
    burger && burger.addEventListener('click', openSidebar);
    overlay && overlay.addEventListener('click', closeSidebar);
    // Fermer avec Echap
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') closeSidebar();
    });
    // Accessibilité: fermer sidebar si focus sort
    sidebar && sidebar.addEventListener('focusout', function(e){
        if(window.innerWidth <= 900 && !sidebar.contains(e.relatedTarget)) closeSidebar();
    });
    // Gérer le resize pour afficher/masquer la sidebar selon la largeur
    window.addEventListener('resize', updateSidebarDisplay);
    // Initialisation
    updateSidebarDisplay();
});
</script>
