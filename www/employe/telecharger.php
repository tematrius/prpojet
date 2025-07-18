<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/encryption.php';
require '../includes/log.php';
date_default_timezone_set('Africa/Kinshasa');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employe') {
    header('Location: ../index.php');
    exit;
}

$token = $_GET['token'] ?? null;
$id = intval($_GET['id'] ?? 0);
// Si c'est un fichier non restreint (id donné)
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT nom_fichier, chemin, est_restreint FROM archives WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) exit('Document introuvable.');
    if ($doc['est_restreint']) {
        exit('Accès interdit via cet URL, utilisez le token.');
    }
    $filePath = '../' . $doc['chemin'];
    if (!file_exists($filePath)) exit('Fichier introuvable.');
    // Incrémente le nombre de téléchargements
    $pdo->prepare("UPDATE archives SET nombre_telechargements = nombre_telechargements + 1 WHERE id = ?")->execute([$id]);
    $stmtCle = $pdo->prepare("SELECT valeur FROM cles WHERE id = (SELECT id_cle FROM archives WHERE id = ?)");
    $stmtCle->execute([$id]);
    $cle = $stmtCle->fetchColumn();
    $data = file_get_contents($filePath);
    $decrypted = decrypt_file($data, $cle);
    add_log('telechargement', $_SESSION['user']['id'] ?? null, '', 'document', $doc['id_document'], 'succes', 'Téléchargement du document', $_SERVER['REMOTE_ADDR']);
    $nomFinal = basename($doc['nom_fichier']);
    $extension = strtolower(pathinfo($nomFinal, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'txt' => 'text/plain'
    ];
    $mime = $mimeTypes[$extension] ?? 'application/octet-stream';
    if (ob_get_level()) ob_end_clean();
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $nomFinal . '"');
    header('Content-Length: ' . strlen($decrypted));
    echo $decrypted;
    exit;
}
// Sinon fichier restreint (via token)
if (!$token) {
    exit('Token manquant.');
}
$stmt = $pdo->prepare("SELECT a.nom_fichier, a.chemin, d.telechargements_restants, d.expiration_acces, d.id, a.id_cle FROM archives a JOIN demandes d ON d.id_document = a.id WHERE d.token = ? AND d.id_demandeur = ? AND d.statut = 'accepte'");
$stmt->execute([$token, $_SESSION['user']['id']]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    exit('Token invalide ou accès refusé.');
}
if (strtotime($doc['expiration_acces']) < time()) {
    exit('Accès expiré.');
}
if ($doc['telechargements_restants'] <= 0) {
    exit('Aucun téléchargement restant.');
}
$filePath = '../' . $doc['chemin'];
if (!file_exists($filePath)) {
    exit('Fichier introuvable.');
}
// Décrémente
$stmt = $pdo->prepare("UPDATE demandes SET telechargements_restants = telechargements_restants - 1 WHERE id = ?");
$stmt->execute([$doc['id']]);
// Incrémente le nombre de téléchargements
$pdo->prepare("UPDATE archives SET nombre_telechargements = nombre_telechargements + 1 WHERE id = ?")->execute([$doc['id_document']]);
$stmtCle = $pdo->prepare("SELECT valeur FROM cles WHERE id = ?");
$stmtCle->execute([$doc['id_cle']]);
$cle = $stmtCle->fetchColumn();
$data = file_get_contents($filePath);
$decrypted = decrypt_file($data, $cle);

add_log('telechargement', $_SESSION['user']['id'] ?? null, '', 'document', $doc['id_document'], 'succes', 'Téléchargement du document', $_SERVER['REMOTE_ADDR']);
$nomFinal = basename($doc['nom_fichier']);
$extension = strtolower(pathinfo($nomFinal, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'txt' => 'text/plain'
];
$mime = $mimeTypes[$extension] ?? 'application/octet-stream';
if (ob_get_level()) ob_end_clean();
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nomFinal . '"');
header('Content-Length: ' . strlen($decrypted));
echo $decrypted;
exit;
