<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secretaire') {
    header('Location: /login.html');
    exit;
}

$id = $_GET['id'];
$success = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$nom, $email, $id])) {
        $success = "Employé mis à jour avec succès.";
    }
}

// Charger les infos existantes
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$id]);
$employe = $stmt->fetch();

if (!$employe) {
    echo "<div class='alert alert-danger'>Employé introuvable.</div>";
    exit;
}
?>

<div class="container">
    <h3>Modifier les informations de l’employé</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Nom complet</label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($employe['nom']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($employe['email']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="liste-employes.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
