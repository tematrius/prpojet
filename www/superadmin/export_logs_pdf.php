<?php
// Export PDF pour chaque tableau avec mPDF
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Utilise l'autoloader Composer

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$type = $_GET['type'] ?? 'logs';
$user = $_GET['user'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';

$mpdf = new mPDF(['mode' => 'utf-8', 'format' => 'A4']); // Pour mPDF v6.x, pas de namespace
$html = '<style>table {border-collapse:collapse;width:100%;font-size:12px;} th,td {border:1px solid #ccc;padding:6px;} th {background:#eee;}</style>';

if ($type === 'conn') {
    $sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, l.action, l.statut, l.message, l.ip_address
        FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id
        WHERE l.action IN ('login_succes', 'logout')";
    if ($user) $sql .= " AND u.nom LIKE '%".addslashes($user)."%'";
    if ($action) $sql .= " AND l.action = '".addslashes($action)."'";
    if ($date) $sql .= " AND DATE(l.timestamp) = '".addslashes($date)."'";
    $sql .= " ORDER BY l.timestamp DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $html .= '<h3>Connexions / Déconnexions</h3><table><tr><th>Date</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Action</th><th>Statut</th><th>Message</th><th>IP</th></tr>';
    foreach ($rows as $row) {
        $html .= '<tr><td>'.$row['timestamp'].'</td><td>'.$row['utilisateur_nom'].'</td><td>'.$row['utilisateur_email'].'</td><td>'.$row['utilisateur_role'].'</td><td>'.$row['action'].'</td><td>'.$row['statut'].'</td><td>'.$row['message'].'</td><td>'.$row['ip_address'].'</td></tr>';
    }
    $html .= '</table>';
    $mpdf->WriteHTML($html);
    $mpdf->Output('export_conn.pdf', 'D');
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
    $html .= '<h3>Téléchargements</h3><table><tr><th>Date</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Nom du fichier</th><th>ID</th><th>Statut</th><th>IP</th></tr>';
    foreach ($rows as $row) {
        $html .= '<tr><td>'.$row['timestamp'].'</td><td>'.$row['utilisateur_nom'].'</td><td>'.$row['utilisateur_email'].'</td><td>'.$row['utilisateur_role'].'</td><td>'.$row['fichier_nom'].'</td><td>'.$row['target_id'].'</td><td>'.$row['statut'].'</td><td>'.$row['ip_address'].'</td></tr>';
    }
    $html .= '</table>';
    $mpdf->WriteHTML($html);
    $mpdf->Output('export_dl.pdf', 'D');
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
    $html .= '<h3>Consultations</h3><table><tr><th>Date</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Nom du fichier</th><th>ID</th><th>IP</th></tr>';
    foreach ($rows as $row) {
        $html .= '<tr><td>'.$row['timestamp'].'</td><td>'.$row['utilisateur_nom'].'</td><td>'.$row['utilisateur_email'].'</td><td>'.$row['utilisateur_role'].'</td><td>'.$row['fichier_nom'].'</td><td>'.$row['target_id'].'</td><td>'.$row['ip_address'].'</td></tr>';
    }
    $html .= '</table>';
    $mpdf->WriteHTML($html);
    $mpdf->Output('export_consult.pdf', 'D');
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
    $html .= '<h3>Demandes d\'accès</h3><table><tr><th>Date</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Message</th><th>ID</th><th>Statut</th><th>IP</th></tr>';
    foreach ($rows as $row) {
        $html .= '<tr><td>'.$row['timestamp'].'</td><td>'.$row['utilisateur_nom'].'</td><td>'.$row['utilisateur_email'].'</td><td>'.$row['utilisateur_role'].'</td><td>'.$row['message'].'</td><td>'.$row['target_id'].'</td><td>'.$row['statut'].'</td><td>'.$row['ip_address'].'</td></tr>';
    }
    $html .= '</table>';
    $mpdf->WriteHTML($html);
    $mpdf->Output('export_demande.pdf', 'D');
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
$html .= '<h3>Tous les logs</h3><table><tr><th>Date</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Action</th><th>Cible</th><th>ID</th><th>Statut</th><th>Message</th><th>IP</th></tr>';
foreach ($rows as $row) {
    $html .= '<tr><td>'.$row['timestamp'].'</td><td>'.$row['utilisateur_nom'].'</td><td>'.$row['utilisateur_email'].'</td><td>'.$row['utilisateur_role'].'</td><td>'.$row['action'].'</td><td>'.$row['type_cible'].'</td><td>'.$row['target_id'].'</td><td>'.$row['statut'].'</td><td>'.$row['message'].'</td><td>'.$row['ip_address'].'</td></tr>';
}
$html .= '</table>';
$mpdf->WriteHTML($html);
$mpdf->Output('export_logs.pdf', 'D');
exit;
