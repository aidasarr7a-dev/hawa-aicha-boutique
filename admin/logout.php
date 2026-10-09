<?php
require_once __DIR__ . '/../includes/functions.php';

// Déconnexion uniquement par le bouton (POST + jeton CSRF) : un lien piégé ne peut pas déconnecter l'administrateur
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_check();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
redirect('login.php');
