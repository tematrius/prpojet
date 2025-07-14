<?php
require '../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID manquant ou invalide.");
}

// Récupération des infos du PDF en base de données
$stmt = $pdo->prepare("SELECT chemin FROM archives WHERE id = ?");
$stmt->execute([$id]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fichier) {
    die("Fichier non trouvé.");
} else {
    $ip = "192.168.143.79"; // Adresse IP de la machine qui héberge
    $chemin = $fichier['chemin']; // Ex: uploads/mon_fichier.pdf

    // On construit le lien vers le fichier via serve_pdf.php
    $serve_pdf_url = "http://$ip/phpdesktop-bnb/www/serve_pdf.php?file=$chemin";

    // On encode le lien une seule fois pour PDF.js
    $viewer_url = "http://$ip/phpdesktop-bnb/www/pdfjs/web/viewer.html?file=" . urlencode($serve_pdf_url);

    header("Location: $viewer_url");
    exit;
}