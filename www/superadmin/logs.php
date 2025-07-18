<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

// Récupère les logs (les 100 plus récents)
$stmt = $pdo->query("SELECT l.*, u.nom AS utilisateur_nom, u.email AS utilisateur_email 
    FROM logs l 
    LEFT JOIN utilisateurs u ON l.user_id = u.id 
    ORDER BY l.timestamp DESC 
    LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
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
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['type_cible']) ?> #<?= htmlspecialchars($log['target_id']) ?></td>
                    <td><?= htmlspecialchars($log['status']) ?></td>
                    <td><?= htmlspecialchars($log['message']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>