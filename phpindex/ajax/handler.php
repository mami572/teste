<?php
// Initialisation de la session
session_start();

// Inclusion du fichier de configuration de la base de données
require_once '../database.php';

// Vérification de la requête AJAX
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

// Récupération de l'action demandée
$action = $_POST['action'];

// Traitement en fonction de l'action
switch ($action) {
    case 'get_quartiers':
        // Récupération des quartiers pour une ville
        getQuartiers();
        break;
    
    case 'submit_signalement':
        // Soumission d'un signalement
        submitSignalement();
        break;
    
    case 'get_signalements':
        // Récupération des signalements pour la carte
        getSignalements();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}

/**
 * Récupère les quartiers pour une ville donnée
 */
function getQuartiers() {
    // Vérification du paramètre ville_id
    if (!isset($_POST['ville_id']) || empty($_POST['ville_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de ville non spécifié']);
        exit;
    }
    
    $villeId = intval($_POST['ville_id']);
    $quartiers = [];
    
    // Connexion à la base de données
    $conn = getConnection();
    
    // Récupération des quartiers
    $sql = "SELECT id, nom FROM quartier WHERE ville_id = ? ORDER BY nom";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $villeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $quartiers[] = $row;
        }
    }
    
    $conn->close();
    
    // Retour des quartiers en JSON
    echo json_encode(['success' => true, 'quartiers' => $quartiers]);
}

/**
 * Soumet un nouveau signalement
 */
function submitSignalement() {
    // Vérification si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour signaler un problème.']);
        exit;
    }
    
    // Récupération des données du formulaire
    $titre = isset($_POST['titre']) ? $_POST['titre'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $typeProblemeId = isset($_POST['type_probleme']) ? $_POST['type_probleme'] : '';
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : '';
    $adresse = isset($_POST['adresse']) ? $_POST['adresse'] : '';
    $villeId = isset($_POST['ville_id']) && !empty($_POST['ville_id']) ? $_POST['ville_id'] : null;
    $quartierId = isset($_POST['quartier_id']) && !empty($_POST['quartier_id']) ? $_POST['quartier_id'] : null;
    $userId = $_SESSION['user_id'];
    
    // Validation des données
    if (empty($titre) || empty($description) || empty($typeProblemeId) || 
        empty($latitude) || empty($longitude) || empty($adresse)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
        exit;
    }
    
    // Connexion à la base de données
    $conn = getConnection();
    
    // Début de la transaction
    $conn->begin_transaction();
    
    try {
        // Insertion du signalement dans la base de données
        $sql = "INSERT INTO signalement (titre, description, latitude, longitude, adresse, 
                statut, date_creation, utilisateur_id, type_probleme_id, ville_id, quartier_id) 
                VALUES (?, ?, ?, ?, ?, 'en_attente', NOW(), ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssddsiiii", $titre, $description, $latitude, $longitude, 
                        $adresse, $userId, $typeProblemeId, $villeId, $quartierId);
        
        if ($stmt->execute()) {
            $signalementId = $stmt->insert_id;
            
            // Traitement des photos (à implémenter avec une bibliothèque de téléchargement AJAX)
            // Pour l'instant, nous retournons simplement l'ID du signalement
            
            // Valider la transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Signalement envoyé avec succès!',
                'signalement_id' => $signalementId
            ]);
        } else {
            throw new Exception("Erreur lors de l'insertion du signalement: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du signalement: ' . $e->getMessage()]);
    }
    
    $conn->close();
}

/**
 * Récupère les signalements pour la carte
 */
function getSignalements() {
    // Paramètres de filtrage (optionnels)
    $villeId = isset($_POST['ville_id']) ? intval($_POST['ville_id']) : null;
    $typeProblemeId = isset($_POST['type_probleme_id']) ? intval($_POST['type_probleme_id']) : null;
    $statut = isset($_POST['statut']) ? $_POST['statut'] : null;
    
    // Connexion à la base de données
    $conn = getConnection();
    
    // Construction de la requête SQL avec filtres optionnels
    $sql = "SELECT s.id, s.titre, s.description, s.latitude, s.longitude, s.adresse, 
                   s.statut, s.date_creation, tp.libelle AS type_probleme, tp.icone,
                   v.nom AS ville, q.nom AS quartier,
                   CONCAT(u.prenom, ' ', u.nom) AS utilisateur,
                   (SELECT url FROM photo WHERE signalement_id = s.id AND est_principale = 1 LIMIT 1) AS photo_url
            FROM signalement s
            JOIN type_probleme tp ON s.type_probleme_id = tp.id
            JOIN utilisateur u ON s.utilisateur_id = u.id
            LEFT JOIN ville v ON s.ville_id = v.id
            LEFT JOIN quartier q ON s.quartier_id = q.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($villeId) {
        $sql .= " AND s.ville_id = ?";
        $params[] = $villeId;
        $types .= "i";
    }
    
    if ($typeProblemeId) {
        $sql .= " AND s.type_probleme_id = ?";
        $params[] = $typeProblemeId;
        $types .= "i";
    }
    
    if ($statut) {
        $sql .= " AND s.statut = ?";
        $params[] = $statut;
        $types .= "s";
    }
    
    $sql .= " ORDER BY s.date_creation DESC";
    
    // Préparation et exécution de la requête
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signalements = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $signalements[] = $row;
        }
    }
    
    $conn->close();
    
    // Retour des signalements en JSON
    echo json_encode(['success' => true, 'signalements' => $signalements]);
}
?>