<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Temps de blocage restant
$remainingTime = $_SESSION['remaining_time'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Lien local vers Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
        <!-- Lien local vers Bootstrap JS -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../../config/style.css">
</head>
<body style="background-image: url('../../config/classroom.jpg');">

<div class="authentication">
    <form action="../../controllers/loginController.php" method="POST" id="loginForm" class="login active">
        <h2 class="title">Login with your account</h2>

        <!-- Message -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Chrono -->
        <div id="countdown-container" class="alert alert-warning d-none" role="alert">
            Trop de tentatives. Réessayez dans <span id="countdown"><?= $remainingTime ?></span> secondes...
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="Email address" class="form-control" <?= ($remainingTime > 0) ? 'disabled' : '' ?>>
                <i class='bx bx-envelope'></i>
            </div>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
                <input type="password" name="mot_de_passe" id="password" placeholder="Your password" class="form-control" <?= ($remainingTime > 0) ? 'disabled' : '' ?>>
                <i class='bx bx-lock-alt'></i>
            </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="submit" id="submitBtn" class="btn btn-primary mt-3 w-100" value="Login" <?= ($remainingTime > 0) ? 'disabled' : '' ?>>
    </form>
</div>

<script>
// Blocage
let remaining = <?= $remainingTime ?>;
const countdownEl = document.getElementById("countdown");
const container = document.getElementById("countdown-container");
const emailInput = document.getElementById("email");
const passInput = document.getElementById("password");
const submitBtn = document.getElementById("submitBtn");

if (remaining > 0) {
    container.classList.remove("d-none");

    // Déjà désactivés en HTML, mais on force ici aussi
    emailInput.disabled = true;
    passInput.disabled = true;
    submitBtn.disabled = true;

    const interval = setInterval(() => {
        remaining--;
        countdownEl.textContent = remaining;

        if (remaining <= 0) {
            clearInterval(interval);
            container.classList.add("d-none");

            // Message succès
            const success = document.createElement("div");
            success.className = "alert alert-success";
            success.innerText = "✅ Vous pouvez réessayer maintenant !";
            document.querySelector("form").prepend(success);

            // Réactivation des champs
            emailInput.disabled = false;
            passInput.disabled = false;
            submitBtn.disabled = false;

            setTimeout(() => success.remove(), 3000);
        }
    }, 1000);
}

</script>

</body>
</html>