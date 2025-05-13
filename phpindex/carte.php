<?php
// Initialisation de la session
session_start();

// Inclusion du fichier de configuration de la base de données
require_once 'database.php';

// Connexion à la base de données
$conn = getConnection();

// Récupération des types de problèmes pour le filtre
$typeProblemes = [];
$sql = "SELECT id, libelle FROM type_probleme ORDER BY libelle";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $typeProblemes[] = $row;
    }
}

// Récupération des villes pour le filtre
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
    <title>Carte des signalements - AlertRoute</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .map-container-full {
            height: 70vh;
            width: 100%;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid var(--color-gray-300);
            overflow: hidden;
        }
        
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        
        .signalement-popup {
            max-width: 300px;
        }
        
        .signalement-popup img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .signalement-popup h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }
        
        .signalement-popup p {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
        }
        
        .signalement-popup .badge {
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        
        .signalement-popup .details-link {
            display: block;
            text-align: center;
            margin-top: 0.5rem;
            padding: 0.25rem;
            background-color: var(--color-primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
        }
    </style>
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
                    <li><a href="signaler.php">Signaler</a></li>
                    <li><a href="carte.php" class="active">Carte</a></li>
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
            <h1 class="page-title">Carte des signalements</h1>
            <p class="page-description">
                Visualisez les problèmes routiers signalés en Mauritanie. Utilisez les filtres pour affiner votre recherche.
            </p>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Filtres</h2>
                </div>
                <div class="card-content">
                    <div class="filter-container">
                        <div class="filter-item">
                            <label for="filter-ville">Ville</label>
                            <select id="filter-ville" class="filter-select">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?php echo $ville['id']; ?>"><?php echo $ville['nom']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filter-type">Type de problème</label>
                            <select id="filter-type" class="filter-select">
                                <option value="">Tous les types</option>
                                <?php foreach ($typeProblemes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo $type['libelle']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filter-statut">Statut</label>
                            <select id="filter-statut" class="filter-select">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente">En attente</option>
                                <option value="en_cours">En cours</option>
                                <option value="resolu">Résolu</option>
                                <option value="rejete">Rejeté</option>
                            </select>
                        </div>
                    </div>
                    
                    <button id="apply-filters" class="btn btn-primary">Appliquer les filtres</button>
                    <button id="reset-filters" class="btn btn-outline">Réinitialiser</button>
                </div>
            </div>

            <div id="map" class="map-container-full"></div>
            
            <div id="signalements-stats" class="card">
                <div class="card-header">
                    <h2 class="card-title">Statistiques</h2>
                </div>
                <div class="card-content">
                    <div id="stats-content">
                        <p>Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> AlertRoute. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Variables globales
        let map;
        let markers = [];
        const defaultPosition = [18.0735, -15.9582]; // Nouakchott, Mauritanie
        
        // Initialisation de la carte
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            setupFilters();
            loadSignalements();
        });
        
        // Initialiser la carte Leaflet
        function initMap() {
            // Créer la carte
            map = L.map('map').setView(defaultPosition, 8);
            
            // Ajouter la couche de tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }
        
        // Configurer les filtres
        function setupFilters() {
            document.getElementById('apply-filters').addEventListener('click', function() {
                loadSignalements();
            });
            
            document.getElementById('reset-filters').addEventListener('click', function() {
                document.getElementById('filter-ville').value = '';
                document.getElementById('filter-type').value = '';
                document.getElementById('filter-statut').value = '';
                loadSignalements();
            });
        }
        
        // Charger les signalements
        function loadSignalements() {
            // Récupérer les valeurs des filtres
            const villeId = document.getElementById('filter-ville').value;
            const typeProblemeId = document.getElementById('filter-type').value;
            const statut = document.getElementById('filter-statut').value;
            
            // Préparer les données pour la requête AJAX
            const formData = new FormData();
            formData.append('action', 'get_signalements');
            
            if (villeId) formData.append('ville_id', villeId);
            if (typeProblemeId) formData.append('type_probleme_id', typeProblemeId);
            if (statut) formData.append('statut', statut);
            
            // Afficher un indicateur de chargement
            document.getElementById('stats-content').innerHTML = '<p>Chargement des statistiques...</p>';
            
            // Effectuer la requête AJAX
            fetch('ajax/handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Effacer les marqueurs existants
                    clearMarkers();
                    
                    // Ajouter les nouveaux marqueurs
                    addMarkersToMap(data.signalements);
                    
                    // Mettre à jour les statistiques
                    updateStats(data.signalements);
                } else {
                    showNotification(data.message || 'Erreur lors du chargement des signalements', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des signalements:', error);
                showNotification('Une erreur est survenue lors du chargement des signalements', 'error');
            });
        }
        
        // Effacer tous les marqueurs de la carte
        function clearMarkers() {
            markers.forEach(marker => {
                map.removeLayer(marker);
            });
            markers = [];
        }
        
        // Ajouter des marqueurs à la carte
        function addMarkersToMap(signalements) {
            if (signalements.length === 0) {
                showNotification('Aucun signalement ne correspond aux critères de recherche', 'info');
                return;
            }
            
            const bounds = L.latLngBounds();
            
            signalements.forEach(signalement => {
                const lat = parseFloat(signalement.latitude);
                const lng = parseFloat(signalement.longitude);
                
                if (isNaN(lat) || isNaN(lng)) return;
                
                const marker = L.marker([lat, lng]).addTo(map);
                
                // Créer le contenu du popup
                const popupContent = `
                    <div class="signalement-popup">
                        ${signalement.photo_url ? `<img src="${signalement.photo_url}" alt="Photo du signalement">` : ''}
                        <h3>${signalement.titre}</h3>
                        <span class="badge badge-${signalement.statut}">
                            ${getStatutLabel(signalement.statut)}
                        </span>
                        <p><strong>Type:</strong> ${signalement.type_probleme}</p>
                        ${signalement.ville ? `<p><strong>Ville:</strong> ${signalement.ville}${signalement.quartier ? ` (${signalement.quartier})` : ''}</p>` : ''}
                        <p><strong>Date:</strong> ${formatDate(signalement.date_creation)}</p>
                        <a href="detail-signalement.php?id=${signalement.id}" class="details-link">Voir les détails</a>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                markers.push(marker);
                bounds.extend([lat, lng]);
            });
            
            // Ajuster la vue de la carte pour montrer tous les marqueurs
            if (markers.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
        
        // Mettre à jour les statistiques
        function updateStats(signalements) {
            // Compter les signalements par statut
            const statuts = {
                en_attente: 0,
                en_cours: 0,
                resolu: 0,
                rejete: 0
            };
            
            signalements.forEach(signalement => {
                if (statuts.hasOwnProperty(signalement.statut)) {
                    statuts[signalement.statut]++;
                }
            });
            
            // Mettre à jour l'affichage des statistiques
            document.getElementById('stats-content').innerHTML = `
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    <div style="flex: 1; min-width: 150px;">
                        <h3>Total des signalements</h3>
                        <p style="font-size: 2rem; font-weight: bold;">${signalements.length}</p>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <h3>En attente</h3>
                        <p style="font-size: 2rem; font-weight: bold; color: var(--color-warning);">${statuts.en_attente}</p>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <h3>En cours</h3>
                        <p style="font-size: 2rem; font-weight: bold; color: var(--color-info);">${statuts.en_cours}</p>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <h3>Résolus</h3>
                        <p style="font-size: 2rem; font-weight: bold; color: var(--color-success);">${statuts.resolu}</p>
                    </div>
                </div>
            `;
        }
        
        // Obtenir le libellé d'un statut
        function getStatutLabel(statut) {
            const statuts = {
                en_attente: 'En attente',
                en_cours: 'En cours',
                resolu: 'Résolu',
                rejete: 'Rejeté'
            };
            
            return statuts[statut] || statut;
        }
        
        // Formater une date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Afficher une notification
        function showNotification(message, type = 'info') {
            // Vérifier si le conteneur de notifications existe
            let notifContainer = document.getElementById('notificationContainer');
            
            // Créer le conteneur s'il n'existe pas
            if (!notifContainer) {
                notifContainer = document.createElement('div');
                notifContainer.id = 'notificationContainer';
                notifContainer.className = 'notification-container';
                document.body.appendChild(notifContainer);
            }
            
            // Créer la notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            // Ajouter la notification au conteneur
            notifContainer.appendChild(notification);
            
            // Supprimer la notification après 5 secondes
            setTimeout(() => {
                notification.classList.add('notification-hide');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>