<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ag') {
  echo "<div class='container mt-4'><div class='alert alert-danger'>Accès refusé.</div></div>";
  exit;
}

?>

<style>
.container {
  max-width: 720px;
}
.dropzone {
  border: 2px dashed #0d6efd;
  border-radius: 8px;
  padding: 40px;
  background-color: #f8f9fa;
  text-align: center;
  color: #6c757d;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.dropzone:hover {
  background-color: #e9ecef;
}
</style>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-send-plus"></i> Envoyer un ou plusieurs fichiers au secrétariat</h3>

  <form id="form-commentaire" class="mb-3">
    <div class="mb-3">
      <label class="form-label">Commentaire pour le secrétariat</label>
      <textarea name="commentaire" class="form-control" rows="3" placeholder="Entrez un commentaire facultatif..."></textarea>
    </div>
  </form>

  <form action="envoyer-ag.php" class="dropzone" id="uploadDropzone" enctype="multipart/form-data">
    <div class="dz-message">
      Glissez vos fichiers ici ou cliquez pour sélectionner.
    </div>
  </form>
</div>

<script>
Dropzone.autoDiscover = false;

const formCommentaire = document.getElementById('form-commentaire');
const dz = new Dropzone("#uploadDropzone", {
  paramName: "file",
  maxFilesize: 100,
  acceptedFiles: ".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx",
  init: function () {
    this.on("sending", function(file, xhr, formData) {
      const data = new FormData(formCommentaire);
      for (const [key, value] of data.entries()) {
        formData.append(key, value);
      }
    });

    this.on("success", function(file, response) {
      alert("✅ Document envoyé au secrétariat !");
    });

    this.on("error", function(file, errorMessage) {
      alert("❌ Erreur : " + errorMessage);
    });
  }
});
</script>

<?php
// Traitement de l'upload si POST AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
  header('Content-Type: text/plain');
  require '../includes/log.php';
  $file = $_FILES['file'];
  $tmp = $file['tmp_name'];
  $nom = basename($file['name']);
  $uploadDir = __DIR__ . '/../uploads/documents/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
  $chemin = $uploadDir . $nom;
  $cheminRelatif = 'uploads/documents/' . $nom;

  if (!move_uploaded_file($tmp, $chemin)) {
    http_response_code(500);
    echo "Erreur lors du transfert.";
    exit;
  }

  $titre = pathinfo($nom, PATHINFO_FILENAME);
  $commentaire = $_POST['commentaire'] ?? null;
  $stmt = $pdo->prepare("INSERT INTO documents (titre, nom_fichier, chemin, commentaire, provenance, etat, date_upload, auteur_id) VALUES (?, ?, ?, ?, 'ag', 'en_attente', NOW(), ?)");
  $stmt->execute([$titre, $nom, $cheminRelatif, $commentaire, $_SESSION['user']['id']]);
  add_log('envoi_document', $_SESSION['user']['id'], $commentaire, 'document', $pdo->lastInsertId(), 'soumis', 'Document envoyé au secrétariat', $_SERVER['REMOTE_ADDR']);

  echo "Document enregistré.";
  exit;
}
?>
