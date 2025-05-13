<?php
// Configuration de la base de données AlertRoute
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'alertroute');

// Établir la connexion à la base de données
function getConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données: " . $conn->connect_error);
    }
    
    // Définir le jeu de caractères
    $conn->set_charset("utf8mb4");
    
    return $conn;
}
?>