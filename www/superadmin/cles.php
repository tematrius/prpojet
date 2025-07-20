<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/encryption.php';
require '../includes/log.php';
include '../includes/dashboard-template.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$cle_generee = '';

// Ajout d'une nouvelle clé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    $nom = trim($_POST['nom']);
    if ($nom) {
        $valeur = generate_key(32); // Génère une clé forte
        // Ajoute la clé sans l'activer
        $stmt = $pdo->prepare('INSERT INTO cles (nom, valeur, active) VALUES (?, ?, 0)');
        if ($stmt->execute([$nom, $valeur])) {
            add_log('ajout_cle', $_SESSION['user']['id'] ?? 0, '', 'cle', null, 'ajout', 'Ajout d\'une nouvelle clé: ' . htmlspecialchars($nom), $_SERVER['REMOTE_ADDR']);
            $message = '<div class="alert alert-success">Clé ajoutée avec succès.</div>';
            $cle_generee = $valeur;
        } else {
            $message = '<div class="alert alert-danger">Erreur lors de l\'ajout.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Le nom de la clé est obligatoire.</div>';
    }
}

// Activation unique d'une clé
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    // Désactive toutes les clés
    $pdo->query('UPDATE cles SET active = 0');
    // Active la clé choisie
    $stmt = $pdo->prepare('UPDATE cles SET active = 1 WHERE id = ?');
    $stmt->execute([$id]);
    add_log('activation_cle', $_SESSION['user']['id'] ?? 0, '', 'cle', $id, 'activation', 'Activation de la clé ID ' . $id, $_SERVER['REMOTE_ADDR']);
    $_SESSION['action_message'] = '<div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i> Clé activée avec succès.</div>';
    header('Location: cles.php');
    exit;
}

// Suppression d'une clé avec confirmation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM archives WHERE id_cle = ?');
    $stmt->execute([$id]);
    $used = $stmt->fetchColumn();
    if ($used > 0) {
        $message = '<div class="alert alert-warning">Impossible de supprimer cette clé : elle est utilisée par au moins un fichier.</div>';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM cles WHERE id = ?');
        $stmt->execute([$id]);
        $cle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cle) {
            $message = '<div class="alert alert-danger">Clé introuvable.</div>';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
                $activatedMsg = '';
                // Si la clé supprimée était active, il faut en activer une autre
                if ($cle['active']) {
                    // Cherche la précédente clé active (avant celle-ci)
                    $stmtPrev = $pdo->prepare('SELECT id, nom FROM cles WHERE id < ? AND id != ? ORDER BY id DESC LIMIT 1');
                    $stmtPrev->execute([$cle['id'], $cle['id']]);
                    $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
                    if ($prev) {
                        $pdo->prepare('UPDATE cles SET active = 1 WHERE id = ?')->execute([$prev['id']]);
                        add_log('activation_auto_cle', $_SESSION['user']['id'] ?? 0, '', 'cle', $prev['id'], 'activation_auto', 'Activation automatique de la clé ID ' . $prev['id'] . ' après suppression', $_SERVER['REMOTE_ADDR']);
                        $activatedMsg = '<div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i> La clé <strong>' . htmlspecialchars($prev['nom']) . '</strong> a été activée automatiquement.</div>';
                    } else {
                        // Sinon, active la plus récente autre clé
                        $stmtRecent = $pdo->prepare('SELECT id, nom FROM cles WHERE id != ? ORDER BY id DESC LIMIT 1');
                        $stmtRecent->execute([$cle['id']]);
                        $recent = $stmtRecent->fetch(PDO::FETCH_ASSOC);
                        if ($recent) {
                            $pdo->prepare('UPDATE cles SET active = 1 WHERE id = ?')->execute([$recent['id']]);
                            add_log('activation_auto_cle', $_SESSION['user']['id'] ?? 0, '', 'cle', $recent['id'], 'activation_auto', 'Activation automatique de la clé ID ' . $recent['id'] . ' après suppression', $_SERVER['REMOTE_ADDR']);
                            $activatedMsg = '<div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i> La clé <strong>' . htmlspecialchars($recent['nom']) . '</strong> a été activée automatiquement.</div>';
                        }
                    }
                }
                $stmt = $pdo->prepare('DELETE FROM cles WHERE id = ?');
                $stmt->execute([$id]);
                add_log('suppression_cle', $_SESSION['user']['id'] ?? 0, '', 'cle', $id, 'suppression', 'Suppression de la clé ID ' . $id . ' (' . htmlspecialchars($cle['nom']) . ')', $_SERVER['REMOTE_ADDR']);
                $_SESSION['action_message'] = '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> Clé supprimée avec succès.</div>' . $activatedMsg;
                header('Location: cles.php?deleted=1');
                exit;
            }
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>Supprimer une clé</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
            </head>
            <body>
            <div class="container mt-5">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Voulez-vous vraiment supprimer la clé <strong><?= htmlspecialchars($cle['nom']) ?></strong> ?
                </div>
                <form method="post">
                    <button type="submit" name="confirm_delete" class="btn btn-danger"><i class="bi bi-trash"></i> Oui, supprimer</button>
                    <a href="cles.php" class="btn btn-secondary ms-2">Annuler</a>
                </form>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Liste des clés
