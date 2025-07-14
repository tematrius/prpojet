<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = $_POST['titre'];
    $provenance = $_POST['provenance'];
    $secretaire_access = isset($_POST['consultable']) ? 1 : 0;
    $file = $_FILES['fichier'];

    $filename = time() . '_' . basename($file['name']);
    $dest = 'uploads/' . $filename;
    move_uploaded_file($file['tmp_name'], $dest);

    $stmt = $pdo->prepare("INSERT INTO documents (titre, nom_fichier, auteur_id, provenance, consultable_par_secretaire) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titre, $filename, $_SESSION['user']['id'], $provenance, $secretaire_access]);

    echo "Fichier importé avec succès.";
}
?>
<form method="POST" enctype="multipart/form-data">
    Titre: <input type="text" name="titre" required /><br/>
    Provenance: 
    <select name="provenance">
        <option value="ag">AG</option>
        <option value="secretaire">Secrétaire</option>
    </select><br/>
    Consultation par secrétaire : <input type="checkbox" name="consultable" /><br/>
    Fichier: <input type="file" name="fichier" required /><br/>
    <button type="submit">Uploader</button>
</form>
