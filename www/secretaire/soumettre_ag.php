<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demande_id'])) {
    $id = $_POST['demande_id'];
    $stmt = $pdo->prepare("UPDATE demandes SET soumis_ag = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: demandes.php");
exit;
