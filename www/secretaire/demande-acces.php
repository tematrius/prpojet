<?php
require '../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_document'])) {
    $id_document = intval($_POST['id_document']);
    $id_user = $_SESSION['user']['id'] ?? null;

    if (!$id_user) {
        header("Location: ../index.php?error=not_logged_in");
        exit;
    }

    $commentaire = trim($_POST['commentaire'] ?? '');

    // Vérifie si une demande similaire existe déjà
    $stmt = $pdo->prepare("SELECT id FROM demandes WHERE id_document = ? AND id_demandeur = ? AND statut = 'en_attente'");
    $stmt->execute([$id_document, $id_user]);

    if ($stmt->fetch()) {
        header("Location: demandes.php?info=doublon");
        exit;
    }

    // Insère la nouvelle demande (soumise directement à l’AG)
    $stmt = $pdo->prepare("
        INSERT INTO demandes (id_document, id_demandeur, commentaire, statut, date_post, soumis_ag)
        VALUES (?, ?, ?, 'en_attente', NOW(), 1)
    ");
    $stmt->execute([$id_document, $id_user, $commentaire]);

    header("Location: demandes.php?info=success");
    exit;
} else {
    http_response_code(400);
    echo "Requête invalide.";
}
?>
