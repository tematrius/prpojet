<?php
// Export CSV pour chaque tableau
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$type = $_GET['type'] ?? 'logs'; // logs, conn, dl, consult, demande
$user = $_GET['user'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_'. $type .'.csv"');
$output = fopen('php://output', 'w');

if ($type === 'conn') {
    $sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, l.action, l.statut, l.message, l.ip_address
        FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id
        WHERE l.action IN ('login_succes', 'logout')";
    if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
    if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
    if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
    $sql .= " ORDER BY l.timestamp DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Action', 'Statut', 'Message', 'IP']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['timestamp'], $row['utilisateur_nom'], $row['utilisateur_email'], $row['utilisateur_role'], $row['action'], $row['statut'], $row['message'], $row['ip_address']]);
    }
    exit;
}
if ($type === 'dl') {
    $sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, a.nom_fichier AS fichier_nom, l.target_id, l.statut, l.ip_address
        FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id LEFT JOIN archives a ON l.target_id = a.id
        WHERE l.action = 'telechargement'";
    if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
    if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
    if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
    $sql .= " ORDER BY l.timestamp DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Nom du fichier', 'ID', 'Statut', 'IP']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['timestamp'], $row['utilisateur_nom'], $row['utilisateur_email'], $row['utilisateur_role'], $row['fichier_nom'], $row['target_id'], $row['statut'], $row['ip_address']]);
    }
    exit;
}
if ($type === 'consult') {
    $sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, a.nom_fichier AS fichier_nom, l.target_id, l.ip_address
        FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id LEFT JOIN archives a ON l.target_id = a.id
        WHERE l.action = 'consultation'";
    if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
    if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
    if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
    $sql .= " ORDER BY l.timestamp DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Nom du fichier', 'ID', 'IP']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['timestamp'], $row['utilisateur_nom'], $row['utilisateur_email'], $row['utilisateur_role'], $row['fichier_nom'], $row['target_id'], $row['ip_address']]);
    }
    exit;
}
if ($type === 'demande') {
    $sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, l.message, l.target_id, l.statut, l.ip_address
        FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id
        WHERE l.action = 'demande_acces'";
    if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
    if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
    if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
    $sql .= " ORDER BY l.timestamp DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Message', 'ID', 'Statut', 'IP']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['timestamp'], $row['utilisateur_nom'], $row['utilisateur_email'], $row['utilisateur_role'], $row['message'], $row['target_id'], $row['statut'], $row['ip_address']]);
    }
    exit;
}
// Tous les logs
$sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, l.action, l.type_cible, l.target_id, l.statut, l.message, l.ip_address
    FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id WHERE 1";
if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
$sql .= " ORDER BY l.timestamp DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Action', 'Cible', 'ID', 'Statut', 'Message', 'IP']);
foreach ($rows as $row) {
    fputcsv($output, [$row['timestamp'], $row['utilisateur_nom'], $row['utilisateur_email'], $row['utilisateur_role'], $row['action'], $row['type_cible'], $row['target_id'], $row['statut'], $row['message'], $row['ip_address']]);
}
exit;
