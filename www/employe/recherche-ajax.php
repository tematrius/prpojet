<?php
require '../includes/db.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$q = trim($q);

if ($q === '') {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint
        FROM archives
        WHERE (nom_fichier LIKE :q OR contenu_textuel LIKE :q)
        ORDER BY date_upload DESC
        LIMIT 20";

$stmt = $pdo->prepare($sql);
$search = "%$q%";
$stmt->bindParam(':q', $search, PDO::PARAM_STR);
$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as &$res) {
    $res['date'] = date('d/m/Y H:i', strtotime($res['date_upload']));
}

echo json_encode($results);
