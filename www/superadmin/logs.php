<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
include '../includes/dashboard-template.php';
// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

// Filtres
// Filtres
$filtre_action = $_GET['action'] ?? '';
$filtre_user = $_GET['user'] ?? '';
$filtre_date = $_GET['date'] ?? '';
$filtre_search = $_GET['search'] ?? '';

// Pagination et limite pour tous les logs
$limit_logs = isset($_GET['limit_logs']) ? intval($_GET['limit_logs']) : 10;
$page_logs = isset($_GET['page_logs']) ? max(1, intval($_GET['page_logs'])) : 1;
$offset_logs = ($page_logs - 1) * $limit_logs;

// Construction de la requête principale avec pagination
$sql = "SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role
        FROM logs l
        LEFT JOIN utilisateurs u ON l.user_id = u.id
        WHERE 1";
$params = [];
if ($filtre_action) {
    $sql .= " AND l.action = ?";
    $params[] = $filtre_action;
}
if ($filtre_user) {
    $sql .= " AND u.nom LIKE ?";
    $params[] = "%$filtre_user%";
}
if ($filtre_date) {
    $sql .= " AND DATE(l.timestamp) = ?";
    $params[] = $filtre_date;
}
if ($filtre_search) {
    $sql .= " AND (u.nom LIKE ? OR u.email LIKE ? OR l.message LIKE ? OR l.action LIKE ? OR l.type_cible LIKE ? OR l.target_id LIKE ?)";
    for ($i = 0; $i < 6; $i++) $params[] = "%$filtre_search%";
}
$sql_count = "SELECT COUNT(*) FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id WHERE 1";
if ($filtre_action) {
    $sql_count .= " AND l.action = '" . addslashes($filtre_action) . "'";
}
if ($filtre_user) {
    $sql_count .= " AND u.nom LIKE '%" . addslashes($filtre_user) . "%'";
}
if ($filtre_date) {
    $sql_count .= " AND DATE(l.timestamp) = '" . addslashes($filtre_date) . "'";
}
if ($filtre_search) {
    $search = addslashes($filtre_search);
    $sql_count .= " AND (u.nom LIKE '%$search%' OR u.email LIKE '%$search%' OR l.message LIKE '%$search%' OR l.action LIKE '%$search%' OR l.type_cible LIKE '%$search%' OR l.target_id LIKE '%$search%')";
}
$total_logs = $pdo->query($sql_count)->fetchColumn();
$sql .= " ORDER BY l.timestamp DESC LIMIT $limit_logs OFFSET $offset_logs";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Limite et pagination par défaut pour chaque tableau
$limit_default = 5;
$limit_conn = isset($_GET['limit_conn']) ? intval($_GET['limit_conn']) : $limit_default;
$page_conn = isset($_GET['page_conn']) ? max(1, intval($_GET['page_conn'])) : 1;
$offset_conn = ($page_conn - 1) * $limit_conn;

$limit_dl = isset($_GET['limit_dl']) ? intval($_GET['limit_dl']) : $limit_default;
$page_dl = isset($_GET['page_dl']) ? max(1, intval($_GET['page_dl'])) : 1;
$offset_dl = ($page_dl - 1) * $limit_dl;

$limit_consult = isset($_GET['limit_consult']) ? intval($_GET['limit_consult']) : $limit_default;
$page_consult = isset($_GET['page_consult']) ? max(1, intval($_GET['page_consult'])) : 1;
$offset_consult = ($page_consult - 1) * $limit_consult;

$limit_demande = isset($_GET['limit_demande']) ? intval($_GET['limit_demande']) : $limit_default;
$page_demande = isset($_GET['page_demande']) ? max(1, intval($_GET['page_demande'])) : 1;
$offset_demande = ($page_demande - 1) * $limit_demande;

// Compte total pour chaque tableau
$total_conn = $pdo->query("SELECT COUNT(*) FROM logs WHERE action IN ('login_succes', 'logout')")->fetchColumn();
$total_dl = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'telechargement'")->fetchColumn();
$total_consult = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'consultation'")->fetchColumn();
$total_demande = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'demande_acces'")->fetchColumn();

