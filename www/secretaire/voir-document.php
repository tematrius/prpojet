<?php 
session_start(); 
require_once '../includes/db.php'; 
require_once '../includes/encryption.php'; 
date_default_timezone_set('Africa/Kinshasa'); 
if (!isset($_SESSION['user'])) { header('Location: ../index.php'); 
    exit; 
} $id = intval($_GET['id'] ?? 0); 
    $stmt = $pdo->prepare("SELECT nom_fichier, chemin, est_restreint FROM archives WHERE id = ?"); 
    $stmt->execute([$id]); 
    $doc = $stmt->fetch(PDO::FETCH_ASSOC); 
    if (!$doc) exit('Document introuvable.'); 
    if ($doc['est_restreint']) {
        $stmt = $pdo->prepare("SELECT a.nom_fichier, a.chemin, d.expiration_acces, d.id FROM archives a JOIN demandes d ON d.id_document = a.id WHERE d.id_document = ? AND d.id_demandeur = ? AND d.statut = 'accepte' ORDER BY d.expiration_acces DESC LIMIT 1");
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$demande) {
            echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"></head><body>';
            echo '<div class="alert alert-danger d-flex align-items-center" style="margin:30px auto;max-width:500px;"><i class="bi bi-lock-fill me-2"></i> <strong>Accès bloqué !</strong> Vous n\'avez pas l\'autorisation d\'accéder à ce document.</div>';
            echo '</body></html>';
            exit;
        }
        $expiration_timestamp = strtotime($demande['expiration_acces']);
        if ($expiration_timestamp < time()) {
            echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"></head><body>';
            echo '<div class="alert alert-danger d-flex align-items-center" style="margin:30px auto;max-width:500px;"><i class="bi bi-lock-fill me-2"></i> <strong>Accès expiré !</strong> Votre autorisation d\'accès à ce document est terminée.</div>';
            echo '</body></html>';
            exit;
        }
    } $filePath = '../' . $doc['chemin']; 
        if (!file_exists($filePath)) { exit('Fichier introuvable.'); 
        } $data = file_get_contents($filePath); 
        $decrypted = decrypt_file($data); 
        header('Content-Type: application/pdf'); 
        header('Content-Disposition: inline; filename="' . basename($doc['nom_fichier']) . '"'); 
echo $decrypted; 
exit;