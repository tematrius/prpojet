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
    echo '<div class="alert alert-danger">Utilisateur introuvable.</div>';
    exit;
}

// Superadmin non modifiable
if ($user['role'] === 'superadmin') {
    echo '<div class="alert alert-warning">Le Super Admin ne peut pas être modifié.</div>';
    exit;
}

$roles = [
    'employe' => 'Employé',
    'ag' => 'Associé gérant',
    'secretaire' => 'Secrétaire',
    'associe' => 'Associé simple',
    'superadmin' => 'Super Admin'
];

$nom = $_POST['nom'] ?? $user['nom'];
$email = $_POST['email'] ?? $user['email'];
$role = $_POST['role'] ?? $user['role'];
$mdp = $_POST['mot_de_passe'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nom && $email && $role) {
        if ($mdp) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE utilisateurs SET nom = ?, email = ?, role = ?, mot_de_passe = ? WHERE id = ?');
            $ok = $stmt->execute([$nom, $email, $role, $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE utilisateurs SET nom = ?, email = ?, role = ? WHERE id = ?');
            $ok = $stmt->execute([$nom, $email, $role, $id]);
        }
        $message = $ok
            ? '<div class="alert alert-success">Utilisateur modifié avec succès.</div>'
            : '<div class="alert alert-danger">Erreur lors de la modification.</div>';
    } else {
        $message = '<div class="alert alert-warning">Tous les champs sont obligatoires.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      .container { max-width: 600px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4"><i class="bi bi-pencil"></i> Modifier l’utilisateur</h3>
    <?php if ($message): ?>
      <?= $message ?>
    <?php endif; ?>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom complet</label>
            <input type="text" name="nom" id="nom" class="form-control" required value="<?= htmlspecialchars($nom) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Rôle</label>
            <select name="role" id="role" class="form-select" required>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $role === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <div class="input-group">
                <input type="text" name="mot_de_passe" id="mot_de_passe" class="form-control" value="">
                <button type="button" class="btn btn-outline-secondary" onclick="genMdp()">Générer</button>
                <button type="button" class="btn btn-outline-info" onclick="copyMdp()">Copier</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-pencil-square me-1"></i> Modifier
        </button>
        <a href="utilisateurs.php" class="btn btn-secondary ms-2">Retour</a>
    </form>
</div>
<script>
function genMdp() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%&*';
    let pwd = '';
    for (let i = 0; i < 10; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('mot_de_passe').value = pwd;
}
function copyMdp() {
    const input = document.getElementById('mot_de_passe');
    input.select();
    document.execCommand('copy');
}
</script>
</body>
</html>