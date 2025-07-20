<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/log.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['action_message'] = 'ID utilisateur manquant.';
    header('Location: utilisateurs.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $_SESSION['action_message'] = 'Utilisateur introuvable.';
    header('Location: utilisateurs.php');
    exit;
}

if ($user['role'] === 'superadmin') {
    $_SESSION['action_message'] = 'Impossible de débloquer un superadmin.';
    header('Location: utilisateurs.php');
    exit;
}

if ($user['is_active']) {
    $_SESSION['action_message'] = 'Utilisateur déjà actif.';
    header('Location: utilisateurs.php');
    exit;
}

$pdo->prepare('UPDATE utilisateurs SET is_active = 1 WHERE id = ?')->execute([$id]);
add_log('admin_deblocage', $_SESSION['user']['id'], '', 'utilisateur', $id, 'debloque', 'Déblocage manuel par superadmin', $_SERVER['REMOTE_ADDR']);
$_SESSION['action_message'] = 'Utilisateur débloqué avec succès.';
header('Location: utilisateurs.php');
exit;
