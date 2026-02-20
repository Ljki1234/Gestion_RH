<?php
session_start();

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Rediriger vers la page de connexion si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Gestion_RH/login.php');
        exit();
    }
}

// Obtenir les informations de l'utilisateur connecté
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'nom_utilisateur' => $_SESSION['nom_utilisateur'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}
?>
