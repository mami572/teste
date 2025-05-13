<?php
// Initialisation de la session
session_start();

// Redirection si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Inclusion du fichier de configuration de la base de données
require_once 'database.php';

// Traitement du formulaire de connexion
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validation des données
    if (empty($email) || empty($password)) {
        $message = "Tous les champs sont obligatoires.";
        $messageType = "error";
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Recherche de l'utilisateur par email
        $sql = "SELECT id, nom, prenom, email, mot_de_passe, role FROM utilisateur WHERE email = ? AND est_actif = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Vérification du mot de passe
            if (password_verify($password, $user['mot_de_passe'])) {
                // Mot de passe correct, création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Mise à jour de la date de dernière connexion
                $sql = "UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Redirection vers la page d'accueil
                header("Location: index.php");
                exit();
            } else {
                $message = "Email ou mot de passe incorrect.";
                $messageType = "error";
            }
        } else {
            $message = "Email ou mot de passe incorrect.";
            $messageType = "error";
        }
        
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - AlertRoute</title>
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
                    <li><a href="login.php" class="active">Connexion</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-content" style="max-width: 400px; margin: 4rem auto;">
            <h1 class="page-title">Connexion</h1>
            <p class="page-description">
                Connectez-vous pour signaler des problèmes routiers et suivre vos signalements.
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-content">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Se connecter</button>
                    </form>
                </div>
                <div class="card-footer" style="text-align: center;">
                    <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> AlertRoute. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>