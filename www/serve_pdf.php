<?php
// Récupère le chemin du fichier depuis l'URL
$fichier = $_GET['file'] ?? '';

// Sécurité : empêche les chemins comme ../../etc/passwd
$fichier = str_replace('..', '', $fichier);

// Chemin complet vers le fichier sur le disque
$chemin = __DIR__ . '/' . $fichier;

if (file_exists($chemin)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($chemin) . '"');
    header('Content-Length: ' . filesize($chemin));
    readfile($chemin);
    exit;
} else {
    http_response_code(404);
    echo "Fichier introuvable : " . htmlspecialchars($chemin);
}