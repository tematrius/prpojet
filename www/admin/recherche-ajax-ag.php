<?php
require '../includes/db.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$q = trim($q);

if ($q === '') {
    echo json_encode([]);
    exit;
}

// On sélectionne les champs nécessaires
$sql = "SELECT id, nom_fichier, provenance, chemin, est_restreint, date_upload 
        FROM archives 
        WHERE nom_fichier LIKE :q OR contenu_textuel LIKE :q
        ORDER BY date_upload DESC
        LIMIT 20";

$stmt = $pdo->prepare($sql);
$search = "%$q%";
$stmt->bindParam(':q', $search, PDO::PARAM_STR);
$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatage de la date
foreach ($results as &$res) {
    $res['date'] = date('d/m/Y H:i', strtotime($res['date_upload']));
    unset($res['date_upload']); // facultatif, on garde juste 'date'
}

echo json_encode($results);