// Ajout du nom de fichier pour téléchargement et consultation
// Correction du nom de colonne pour le nom du fichier dans la table archives
// Remplace 'a.nom' par 'a.filename' ou 'a.titre' selon la structure réelle
$file_column = 'a.filename'; // à adapter si besoin
try {
    $stmt_dl = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, $file_column AS fichier_nom
        FROM logs l 
        LEFT JOIN utilisateurs u ON l.user_id = u.id 
        LEFT JOIN archives a ON l.target_id = a.id
        WHERE l.action = 'telechargement' 
        ORDER BY l.timestamp DESC 
        LIMIT $limit_dl OFFSET $offset_dl");
    $logs_dl = $stmt_dl->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la colonne filename n'existe pas, essaie titre
    $file_column = 'a.nom_fichier';
    $stmt_dl = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, $file_column AS fichier_nom
        FROM logs l 
        LEFT JOIN utilisateurs u ON l.user_id = u.id 
        LEFT JOIN archives a ON l.target_id = a.id
        WHERE l.action = 'telechargement' 
        ORDER BY l.timestamp DESC 
        LIMIT $limit_dl OFFSET $offset_dl");
    $logs_dl = $stmt_dl->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $stmt_consult = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, $file_column AS fichier_nom
        FROM logs l 
        LEFT JOIN utilisateurs u ON l.user_id = u.id 
        LEFT JOIN archives a ON l.target_id = a.id
        WHERE l.action = 'consultation' 
        ORDER BY l.timestamp DESC 
        LIMIT $limit_consult OFFSET $offset_consult");
    $logs_consult = $stmt_consult->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la colonne filename/titre n'existe pas, affiche '-'
    $logs_consult = [];
}

$stmt_conn = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role
    FROM logs l 
    LEFT JOIN utilisateurs u ON l.user_id = u.id 
    WHERE l.action IN ('login_succes', 'logout') 
    ORDER BY l.timestamp DESC 
    LIMIT $limit_conn OFFSET $offset_conn");
$logs_conn = $stmt_conn->fetchAll(PDO::FETCH_ASSOC);

$stmt_demande = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role
    FROM logs l 
    LEFT JOIN utilisateurs u ON l.user_id = u.id 
    WHERE l.action = 'demande_acces' 
    ORDER BY l.timestamp DESC 
    LIMIT $limit_demande OFFSET $offset_demande");
