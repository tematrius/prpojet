<?php
// includes/log.php
function add_log($action, $user_id = null, $details = '', $type_cible = '', $target_id = null, $status = '', $message = '', $ip = null) {
    global $pdo;
    if (!$pdo) return false;
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    $stmt = $pdo->prepare("INSERT INTO logs (timestamp, user_id, action, type_cible, target_id, statut, message, details, ip_address) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt->execute([
        $user_id,
        $action,
        $type_cible,
        $target_id,
        $status,
        $message,
        $details,
        $ip
    ])) {
        error_log('Erreur log: ' . implode(' | ', $stmt->errorInfo()));
    }
    return true;
}
?>
