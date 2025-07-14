<?php
session_start();
require 'includes/db.php';
$pdo->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')->execute([$_SESSION['user']['id']]);
session_destroy();
header("Location: index.php");

