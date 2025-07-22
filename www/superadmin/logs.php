<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
include '../includes/dashboard-template.php';
// Ajout du lien BNB Archive dans la sidebar
ob_start();
// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

// Filtres
$sort_conn = $_GET['sort_conn'] ?? 'timestamp';
$order_conn = $_GET['order_conn'] ?? 'desc';
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
$params_count = [];
if ($filtre_action) {
    $sql_count .= " AND l.action = ?";
    $params_count[] = $filtre_action;
}
if ($filtre_user) {
    $sql_count .= " AND u.nom LIKE ?";
    $params_count[] = "%$filtre_user%";
}
if ($filtre_date) {
    $sql_count .= " AND DATE(l.timestamp) = ?";
    $params_count[] = $filtre_date;
}
if ($filtre_search) {
    $sql_count .= " AND (u.nom LIKE ? OR u.email LIKE ? OR l.message LIKE ? OR l.action LIKE ? OR l.type_cible LIKE ? OR l.target_id LIKE ?)";
    for ($i = 0; $i < 6; $i++) $params_count[] = "%$filtre_search%";
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_logs = $stmt_count->fetchColumn();
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
$file_column = 'a.nom_fichier'; // à adapter si besoin
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

$allowed_conn = ['timestamp','utilisateur_nom','utilisateur_role','action','statut','message','ip_address'];
$sort_col_conn = in_array($sort_conn, $allowed_conn) ? $sort_conn : 'timestamp';
$order_dir_conn = strtolower($order_conn) === 'asc' ? 'ASC' : 'DESC';
$stmt_conn = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role
    FROM logs l 
    LEFT JOIN utilisateurs u ON l.user_id = u.id 
    WHERE l.action IN ('login_succes', 'logout') 
    ORDER BY $sort_col_conn $order_dir_conn 
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
        font-family: 'Inter', 'Nunito', Arial, sans-serif;
      }
      .container {
        max-width: 1200px;
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        padding: 2.2rem 2rem 2rem 2rem;
        margin-top: 2.5rem;
      }
      h2 {
        font-weight: 800;
        color: #dc3545;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px #dc354511;
      }
      h4 {
        font-weight: 700;
        color: #0d6efd;
        margin-top: 2.2rem;
        margin-bottom: 1.2rem;
      }
      .form-control, .form-select {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        padding: 0.7rem 1rem;
        box-shadow: 0 1px 4px #0d6efd13;
        border: 1px solid #e3e6f3;
        transition: border 0.18s;
      }
      .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px #0d6efd33;
      }
      .btn-primary {
        background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
        color: #fff !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #0d6efd33;
        border: none !important;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-primary:hover {
        background: linear-gradient(135deg, #6f42c1 80%, #0d6efd 100%) !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-success {
        background: #fff !important;
        color: #198754 !important;
        border: 2px solid #198754 !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #19875433;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-success:hover {
        background: #198754 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-secondary {
        background: #fff !important;
        color: #0d6efd !important;
        border: 2px solid #0d6efd !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #0d6efd33;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-secondary:hover {
        background: #0d6efd !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .table {
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        overflow: hidden;
        background: #fff;
      }
      thead th {
        background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
        color: #fff !important;
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: 0.3px;
        border: none !important;
      }
      .table-danger th {
        background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%) !important;
        color: #fff !important;
        border: none !important;
      }
      tbody tr {
        transition: box-shadow 0.18s, transform 0.18s, background 0.18s;
        animation: rowFadeIn 0.7s;
      }
      @keyframes rowFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
      }
      tbody tr:hover {
        background: #eaf7fb !important;
        box-shadow: 0 2px 8px #0dcaf033;
        transform: scale(1.01);
      }
      .pagination .page-link {
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        color: #0d6efd !important;
        transition: background 0.18s, color 0.18s;
      }
      .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
        color: #fff !important;
        border: none !important;
      }
      .alert {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        box-shadow: 0 2px 8px #0d6efd13;
      }
    </style>
