<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/encryption.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ag') {
    header('Location: ../index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT nom_fichier, chemin FROM archives WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    exit('Document introuvable.');
}
$filePath = '../' . $doc['chemin'];
if (!file_exists($filePath)) {
    exit('Fichier introuvable.');
}
// Récupère la clé associée au fichier
$stmtCle = $pdo->prepare("SELECT valeur FROM cles WHERE id = (SELECT id_cle FROM archives WHERE id = ?)");
$stmtCle->execute([$id]);
$cle = $stmtCle->fetchColumn();
$data = file_get_contents($filePath);
$decrypted = decrypt_file($data, $cle);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($doc['nom_fichier']) . '"');
echo $decrypted;
exit;