// Variables globales
let map;
let marker;
const defaultPosition = [18.0735, -15.9582]; // Nouakchott, Mauritanie
const L = window.L; // Declare L as a global variable

// Initialisation de la carte
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    setupFormValidation();
    setupDynamicQuartiers();
});

// Initialiser la carte Leaflet
function initMap() {
    // Créer la carte
    map = L.map('map').setView(defaultPosition, 13);
    
    // Ajouter la couche de tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Ajouter un marqueur initial
    marker = L.marker(defaultPosition, {
        draggable: true
    }).addTo(map);
    
    // Mettre à jour les coordonnées quand le marqueur est déplacé
    marker.on('dragend', function(event) {
        updateCoordinates(marker.getLatLng());
    });
    
    // Mettre à jour les coordonnées quand on clique sur la carte
    map.on('click', function(event) {
        marker.setLatLng(event.latlng);
        updateCoordinates(event.latlng);
    });
    
    // Initialiser les champs de coordonnées
    updateCoordinates(marker.getLatLng());
    
    // Gérer le bouton de géolocalisation
    document.getElementById('getCurrentLocation').addEventListener('click', getCurrentLocation);
}

// Mettre à jour les champs de coordonnées
function updateCoordinates(latlng) {
    document.getElementById('latitude').value = latlng.lat.toFixed(6);
    document.getElementById('longitude').value = latlng.lng.toFixed(6);
    
    // Afficher les coordonnées
    const coordinatesDisplay = document.getElementById('coordinates');
    coordinatesDisplay.innerHTML = `
        <p>Position sélectionnée: ${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}</p>
        <input type="hidden" id="latitude" name="latitude" value="${latlng.lat.toFixed(6)}" required>
        <input type="hidden" id="longitude" name="longitude" value="${latlng.lng.toFixed(6)}" required>
    `;
}

// Obtenir la position actuelle
function getCurrentLocation() {
    const button = document.getElementById('getCurrentLocation');
    
    if (navigator.geolocation) {
        // Changer l'apparence du bouton pendant la recherche
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Localisation en cours...';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latlng = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Mettre à jour la carte et le marqueur
                map.setView(latlng, 15);
                marker.setLatLng(latlng);
                updateCoordinates(latlng);
                
                // Restaurer l'apparence du bouton
                button.disabled = false;
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg> Utiliser ma position actuelle';
            },
            function(error) {
                // Gérer les erreurs
                let errorMessage = "Impossible d'obtenir votre position actuelle.";
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Vous avez refusé la demande de géolocalisation.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Les informations de localisation ne sont pas disponibles.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "La demande de géolocalisation a expiré.";
                        break;
                    case error.UNKNOWN_ERROR:
                        errorMessage = "Une erreur inconnue s'est produite.";
                        break;
                }
                
                showNotification(errorMessage, 'error');
                
                // Restaurer l'apparence du bouton
                button.disabled = false;
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg> Utiliser ma position actuelle';
            }
        );
    } else {
        showNotification("Votre navigateur ne supporte pas la géolocalisation.", 'error');
    }
}

// Gérer l'upload des photos
function handlePhotoUpload(input) {
    const previewContainer = document.getElementById('photoPreviewContainer');
    
    if (input.files) {
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Créer un élément de prévisualisation
                const preview = document.createElement('div');
                preview.className = 'photo-preview';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Aperçu de la photo">
                    <button type="button" class="photo-remove" onclick="removePhoto(this)">×</button>
                `;
                
                previewContainer.appendChild(preview);
            };
            
            reader.readAsDataURL(file);
        }
    }
}

// Supprimer une photo
function removePhoto(button) {
    const preview = button.parentElement;
    preview.remove();
}

// Configuration du chargement dynamique des quartiers
function setupDynamicQuartiers() {
    const villeSelect = document.getElementById('ville_id');
    if (villeSelect) {
        villeSelect.addEventListener('change', function() {
            const villeId = this.value;
            if (villeId) {
                loadQuartiers(villeId);
            } else {
                // Cacher le conteneur des quartiers si aucune ville n'est sélectionnée
                document.getElementById('quartierContainer').style.display = 'none';
            }
        });
    }
}

// Charger les quartiers en fonction de la ville sélectionnée
function loadQuartiers(villeId) {
    const quartierContainer = document.getElementById('quartierContainer');
    const quartierSelect = document.getElementById('quartier_id');
    
    // Réinitialiser le select des quartiers
    quartierSelect.innerHTML = '<option value="">Sélectionnez un quartier</option>';
    
    // Afficher un indicateur de chargement
    quartierContainer.style.display = 'block';
    quartierSelect.disabled = true;
    quartierSelect.innerHTML = '<option>Chargement...</option>';
    
    // Préparer les données pour la requête AJAX
    const formData = new FormData();
    formData.append('action', 'get_quartiers');
    formData.append('ville_id', villeId);
    
    // Effectuer la requête AJAX
    fetch('ajax/handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Réinitialiser le select
        quartierSelect.innerHTML = '<option value="">Sélectionnez un quartier</option>';
        quartierSelect.disabled = false;
        
        if (data.success && data.quartiers.length > 0) {
            // Ajouter les options au select
            data.quartiers.forEach(quartier => {
                const option = document.createElement('option');
                option.value = quartier.id;
                option.textContent = quartier.nom;
                quartierSelect.appendChild(option);
            });
            
            // Afficher le conteneur des quartiers
            quartierContainer.style.display = 'block';
        } else {
            // Cacher le conteneur si aucun quartier n'est trouvé
            quartierContainer.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des quartiers:', error);
        quartierContainer.style.display = 'none';
        showNotification('Erreur lors du chargement des quartiers', 'error');
    });
}

// Validation du formulaire et soumission AJAX
function setupFormValidation() {
    const form = document.getElementById('signalementForm');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Validation des champs
            let isValid = true;
            
            // Vérifier si au moins une photo a été ajoutée
            const photoPreviewContainer = document.getElementById('photoPreviewContainer');
            if (photoPreviewContainer.children.length === 0) {
                showNotification("Veuillez ajouter au moins une photo du problème routier.", 'error');
                isValid = false;
            }
            
            // Vérifier si les coordonnées sont définies
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            if (!latitude || !longitude) {
                showNotification("Veuillez indiquer la localisation du problème routier sur la carte.", 'error');
                isValid = false;
            }
            
            if (isValid) {
                submitFormAjax(form);
            }
        });
    }
}

// Soumettre le formulaire via AJAX
function submitFormAjax(form) {
    // Désactiver le bouton de soumission pour éviter les soumissions multiples
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Envoi en cours...';
    
    // Préparer les données du formulaire
    const formData = new FormData(form);
    formData.append('action', 'submit_signalement');
    
    // Effectuer la requête AJAX
    fetch('ajax/handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher un message de succès
            showNotification(data.message, 'success');
            
            // Rediriger vers la page de confirmation
            setTimeout(() => {
                window.location.href = 'confirmation.php?id=' + data.signalement_id;
            }, 1500);
        } else {
            // Afficher un message d'erreur
            showNotification(data.message, 'error');
            
            // Réactiver le bouton de soumission
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Envoyer le signalement';
        }
    })
    .catch(error => {
        console.error('Erreur lors de la soumission du formulaire:', error);
        showNotification('Une erreur est survenue lors de l\'envoi du signalement', 'error');
        
        // Réactiver le bouton de soumission
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Envoyer le signalement';
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