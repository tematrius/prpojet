<?php
// export_logs.php
require '../includes/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=logs_export.csv');

$filtre_action = $_GET['action'] ?? '';
$filtre_user = $_GET['user'] ?? '';
$filtre_date = $_GET['date'] ?? '';
$filtre_search = $_GET['search'] ?? '';

$sql = "SELECT l.timestamp, u.nom AS utilisateur_nom, u.email AS utilisateur_email, u.role AS utilisateur_role, l.action, l.type_cible, l.target_id, l.statut, l.message, l.ip_address
        FROM logs l
        LEFT JOIN utilisateurs u ON l.user_id = u.id
        WHERE 1";
$params = [];
if ($filtre_action) {
    $sql .= " AND l.action = ?";
    $params[] = $filtre_action;
}
if ($filtre_user) {
    $sql .= " AND u.nom LIKE ?";
    $params[] = "%$filtre_user%";
}
if ($filtre_date) {
    $sql .= " AND DATE(l.timestamp) = ?";
    $params[] = $filtre_date;
}
if ($filtre_search) {
    $sql .= " AND (u.nom LIKE ? OR u.email LIKE ? OR l.message LIKE ? OR l.action LIKE ? OR l.type_cible LIKE ? OR l.target_id LIKE ?)";
    for ($i = 0; $i < 6; $i++) $params[] = "%$filtre_search%";
}
$sql .= " ORDER BY l.timestamp DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Rôle', 'Action', 'Cible', 'ID', 'Statut', 'Message', 'IP']);
foreach ($logs as $log) {
    fputcsv($output, [
        $log['timestamp'],
        $log['utilisateur_nom'],
        $log['utilisateur_email'],
        $log['utilisateur_role'],
        $log['action'],
        $log['type_cible'],
        $log['target_id'],
        $log['statut'],
        $log['message'],
        $log['ip_address']
    ]);
}
fclose($output);
exit;