</head>
<body>
<div class="container mt-4">
    <div id="loaderSpinner" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(255,255,255,0.7); z-index:9999; align-items:center; justify-content:center;">
        <div class="d-flex flex-column align-items-center justify-content-center" style="height:100vh;">
            <div class="spinner-border text-primary" style="width:3rem; height:3rem;" role="status"></div>
            <div class="mt-3 text-primary fw-bold">Chargement...</div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="bi bi-journal-text"></i> Logs et statistiques</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dashboard
        </a>
    </div>
    <form method="get" class="row g-2 mb-3" id="mainFilterForm">
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
            <input type="text" name="search" class="form-control" id="quickSearchInput" placeholder="Recherche rapide (mot-clé)" value="<?= htmlspecialchars($filtre_search) ?>">
        </div>
        <div class="col d-flex gap-2">
            <button class="btn btn-primary">Filtrer</button>
            <button type="button" class="btn btn-outline-secondary" id="resetFiltersBtn">Réinitialiser</button>
        </div>
        <div class="col">
            <a href="export_logs.php?user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
        </div>
    </form>
    <script>
    // Spinner/loader
    function showLoader() {
        document.getElementById('loaderSpinner').style.display = 'flex';
    }
    function hideLoader() {
        document.getElementById('loaderSpinner').style.display = 'none';
    }
    window.addEventListener('pageshow', hideLoader);
    // Barre de recherche rapide avec délai (debounce)
    let searchTimeout;
    document.getElementById('quickSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            showLoader();
            document.getElementById('mainFilterForm').submit();
        }, 400); // 400ms après la dernière frappe
    });
    // Bouton réinitialiser les filtres
    document.getElementById('resetFiltersBtn').addEventListener('click', function() {
        document.querySelector('input[name="user"]').value = '';
        document.querySelector('select[name="action"]').value = '';
        document.querySelector('input[name="date"]').value = '';
        document.getElementById('quickSearchInput').value = '';
        showLoader();
        document.getElementById('mainFilterForm').submit();
    });
    // Affiche le loader lors de la soumission du formulaire principal
    document.getElementById('mainFilterForm').addEventListener('submit', function() {
        showLoader();
    });
    </script>
    <!-- Actions administratives -->
    <h4 class="mt-4"><i class="bi bi-person-badge"></i> Actions administratives</h4>
    <div class="mb-2 text-end text-secondary" style="font-size:0.98rem;">Total : <?= $total_admin = $pdo->query("SELECT COUNT(*) FROM logs WHERE action LIKE 'admin_%'")->fetchColumn(); ?> actions administratives</div>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_admin" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= ($_GET['limit_admin'] ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= ($_GET['limit_admin'] ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= ($_GET['limit_admin'] ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= ($_GET['limit_admin'] ?? 10) == 50 ? 'selected' : '' ?>>50</option>
            </select> actions
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $limit_admin = isset($_GET['limit_admin']) ? intval($_GET['limit_admin']) : 10;
            $page_admin = isset($_GET['page_admin']) ? max(1, intval($_GET['page_admin'])) : 1;
            $max_page_admin = ceil($total_admin / $limit_admin);
            $offset_admin = ($page_admin - 1) * $limit_admin;
            ?>
            <li class="page-item <?= $page_admin == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_admin=<?= $limit_admin ?>&page_admin=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_admin == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_admin=<?= $limit_admin ?>&page_admin=<?= $page_admin-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php
            $window = 2;
            $start = max(1, $page_admin - $window);
            $end = min($max_page_admin, $page_admin + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_admin ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_admin=<?= $limit_admin ?>&page_admin=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_admin) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_admin == $max_page_admin ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_admin=<?= $limit_admin ?>&page_admin=<?= $page_admin+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_admin == $max_page_admin ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_admin=<?= $limit_admin ?>&page_admin=<?= $max_page_admin ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
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
                WHERE l.action LIKE 'admin_%' ORDER BY l.timestamp DESC LIMIT $limit_admin OFFSET $offset_admin");
            foreach ($stmt_admin->fetchAll(PDO::FETCH_ASSOC) as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['admin_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['cible']) ?></td>
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Alertes et logs critiques -->
    <h4 class="mt-4"><i class="bi bi-exclamation-triangle"></i> Alertes et logs critiques</h4>
    <div class="mb-2 text-end text-danger" style="font-size:0.98rem;">Total : <?= $total_alert = $pdo->query("SELECT COUNT(*) FROM logs WHERE statut = 'bloque' OR action IN ('tentative_suspecte', 'acces_expire', 'telechargement_refuse', 'login_bloque')")->fetchColumn(); ?> alertes</div>
    <form method="get" class="mb-2">
        <input type="hidden" name="user" value="<?= htmlspecialchars($filtre_user) ?>">
        <input type="hidden" name="action" value="<?= htmlspecialchars($filtre_action) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($filtre_date) ?>">
        <label>Afficher :
            <select name="limit_alert" onchange="this.form.submit()" class="form-select d-inline w-auto">
                <option value="5" <?= ($_GET['limit_alert'] ?? 10) == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= ($_GET['limit_alert'] ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= ($_GET['limit_alert'] ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= ($_GET['limit_alert'] ?? 10) == 50 ? 'selected' : '' ?>>50</option>
            </select> alertes
        </label>
    </form>
    <nav>
        <ul class="pagination">
            <?php $limit_alert = isset($_GET['limit_alert']) ? intval($_GET['limit_alert']) : 10;
            $page_alert = isset($_GET['page_alert']) ? max(1, intval($_GET['page_alert'])) : 1;
            $max_page_alert = ceil($total_alert / $limit_alert);
            $offset_alert = ($page_alert - 1) * $limit_alert;
            ?>
            <li class="page-item <?= $page_alert == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_alert=<?= $limit_alert ?>&page_alert=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_alert == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_alert=<?= $limit_alert ?>&page_alert=<?= $page_alert-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php
            $window = 2;
            $start = max(1, $page_alert - $window);
            $end = min($max_page_alert, $page_alert + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_alert ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_alert=<?= $limit_alert ?>&page_alert=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_alert) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_alert == $max_page_alert ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_alert=<?= $limit_alert ?>&page_alert=<?= $page_alert+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_alert == $max_page_alert ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_alert=<?= $limit_alert ?>&page_alert=<?= $max_page_alert ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
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
    $stmt_alert = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id WHERE l.statut = 'bloque' OR l.action IN ('tentative_suspecte', 'acces_expire', 'telechargement_refuse', 'login_bloque') ORDER BY l.timestamp DESC LIMIT $limit_alert OFFSET $offset_alert");
            foreach ($stmt_alert->fetchAll(PDO::FETCH_ASSOC) as $log): ?>
                <tr>
                    <td><?= $log['timestamp'] ?></td>
                    <td><?= htmlspecialchars($log['utilisateur_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-person-check"></i> Connexions / Déconnexions</h4>
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <span class="text-secondary" style="font-size:0.98rem;">Total : <?= $total_conn ?> connexions/déconnexions</span>
        <div>
            <a href="export_logs_table.php?type=conn&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success btn-sm me-1"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
            <a href="export_logs_pdf.php?type=conn&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
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
            <li class="page-item <?= $page_conn == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_conn == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=<?= $page_conn-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php $max_page_conn = ceil($total_conn / $limit_conn); ?>
            <?php
            $window = 2;
            $start = max(1, $page_conn - $window);
            $end = min($max_page_conn, $page_conn + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_conn ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_conn) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_conn == $max_page_conn ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=<?= $page_conn+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_conn == $max_page_conn ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_conn=<?= $limit_conn ?>&page_conn=<?= $max_page_conn ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
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
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-file-earmark-arrow-down"></i> Téléchargements</h4>
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <span class="text-secondary" style="font-size:0.98rem;">Total : <?= $total_dl ?> téléchargements</span>
        <div>
            <a href="export_logs_table.php?type=dl&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success btn-sm me-1"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
            <a href="export_logs_pdf.php?type=dl&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
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
            <li class="page-item <?= $page_dl == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_dl == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=<?= $page_dl-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php $max_page_dl = ceil($total_dl / $limit_dl); ?>
            <?php
            $window = 2;
            $start = max(1, $page_dl - $window);
            $end = min($max_page_dl, $page_dl + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_dl ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_dl) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_dl == $max_page_dl ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=<?= $page_dl+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_dl == $max_page_dl ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_dl=<?= $limit_dl ?>&page_dl=<?= $max_page_dl ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
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
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4"><i class="bi bi-eye"></i> Consultations</h4>
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <span class="text-secondary" style="font-size:0.98rem;">Total : <?= $total_consult ?> consultations</span>
        <div>
            <a href="export_logs_table.php?type=consult&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success btn-sm me-1"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
            <a href="export_logs_pdf.php?type=consult&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
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
            <li class="page-item <?= $page_consult == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_consult == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=<?= $page_consult-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php $max_page_consult = ceil($total_consult / $limit_consult); ?>
            <?php
            $window = 2;
            $start = max(1, $page_consult - $window);
            $end = min($max_page_consult, $page_consult + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_consult ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_consult) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_consult == $max_page_consult ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=<?= $page_consult+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_consult == $max_page_consult ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_consult=<?= $limit_consult ?>&page_consult=<?= $max_page_consult ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
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
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <span class="text-secondary" style="font-size:0.98rem;">Total : <?= $total_demande ?> demandes d'accès</span>
        <div>
            <a href="export_logs_table.php?type=demande&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success btn-sm me-1"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
            <a href="export_logs_pdf.php?type=demande&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
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
            <li class="page-item <?= $page_demande == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_demande == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=<?= $page_demande-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php $max_page_demande = ceil($total_demande / $limit_demande); ?>
            <?php
            $window = 2;
            $start = max(1, $page_demande - $window);
            $end = min($max_page_demande, $page_demande + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_demande ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_demande) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_demande == $max_page_demande ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=<?= $page_demande+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_demande == $max_page_demande ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_demande=<?= $limit_demande ?>&page_demande=<?= $max_page_demande ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
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
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Suppression du tableau logs filtrés -->

    <h4 class="mt-4"><i class="bi bi-journal-text"></i> Tous les logs</h4>
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <span class="text-secondary" style="font-size:0.98rem;">Total : <?= $total_logs ?> logs</span>
        <div>
            <a href="export_logs_table.php?type=logs&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-success btn-sm me-1"><i class="bi bi-file-earmark-arrow-down"></i> Export CSV</a>
            <a href="export_logs_pdf.php?type=logs&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
        </div>
    </div>
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
            <li class="page-item <?= $page_logs == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=1&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $page_logs == 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=<?= $page_logs-1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&lsaquo;</a>
            </li>
            <?php $max_page_logs = ceil($total_logs / $limit_logs); ?>
            <?php
            $window = 2;
            $start = max(1, $page_logs - $window);
            $end = min($max_page_logs, $page_logs + $window);
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page_logs ? 'active' : '' ?>">
                    <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=<?= $i ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $max_page_logs) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            <li class="page-item <?= $page_logs == $max_page_logs ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=<?= $page_logs+1 ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&rsaquo;</a>
            </li>
            <li class="page-item <?= $page_logs == $max_page_logs ? 'disabled' : '' ?>">
                <a class="page-link" href="?limit_logs=<?= $limit_logs ?>&page_logs=<?= $max_page_logs ?>&user=<?= urlencode($filtre_user) ?>&action=<?= urlencode($filtre_action) ?>&date=<?= urlencode($filtre_date) ?>">&raquo;</a>
            </li>
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
                    <td>
                        <?php if ($log['statut'] === 'bloque'): ?>
                            <span class="badge bg-danger">Bloqué</span>
                        <?php elseif ($log['statut'] === 'refuse'): ?>
                            <span class="badge bg-warning text-dark">Refusé</span>
                        <?php elseif ($log['statut'] === 'accepte'): ?>
                            <span class="badge bg-success">Accepté</span>
                        <?php elseif ($log['statut']): ?>
                            <span class="badge bg-info text-light"><?= htmlspecialchars($log['statut']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>