<?php session_start(); require 'includes/db.php'; if (!isset($_SESSION['user'])) { header('Location: index.php'); exit(); } $errors = []; $success = ''; if ($_SERVER['REQUEST_METHOD'] === 'POST') { $new_password = $_POST['new_password'] ?? ''; $confirm_password = $_POST['confirm_password'] ?? ''; if (strlen($new_password) < 8) { $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.'; } if ($new_password !== $confirm_password) { $errors[] = 'Les mots de passe ne correspondent pas.'; } if (!$errors) { $hashed = password_hash($new_password, PASSWORD_DEFAULT); $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, a_change_mdp = 1 WHERE id = ?'); $stmt->execute([$hashed, $_SESSION['user']['id']]); $_SESSION['user']['a_change_mdp'] = 1; $success = 'Mot de passe modifié avec succès. Vous pouvez continuer.'; switch ($_SESSION['user']['role']) { case 'ag': header("Location: ../www/admin/dashboard.php"); break; case 'secretaire': header("Location: ../www/secretaire/dashboard.php"); break; case 'employe': header("Location: ../www/employe/dashboard.php"); break; default: header('Location: index.php'); } exit(); } } ?> <!DOCTYPE html> <html lang="fr"> <head> <meta charset="UTF-8"> <title>Changer le mot de passe</title> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> </head> <body class="d-flex justify-content-center align-items-center vh-100 bg-light"> <div class="card p-4 shadow" style="min-width: 350px;"> <h4 class="mb-3">Changer votre mot de passe</h4> <?php if ($errors): ?> <div class="alert alert-danger"> <ul class="mb-0"> <?php foreach ($errors as $e) echo "<li>$e</li>"; ?> </ul> </div> <?php elseif ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Nouveau mot de passe</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Valider</button>
    </form>
</div>
</body> </html>