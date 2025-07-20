<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo '<div class="alert alert-danger mt-4">Utilisateur introuvable.</div>';
    exit;
}

// Interdit de supprimer superadmin ou AG
if ($user['role'] === 'superadmin' || $user['role'] === 'ag') {
    echo '<div class="alert alert-warning mt-4">Suppression interdite pour ce type d\'utilisateur.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?');
        require_once '../includes/log.php';
        add_log(
            'admin_supprimer_utilisateur',
            $_SESSION['user']['id'],
            '',
            'utilisateur',
             $id,
            'succes',
             "Suppression utilisateur : $nom ($email)",
             $_SERVER['REMOTE_ADDR'] ?? ''
        );    
    $stmt->execute([$id]);
        // Log administratif

    header('Location: utilisateurs.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
        font-family: 'Inter', 'Nunito', Arial, sans-serif;
      }
      .container {
        max-width: 500px;
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(220,53,69,0.10), 0 1px 4px rgba(0,0,0,0.04);
        padding: 2.2rem 2rem 2rem 2rem;
        margin-top: 4rem;
      }
      .alert-danger {
        border-radius: 0.9rem;
        font-size: 1.08rem;
        font-weight: 700;
        letter-spacing: 0.2px;
        box-shadow: 0 2px 8px #dc354513;
        background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%);
        color: #fff;
        border: none;
      }
      .btn-danger {
        background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%) !important;
        color: #fff !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #dc354533;
        border: none !important;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-danger:hover {
        background: linear-gradient(135deg, #ffc107 80%, #dc3545 100%) !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-secondary {
        background: #e3e6f3 !important;
        color: #0d6efd !important;
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        border: none !important;
        box-shadow: 0 2px 8px #0d6efd33;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-secondary:hover {
        background: #0d6efd !important;
        color: #fff !important;
        transform: scale(1.07);
      }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        Voulez-vous vraiment supprimer l’utilisateur <strong><?= htmlspecialchars($user['nom']) ?></strong> (<?= htmlspecialchars($user['email']) ?>) ?
    </div>
    <form method="post">
        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Oui, supprimer</button>
        <a href="utilisateurs.php" class="btn btn-secondary ms-2">Annuler</a>
    </form>
</div>
</body>
</html>