<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demande_id'])) {
    require '../includes/log.php';
    $id = $_POST['demande_id'];
    $stmt = $pdo->prepare("UPDATE demandes SET soumis_ag = 1 WHERE id = ?");
    $stmt->execute([$id]);
    $user_id = $_SESSION['user']['id'] ?? null;
    add_log('soumission_ag', $user_id, '', 'demande', $id, 'succes', 'Demande soumise à l\'AG', $_SERVER['REMOTE_ADDR']);
}

header("Location: demandes.php");
exit;
