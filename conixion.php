<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'opti_db';
$username = 'root'; // Changez selon votre configuration
$password = '';     // Changez selon votre configuration

try {
    // Créer une connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optionnel: définir le mode de récupération par défaut
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // En cas d'erreur de connexion
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
