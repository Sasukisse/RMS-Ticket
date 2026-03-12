<?php
session_start();
include '../Database/connection.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Recherche de l'utilisateur par email
            $stmt = $pdo->prepare("SELECT id, username, nom, prenom, email, password_hash, droit FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['droit'] = $user['droit'];
                
                // Redirection vers la page d'accueil
                header('Location: ../HomePage/index.php');
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion à la base de données.";
        }
    }
}

// Si déjà connecté, rediriger vers la page d'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: ../HomePage/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion</title>
    <meta name="color-scheme" content="light dark">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="noise" aria-hidden="true"></div>
    <main class="container" role="main">
        <div class="brand" aria-label="Marque">
            <div class="brand-logo" aria-hidden="true"><span>G</span></div>
            <div class="brand-title">Espace membre</div>
        </div>
        <div class="brand-sub">Connectez-vous pour continuer</div>

        <h1 class="title">Connexion</h1>

        <?php if (isset($error)): ?>
            <div class="error-message" style="background-color: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #fcc;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="#" autocomplete="on" novalidate>
            <div class="form-row">
                <label class="input" aria-label="Adresse e-mail">
                    <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/>
                        <path d="m22 8-8.97 5.65a2 2 0 0 1-2.06 0L2 8"/>
                    </svg>
                    <input type="email" name="email" placeholder="Adresse e-mail" inputmode="email" required>
                </label>

                <label class="input" aria-label="Mot de passe">
                    <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input id="password" type="password" name="password" placeholder="Mot de passe" required minlength="6">
                    <svg id="togglePassword" class="icon-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-label="Afficher le mot de passe" role="button" tabindex="0">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </label>
            </div>

            <div class="actions">
                <label class="checkbox"><input type="checkbox" name="remember"> Se souvenir de moi</label>
                <a href="#" class="foot" style="margin:0;">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="submit">Se connecter</button>       
        </form>
    </main>

    <script src="login.js"></script>
</body>
</html>


