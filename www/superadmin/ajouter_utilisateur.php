<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$roles = [
    'employe' => 'Employé',
    'ag' => 'Ag',
    'secretaire' => 'Secrétaire',
    'associe' => 'Associé simple',
    'superadmin' => 'Super Admin'
];

$nom = $_POST['nom'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? '';
$mdp = $_POST['mot_de_passe'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nom && $email && $role && $mdp) {
        // Vérifie si l'email existe déjà
        $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            $message = '<div class="alert alert-danger">Cette adresse e-mail existe déjà.</div>';
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, email, role, mot_de_passe) VALUES (?, ?, ?, ?)');
            if ($stmt->execute([$nom, $email, $role, $hash])) {
                $message = '<div class="alert alert-success">Utilisateur ajouté avec succès.</div>';
                // Récupère l'ID du nouvel utilisateur
                $id_new = $pdo->lastInsertId();
                // Log administratif (user_id = superadmin connecté)
                if ($id_new && isset($_SESSION['user']['id'])) {
                    require_once '../includes/log.php';
                    add_log(
                        'admin_ajouter_utilisateur',
                        $_SESSION['user']['id'] ?? null,
                        '',
                        'utilisateur',
                        $id_new,
                        'succes',
                        "Ajout utilisateur : $nom ($email)",
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    );
                }
                $nom = $email = $role = $mdp = '';
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'ajout.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">Tous les champs sont obligatoires.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
        font-family: 'Inter', 'Nunito', Arial, sans-serif;
      }
      .container {
        max-width: 600px;
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        padding: 2.2rem 2rem 2rem 2rem;
        margin-top: 3.5rem;
      }
      h3 {
        font-weight: 800;
        color: #0d6efd;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px #0d6efd11;
      }
      .form-label {
        font-weight: 700;
        color: #6f42c1;
        letter-spacing: 0.2px;
      }
      .form-control, .form-select {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        padding: 0.7rem 1rem;
        box-shadow: 0 1px 4px #0d6efd13;
        border: 1px solid #e3e6f3;
        transition: border 0.18s;
      }
      .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px #0d6efd33;
      }
      .input-group .btn {
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #0d6efd33;
        border: none !important;
        transition: background 0.18s, color 0.18s, transform 0.18s;
      }
      .btn-primary {
        background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
        color: #fff !important;
      }
      .btn-primary:hover {
        background: linear-gradient(135deg, #6f42c1 80%, #0d6efd 100%) !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-secondary {
        background: #e3e6f3 !important;
        color: #0d6efd !important;
        border: none !important;
      }
      .btn-outline-secondary {
        background: #fff !important;
        color: #0d6efd !important;
        border: 2px solid #0d6efd !important;
      }
      .btn-outline-secondary:hover {
        background: #0d6efd !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-info {
        background: #fff !important;
        color: #0dcaf0 !important;
        border: 2px solid #0dcaf0 !important;
      }
      .btn-outline-info:hover {
        background: #0dcaf0 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .alert {
        border-radius: 0.7rem;
        font-size: 1.05rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        box-shadow: 0 2px 8px #0d6efd13;
      }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4"><i class="bi bi-person-plus"></i> Ajouter un nouvel utilisateur</h3>
    <?php if ($message): ?>
      <?= $message ?>
    <?php endif; ?>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom complet</label>
            <input type="text" name="nom" id="nom" class="form-control" required value="<?= htmlspecialchars($nom) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Rôle</label>
            <select name="role" id="role" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $role === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <div class="input-group">
                <input type="text" name="mot_de_passe" id="mot_de_passe" class="form-control" required value="<?= htmlspecialchars($mdp) ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="genMdp()">Générer</button>
                <button type="button" class="btn btn-outline-info" onclick="copyMdp()">Copier</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Ajouter l’utilisateur
        </button>
        <a href="utilisateurs.php" class="btn btn-secondary ms-2">Retour</a>
    </form>
</div>
<script>
function genMdp() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%&*';
    let pwd = '';
    for (let i = 0; i < 10; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('mot_de_passe').value = pwd;
}
function copyMdp() {
    const input = document.getElementById('mot_de_passe');
    input.select();
    document.execCommand('copy');
}
</script>
</body>
</html>