<?php 
require '../includes/db.php';
require '../includes/encryption.php'; 
session_start(); 
if (!isset($_SESSION['user'])) { 
    header('Location: ../index.php'); 
    exit; 
} $token = $_GET['token'] ?? ''; 
if (!$token) {
     exit('Token manquant.'); 
} $stmt = $pdo->prepare(" SELECT a.nom_fichier, a.chemin, d.telechargements_restants, d.expiration_acces, d.id FROM archives a JOIN demandes d ON d.id_document = a.id WHERE d.token = ? AND d.id_demandeur = ? AND d.statut = 'accepte' "); 
$stmt->execute([$token, $_SESSION['user']['id']]); 
$doc = $stmt->fetch(PDO::FETCH_ASSOC); 
if (!$doc) { exit('Token invalide ou accès refusé.'); 
} if (strtotime($doc['expiration_acces']) > time()) { 
    exit('Accès expiré.'); 
} if ($doc['telechargements_restants'] <= 0) {
     exit('Aucun téléchargement restant.'); 
    } $filePath = '../' . $doc['chemin']; 
    if (!file_exists($filePath)) {
         exit('Fichier introuvable.'); 
        } 
// Décrémente 
$stmt = $pdo->prepare("UPDATE demandes SET telechargements_restants = telechargements_restants - 1 WHERE id = ?"); 
$stmt->execute([$doc['id']]); 
header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename="' . basename($doc['nom_fichier']) . '"'); 
$data = file_get_contents($filePath);
$decrypted = decrypt_file($data); // ta fonction existante
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($doc['nom_fichier']) . '"');
echo $decrypted;
exit; 
?>