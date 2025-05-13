<?php
// Initialisation de la session
session_start();

// Inclusion du fichier de configuration de la base de données
require_once 'database.php';

// Récupération de l'ID du signalement
$signalementId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Informations sur le signalement
$signalement = null;

if ($signalementId > 0) {
    // Connexion à la base de données
    $conn = getConnection();
    
    // Récupération des détails du signalement
    $sql = "SELECT s.id, s.titre, s.date_creation, s.statut, 
                  tp.libelle AS type_probleme, v.nom AS ville, q.nom AS quartier,
                  (SELECT url FROM photo WHERE signalement_id = s.id AND est_principale = 1 LIMIT 1) AS photo_url
           FROM signalement s
           LEFT JOIN type_probleme tp ON s.type_probleme_id = tp.id
           LEFT JOIN ville v ON s.ville_id = v.id
           LEFT JOIN quartier q ON s.quartier_id = q.id
           WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $signalementId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $signalement = $result->fetch_assoc();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - AlertRoute</title>
    <link rel="stylesheet" href="css/styles.css">
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
        <div class="page-content" style="text-align: center; max-width: 500px; margin: 4rem auto;">
            <?php if ($signalement): ?>
                <div style="margin-bottom: 2rem;">
                    <div style="width: 80px; height: 80px; background-color: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h1 class="page-title">Signalement envoyé avec succès !</h1>
                    <p class="page-description">
                        Merci pour votre contribution à l'amélioration des routes en Mauritanie. Votre signalement a été enregistré
                        et sera transmis aux autorités compétentes.
                    </p>
                    
                    <div class="card" style="margin-top: 2rem; text-align: left;">
                        <div class="card-header">
                            <h2 class="card-title">Détails du signalement</h2>
                        </div>
                        <div class="card-content">
                            <?php if (!empty($signalement['photo_url'])): ?>
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <img src="<?php echo htmlspecialchars($signalement['photo_url']); ?>" alt="Photo du signalement" style="max-width: 100%; max-height: 200px; border-radius: 0.375rem;">
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Titre:</strong> <?php echo htmlspecialchars($signalement['titre']); ?>
                            </div>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Type de problème:</strong> <?php echo htmlspecialchars($signalement['type_probleme']); ?>
                            </div>
                            
                            <?php if (!empty($signalement['ville'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Ville:</strong> <?php echo htmlspecialchars($signalement['ville']); ?>
                                    <?php if (!empty($signalement['quartier'])): ?>
                                        (<?php echo htmlspecialchars($signalement['quartier']); ?>)
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Date de signalement:</strong> <?php echo date('d/m/Y à H:i', strtotime($signalement['date_creation'])); ?>
                            </div>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Statut:</strong> 
                                <span class="badge badge-<?php echo $signalement['statut']; ?>">
                                    <?php 
                                        $statuts = [
                                            'en_attente' => 'En attente',
                                            'en_cours' => 'En cours de traitement',
                                            'resolu' => 'Résolu',
                                            'rejete' => 'Rejeté'
                                        ];
                                        echo isset($statuts[$signalement['statut']]) ? $statuts[$signalement['statut']] : $signalement['statut'];
                                    ?>
                                </span>
                            </div>
                            
                            <div style="margin-top: 1.5rem;">
                                <strong>Référence:</strong> #<?php echo $signalement['id']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="carte.php" class="btn btn-primary" style="width: 100%;">Voir la carte des signalements</a>
                    <a href="index.php" class="btn btn-outline" style="width: 100%;">Retour à l'accueil</a>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 2rem;">
                    <div style="width: 80px; height: 80px; background-color: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h1 class="page-title">Signalement non trouvé</h1>
                    <p class="page-description">
                        Nous n'avons pas pu trouver le signalement demandé. Il est possible qu'il ait été supprimé ou que l'identifiant soit incorrect.
                    </p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="signaler.php" class="btn btn-primary" style="width: 100%;">Créer un nouveau signalement</a>
                    <a href="index.php" class="btn btn-outline" style="width: 100%;">Retour à l'accueil</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> AlertRoute. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>