<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/encryption.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ag') {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("ID invalide.");
}

$stmt = $pdo->prepare("SELECT nom_fichier, chemin FROM archives WHERE id = ?");
$stmt->execute([$id]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fichier) {
    die("Fichier introuvable en base.");
}
$cheminRelatif = '../' . $fichier['chemin'];
if (!file_exists($cheminRelatif)) {
    die("Fichier introuvable sur le serveur.");
}
$nomFinal = basename($fichier['nom_fichier']);
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
// Récupère la clé associée au fichier
$stmtCle = $pdo->prepare("SELECT valeur FROM cles WHERE id = (SELECT id_cle FROM archives WHERE nom_fichier = ?)");
// Récupère l'id_cle directement
$stmtIdCle = $pdo->prepare("SELECT id_cle FROM archives WHERE nom_fichier = ?");
$stmtIdCle->execute([$fichier['nom_fichier']]);
$idCle = $stmtIdCle->fetchColumn();
$stmtCle = $pdo->prepare("SELECT valeur FROM cles WHERE id = ?");
$stmtCle->execute([$idCle]);
$cle = $stmtCle->fetchColumn();
$data = file_get_contents($cheminRelatif);
$decrypted = decrypt_file($data, $cle);
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nomFinal . '"');
header('Content-Length: ' . strlen($decrypted));
echo $decrypted;
exit;
?>
