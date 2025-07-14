<?php
require '../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID manquant ou invalide.");
}

$stmt = $pdo->prepare("SELECT nom_fichier, chemin FROM archives WHERE id = ?");
$stmt->execute([$id]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fichier) {
    die("Fichier non trouvé.");
} else {
    // Nouveau lien via le fichier serve_pdf.php
    $cheminPdf = urlencode("serve_pdf.php?file=" . $fichier['chemin']);

    // Redirection vers PDF.js avec le fichier servi par le script PHP
    header("Location: ../pdfjs/web/viewer.html?file=../../" . $cheminPdf);
    exit;
}