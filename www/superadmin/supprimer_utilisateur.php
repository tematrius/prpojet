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
    $stmt->execute([$id]);
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