<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secretaire') {
    header('Location: /login.html');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID manquant.";
    exit;
}

// Générer nouveau mot de passe
$new_pass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
$hash = password_hash($new_pass, PASSWORD_BCRYPT);

// Mettre à jour
$stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
$stmt->execute([$hash, $id]);

// Affichage simple

?>

<div class="container mt-4">
    <div class="alert alert-success">
        Nouveau mot de passe généré : 
        <input type="text" class="form-control d-inline w-auto" value="<?= $new_pass ?>" readonly onclick="this.select()">
    </div>
    <a href="liste-employes.php" class="btn btn-secondary">Retour à la liste</a>
</div>
