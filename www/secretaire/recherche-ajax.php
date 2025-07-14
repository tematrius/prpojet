<?php
// recherche-ajax.php
require '../includes/db.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nom_fichier, provenance, date_upload, est_restreint FROM archives
    WHERE nom_fichier LIKE ? OR contenu_textuel LIKE ?
    ORDER BY date_upload DESC");
$term = "%$q%";
$stmt->execute([$term, $term]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formatted = array_map(function($fichier) {
    return [
        'id' => $fichier['id'],
        'nom_fichier' => $fichier['nom_fichier'],
        'provenance' => $fichier['provenance'],
        'date' => date('d/m/Y H:i', strtotime($fichier['date_upload'])),
        'est_restreint' => $fichier['est_restreint']
    ];
}, $results);

echo json_encode($formatted);