$logs_demande = $stmt_demande->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Logs et statistiques</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="bi bi-journal-text"></i> Logs et statistiques</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dashboard
        </a>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col">
            <input type="text" name="user" class="form-control" placeholder="Utilisateur" value="<?= htmlspecialchars($filtre_user) ?>">
        </div>
        <div class="col">
            <select name="action" class="form-select">
                <option value="">-- Action --</option>
                <option value="login_succes" <?= $filtre_action === 'login_succes' ? 'selected' : '' ?>>Connexion</option>
                <option value="logout" <?= $filtre_action === 'logout' ? 'selected' : '' ?>>Déconnexion</option>
                <option value="consultation" <?= $filtre_action === 'consultation' ? 'selected' : '' ?>>Consultation</option>
                <option value="telechargement" <?= $filtre_action === 'telechargement' ? 'selected' : '' ?>>Téléchargement</option>
                <option value="demande_acces" <?= $filtre_action === 'demande_acces' ? 'selected' : '' ?>>Demande d'accès</option>
                <option value="admin_action" <?= $filtre_action === 'admin_action' ? 'selected' : '' ?>>Action administrative</option>
            </select>
        </div>
        <div class="col">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filtre_date) ?>">
        </div>
        <div class="col">
            <input type="text" name="search" class="form-control" placeholder="Recherche rapide (mot-clé)">
        </div>
        <div class="col">
            <button class="btn btn-primary">Filtrer</button>
        </div>
        <div class="col">
            <a href="export_logs.php?user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
        </div>
    </form>
    <!-- Actions administratives -->
    <h4 class="mt-4"><i class="bi bi-person-badge"></i> Actions administratives</h4>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Date/Heure</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Cible</th>
                <th>Statut</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt_admin = $pdo->query("SELECT l.timestamp, u.nom AS admin_nom, l.action, l.type_cible AS cible, l.statut, l.ip_address
                FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id
                WHERE l.action LIKE 'admin_%' ORDER BY l.timestamp DESC LIMIT 10");
            foreach ($stmt_admin->fetchAll(PDO::FETCH_ASSOC) as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['admin_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['cible']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Alertes et logs critiques -->
    <h4 class="mt-4"><i class="bi bi-exclamation-triangle"></i> Alertes et logs critiques</h4>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-danger">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Message</th>
                <th>Statut</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt_alert = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id WHERE l.statut = 'bloque' OR l.action IN ('tentative_suspecte', 'acces_expire', 'telechargement_refuse') ORDER BY l.timestamp DESC LIMIT 10");
            foreach ($stmt_alert->fetchAll(PDO::FETCH_ASSOC) as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-person-check"></i> Connexions / Déconnexions</h4>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_conn" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= $limit_conn == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit_conn == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $limit_conn == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $limit_conn == 50 ? 'selected' : '' ?>>50</option>
            </select> dernières connexions
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $max_page_conn = ceil($total_conn / $limit_conn); ?>
            <?php for ($i = 1; $i <= $max_page_conn; $i++): ?>
                <li class="page-item <?= $i == $page_conn ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Action</th>
                <th>Status</th>
                <th>Message</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs_conn as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td>
                        <?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?><br>
                        <small><?= htmlspecialchars($log['utilisateur_email'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($log['utilisateur_role'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-file-earmark-arrow-down"></i> Téléchargements</h4>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_dl" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= $limit_dl == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit_dl == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $limit_dl == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $limit_dl == 50 ? 'selected' : '' ?>>50</option>
            </select> derniers téléchargements
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $max_page_dl = ceil($total_dl / $limit_dl); ?>
            <?php for ($i = 1; $i <= $max_page_dl; $i++): ?>
                <li class="page-item <?= $i == $page_dl ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Nom du fichier</th>
                <th>ID</th>
                <th>Status</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs_dl as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?><br><small><?= htmlspecialchars($log['utilisateur_email'] ?? '') ?></small></td>
                    <td><?= htmlspecialchars($log['utilisateur_role'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['fichier_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['target_id']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-eye"></i> Consultations</h4>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_consult" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= $limit_consult == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit_consult == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $limit_consult == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $limit_consult == 50 ? 'selected' : '' ?>>50</option>
            </select> dernières consultations
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $max_page_consult = ceil($total_consult / $limit_consult); ?>
            <?php for ($i = 1; $i <= $max_page_consult; $i++): ?>
                <li class="page-item <?= $i == $page_consult ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Nom du fichier</th>
                <th>ID</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs_consult as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?><br><small><?= htmlspecialchars($log['utilisateur_email'] ?? '') ?></small></td>
                    <td><?= htmlspecialchars($log['utilisateur_role'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['fichier_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['target_id']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-envelope-paper"></i> Demandes d'accès</h4>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_demande" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= $limit_demande == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit_demande == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $limit_demande == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $limit_demande == 50 ? 'selected' : '' ?>>50</option>
            </select> dernières demandes
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $max_page_demande = ceil($total_demande / $limit_demande); ?>
            <?php for ($i = 1; $i <= $max_page_demande; $i++): ?>
                <li class="page-item <?= $i == $page_demande ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Fichier</th>
                <th>ID</th>
                <th>Statut</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs_demande as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?><br><small><?= htmlspecialchars($log['utilisateur_email'] ?? '') ?></small></td>
                    <td><?= htmlspecialchars($log['utilisateur_role'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['target_id']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Suppression du tableau logs filtrés -->

    <h4 class="mt-4"><i class="bi bi-journal-text"></i> Tous les logs</h4>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_logs" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= $limit_logs == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit_logs == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $limit_logs == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $limit_logs == 50 ? 'selected' : '' ?>>50</option>
            </select> logs par page
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $max_page_logs = ceil($total_logs / $limit_logs); ?>
            <?php for ($i = 1; $i <= $max_page_logs; $i++): ?>
                <li class="page-item <?= $i == $page_logs ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Action</th>
                <th>Cible</th>
                <th>Status</th>
                <th>Message</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td>
                        <?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?><br>
                        <small><?= htmlspecialchars($log['utilisateur_email'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($log['utilisateur_role'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['type_cible']) ?> #<?= htmlspecialchars($log['target_id']) ?></td>
                    <td><?= htmlspecialchars($log['statut']) ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>