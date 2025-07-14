<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.html"); // Redirige vers la page de connexion si non connecté
    exit();
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BNB Archives - Tableau de bord</title>
</head>
<body>
    <h1>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?> 👋</h1>
    <p>Rôle : <?php echo htmlspecialchars($user['role']); ?></p>
    
    <ul>
        <li><a href="importer_document.php">Importer un document</a></li>
        <li><a href="rechercher_document.php">Rechercher un document</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
    </ul>
</body>
</html>
