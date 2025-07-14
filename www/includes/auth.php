<?php
function secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user'])) {
        header("Location: /login.html");
        exit;
    }
    // Vérification IP et user agent
    if ($_SESSION['user']['ip'] !== $_SERVER['REMOTE_ADDR'] ||
        $_SESSION['user']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        exit('Session compromise');
    }
    // Déconnexion après 15 min d’inactivité
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        session_unset();
        session_destroy();
        header('Location: /login.html?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}