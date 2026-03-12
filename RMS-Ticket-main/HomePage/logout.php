<?php
session_start();

// Détruire toutes les données de session
session_destroy();

// Supprimer le cookie de session si il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Rediriger vers la page d'accueil
header('Location: index.php');
exit();
?>