$stmt = $pdo->query('SELECT * FROM cles ORDER BY date_creation DESC');
$cles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des clés de chiffrement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
        font-family: 'Inter', 'Nunito', Arial, sans-serif;
      }
      .container {
        max-width: 700px;
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        padding: 2.2rem 2rem 2rem 2rem;
        margin-top: 3.5rem;
      }
      h2 {
        font-weight: 800;
        color: #198754;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px #19875411;
      }
      .form-control, .form-select {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        padding: 0.7rem 1rem;
        box-shadow: 0 1px 4px #19875413;
        border: 1px solid #e3e6f3;
        transition: border 0.18s;
      }
      .form-control:focus, .form-select:focus {
        border-color: #198754;
        box-shadow: 0 2px 8px #19875433;
      }
      .btn-success {
        background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important;
        color: #fff !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #19875433;
        border: none !important;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-success:hover {
        background: linear-gradient(135deg, #0dcaf0 80%, #198754 100%) !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-secondary {
        background: #fff !important;
        color: #198754 !important;
        border: 2px solid #198754 !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #19875433;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-secondary:hover {
        background: #198754 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-info {
        background: #fff !important;
        color: #0dcaf0 !important;
        border: 2px solid #0dcaf0 !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #0dcaf033;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-info:hover {
        background: #0dcaf0 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-warning {
        background: #fff !important;
        color: #ffc107 !important;
        border: 2px solid #ffc107 !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #ffc10733;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-warning:hover {
        background: #ffc107 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-danger {
        background: #fff !important;
        color: #dc3545 !important;
        border: 2px solid #dc3545 !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #dc354533;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-outline-danger:hover {
        background: #dc3545 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .alert {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        box-shadow: 0 2px 8px #19875413;
      }
      .alert-info {
        background: linear-gradient(135deg, #0dcaf0 80%, #198754 100%);
        color: #fff;
        border: none;
      }
      .table {
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        overflow: hidden;
        background: #fff;
      }
      thead th {
        background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important;
        color: #fff !important;
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: 0.3px;
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
      .badge {
        font-size: 0.95rem;
        font-weight: 700;
        padding: 0.3em 0.8em;
        border-radius: 0.7em;
        letter-spacing: 0.2px;
        box-shadow: 0 1px 4px #19875413;
      }
      .badge.bg-success      { background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important; color: #fff !important; }
      .badge.bg-secondary    { background: linear-gradient(135deg, #6c757d 80%, #e3e6f3 100%) !important; color: #fff !important; }
    </style>
</head>
<body>
<div class="container mt-4">
    <?php if (!empty($_SESSION['action_message'])): ?>
      <?= $_SESSION['action_message']; unset($_SESSION['action_message']); ?>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="bi bi-key-fill"></i> Gestion des clés de chiffrement</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dashboard
        </a>
    </div>
    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>
    <?php if ($cle_generee): ?>
        <div class="alert alert-info">
            <strong>Valeur générée :</strong>
            <span id="cleGeneree"><?= htmlspecialchars($cle_generee) ?></span>
            <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="copyCle()">Copier</button>
        </div>
        <script>
        function copyCle() {
            const el = document.getElementById('cleGeneree');
            navigator.clipboard.writeText(el.textContent);
        }
        </script>
    <?php endif; ?>
    <form method="post" class="row g-3 mb-4">
        <div class="col-md-8">
            <input type="text" name="nom" class="form-control" placeholder="Nom de la clé (ex: Janvier)" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Générer et ajouter</button>
        </div>
    </form>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Nom</th>
                <th>Date création</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cles as $cle): ?>
                <tr>
                    <td><?= htmlspecialchars($cle['nom']) ?></td>
                    <td><?= $cle['date_creation'] ?></td>
                    <td>
                        <?php if ($cle['active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="cles.php?toggle=<?= $cle['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                            <?= $cle['active'] ? '<i class="bi bi-eye-slash"></i> Désactiver' : '<i class="bi bi-eye"></i> Activer' ?>
                        </a>
                        <a href="cles.php?delete=<?= $cle['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="bi bi-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>