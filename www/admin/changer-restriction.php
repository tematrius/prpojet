<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($id && in_array($action, ['restreindre', 'de-restreindre'])) {
        $val = $action === 'restreindre' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE archives SET est_restreint = ? WHERE id = ?");
        $stmt->execute([$val, $id]);

        header("Location: recherche-ag.php?success=1");
        exit;
    }
}

http_response_code(400);
echo "Requête invalide.";
