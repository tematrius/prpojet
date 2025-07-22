<?php
// superadmin/ajouter_utilisateur.php
session_start();
require '../includes/db.php';
require '../auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$roles = ['employe' => 'Employé', 'ag' => 'Agent', 'secretaire' => 'Secrétaire', 'associe' => 'Associé simple', 'superadmin' => 'Super Admin'];

$nom = $_POST['nom'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? '';
$langue = $_POST['langue'] ?? 'fr';
$mdp = $_POST['mot_de_passe'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nom && $email && $role && $mdp) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, email, role, mot_de_passe, langue) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$nom, $email, $role, $hash, $langue])) {
            $message = '<div class="alert alert-success">Utilisateur ajouté avec succès.</div>';
        } else {
            $message = '<div class="alert alert-danger">Erreur lors de l\'ajout.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Tous les champs sont obligatoires.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Ajouter un utilisateur</h2>
    <?= $message ?>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom</label>
            <input type="text" name="nom" id="nom" class="form-control" required value="<?= htmlspecialchars($nom) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Rôle</label>
            <select name="role" id="role" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $role === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="langue" class="form-label">Langue</label>
            <select name="langue" id="langue" class="form-select">
                <option value="fr" <?= $langue === 'fr' ? 'selected' : '' ?>>Français</option>
                <option value="en" <?= $langue === 'en' ? 'selected' : '' ?>>Anglais</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="utilisateurs.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
</body>
</html>
