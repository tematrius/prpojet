<?php
session_start();
require 'includes/db.php';
require 'includes/log.php';
$pdo->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')->execute([$_SESSION['user']['id']]);
add_log('logout', $_SESSION['user']['id'] ?? null, '', 'user', $_SESSION['user']['id'] ?? null, 'succes', 'Déconnexion', $_SERVER['REMOTE_ADDR']);
session_destroy();
header("Location: index.php");

