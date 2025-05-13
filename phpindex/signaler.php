<?php
// Initialisation de la session
session_start();

// Inclusion du fichier de configuration de la base de données
require_once 'database.php';

// Connexion à la base de données
$conn = getConnection();

// Récupération des types de problèmes depuis la base de données
$typeProblemes = [];
$sql = "SELECT id, libelle FROM type_probleme ORDER BY libelle";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $typeProblemes[] = $row;
    }
}

// Récupération des villes depuis la base de données
$villes = [];
$sql = "SELECT id, nom FROM ville WHERE est_active = 1 ORDER BY nom";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $villes[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signaler un problème - AlertRoute</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>
<body>
    <header class="header">
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="logo-icon">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <h1 class="logo-text">AlertRoute</h1>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="signaler.php" class="active">Signaler</a></li>
                    <li><a href="carte.php">Carte</a></li>
                    <li><a href="statistiques.php">Statistiques</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profil.php">Mon profil</a></li>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-content">
            <h1 class="page-title">Signaler un problème routier</h1>
            <p class="page-description">
                Aidez à améliorer les routes en Mauritanie en signalant les problèmes que vous rencontrez. 
                Vos signalements seront transmis aux autorités compétentes.
            </p>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-warning">
                    <p>Vous devez être <a href="login.php">connecté</a> pour signaler un problème. Pas encore de compte ? <a href="register.php">Inscrivez-vous</a>.</p>
                </div>
            <?php endif; ?>

            <form id="signalementForm" class="form-signalement">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Informations sur le problème</h2>
                        <p class="card-description">Décrivez le problème routier que vous avez constaté</p>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label for="titre">Titre</label>
                            <input type="text" id="titre" name="titre" required placeholder="Titre du signalement">
                        </div>

                        <div class="form-group">
                            <label for="type_probleme">Type de problème</label>
                            <select id="type_probleme" name="type_probleme" required>
                                <option value="">Sélectionnez le type de problème</option>
                                <?php foreach ($typeProblemes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo $type['libelle']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Décrivez le problème en détail..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Photos</h2>
                        <p class="card-description">Ajoutez des photos du problème routier (au moins une photo)</p>
                    </div>
                    <div class="card-content">
                        <div id="photosContainer" class="photos-container">
                            <div class="photo-upload">
                                <label for="photo-upload" class="photo-upload-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="photo-icon">
                                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                                        <circle cx="12" cy="13" r="3"></circle>
                                    </svg>
                                    <span>Ajouter une photo</span>
                                </label>
                                <input type="file" id="photo-upload" name="photos[]" accept="image/*" multiple class="hidden" onchange="handlePhotoUpload(this)">
                            </div>
                        </div>
                        <div id="photoPreviewContainer" class="photo-preview-container"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Localisation</h2>
                        <p class="card-description">Indiquez l'emplacement exact du problème routier</p>
                    </div>
                    <div class="card-content">
                        <button type="button" id="getCurrentLocation" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Utiliser ma position actuelle
                        </button>

                        <div class="form-group">
                            <label for="ville_id">Ville</label>
                            <select id="ville_id" name="ville_id">
                                <option value="">Sélectionnez une ville</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?php echo $ville['id']; ?>"><?php echo $ville['nom']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="quartierContainer" style="display: none;">
                            <label for="quartier_id">Quartier</label>
                            <select id="quartier_id" name="quartier_id">
                                <option value="">Sélectionnez un quartier</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="adresse">Adresse</label>
                            <input type="text" id="adresse" name="adresse" required placeholder="Adresse ou description du lieu">
                        </div>

                        <div id="map" class="map-container"></div>

                        <div id="coordinates" class="coordinates-display">
                            <p>Position sélectionnée: Cliquez sur la carte pour définir la position</p>
                            <input type="hidden" id="latitude" name="latitude" required>
                            <input type="hidden" id="longitude" name="longitude" required>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" onclick="window.location.href='index.php'" class="btn btn-outline">Annuler</button>
                        <button type="submit" id="submitBtn" class="btn btn-primary" <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                            Envoyer le signalement
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> AlertRoute. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="js/signalement.js"></script>
</body>
</html>