<?php
require '../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("ID invalide.");
}

// Récupérer les infos du fichier
$stmt = $pdo->prepare("SELECT nom_fichier, chemin FROM archives WHERE id = ?");
$stmt->execute([$id]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fichier) {
    die("Fichier introuvable en base.");
}

// Chemin absolu du fichier
$cheminRelatif = '../' . $fichier['chemin'];
$cheminFichier = realpath($cheminRelatif);

// Vérifier l’existence
if (!$cheminFichier || !file_exists($cheminFichier)) {
    die("Fichier introuvable sur le serveur.");
}

// Forcer le téléchargement avec le bon nom + extension
$nomFinal = basename($fichier['nom_fichier']); // ex: exemple.pdf
$extension = strtolower(pathinfo($nomFinal, PATHINFO_EXTENSION));

// Déterminer le type MIME à la main
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

// Nettoyage du buffer
if (ob_get_level()) ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $nomFinal . '"');
header('Content-Length: ' . filesize($cheminFichier));
readfile($cheminFichier);
exit;
?>
