<?php
session_start();
include '../Database/connection.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}

// Gestion soumission du formulaire
$success = false;
$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? '');
    $description = trim($_POST["description"] ?? '');
    $category = trim($_POST["category"] ?? '');
    $type = trim($_POST["type"] ?? '');

    // Validation des données
    if (empty($title) || empty($description) || empty($category) || empty($type)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (strlen($title) < 4) {
        $error = "Le titre doit contenir au moins 4 caractères.";
    } elseif (strlen($description) < 10) {
        $error = "La description doit contenir au moins 10 caractères.";
    } else {
        try {
            // Créer la table tickets si elle n'existe pas
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `tickets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                `category` ENUM('materiel', 'logiciel', 'reseau', 'autre') NOT NULL,
                `type` ENUM('incident', 'demande') NOT NULL,
                `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createTableSQL);

            // Insérer le ticket en base (priority laissé au défaut en base si non fourni)
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, description, category, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $category, $type]);
            
            $success = true;
            
            // Optionnel : rediriger après succès
            // header('Location: ../HomePage/index.php');
            
        } catch (PDOException $e) {
            $error = "Erreur lors de la création du ticket. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un ticket - RMS-Ticket</title>
    <meta name="color-scheme" content="light dark">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="noise" aria-hidden="true"></div>
    
    <!-- Navbar -->
    <nav class="navbar">
  <div class="nav-container">
    <a href="../HomePage/index.php" class="nav-brand" style="text-decoration: none; color: inherit;">
      <div class="brand-logo"><span>R</span></div>
      <span class="brand-text">RMS-Ticket</span>
    </a>

    <div class="nav-menu">
      <a href="../HomePage/index.php" class="nav-link">Accueil</a>
      <a href="../CreateTickets/create_ticket.php" class="nav-link active">Créer un ticket</a>
      <a href="../Tickets/my_tickets.php" class="nav-link">Mes tickets</a>
      <?php if (!empty($_SESSION['droit']) && $_SESSION['droit']>=1): ?>
        <a href="../AdminPanel/adminpanel.php" class="nav-link">Administration</a>
      <?php endif; ?>
      <a href="../HomePage/logout.php" class="nav-link">Déconnexion</a>
    </div>
  </div> <!-- ✅ fermeture nav-container -->
</nav>


    <!-- Contenu principal -->
    <main class="main-content">
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h1>Créer un ticket</h1>
                    <p class="form-subtitle">Remplissez les informations ci-dessous pour créer votre ticket de support</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                        Ticket créé avec succès !
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="ticketForm" class="ticket-form">
                    <div class="user-info">
                        <div class="field">
                            <label>Nom & Prénom</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>" disabled>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" disabled>
                        </div>
                    </div>

                    <div class="field">
                        <label for="title">Titre du ticket <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="Ex: Problème d'accès Wi-Fi" maxlength="255">
                    </div>

                    <div class="field">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" required placeholder="Décrivez le problème en détail..." maxlength="500"></textarea>
                        <div class="char-counter" id="descCounter">0/500</div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="category">Catégorie <span class="required">*</span></label>
                            <select id="category" name="category" required>
                              <option value="">Choisir une catégorie</option>
                                <option value="materiel">🖥️ Matériel</option>
                                <option value="logiciel">💿 Logiciel</option>
                                <option value="reseau">🌐 Réseau</option>
                                <option value="autre">❓ Autre</option>
                            </select>
                        </div>

                        <!-- Priorité supprimée de l'interface; la valeur par défaut en base est utilisée -->
                    </div>

                    <div class="field">
                        <label>Type <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="type" value="incident" required>
                                <span class="radio-custom"></span>
                                <div class="radio-content">
                                    <strong>🚨 Incident</strong>
                                    <small>Un problème qui empêche le fonctionnement normal</small>
                                </div>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="type" value="demande" required>
                                <span class="radio-custom"></span>
                                <div class="radio-content">
                                    <strong>📋 Demande</strong>
                                    <small>Une demande de service ou d'assistance</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="submit-btn">
    Créer le ticket
</button>

                </form>
            </div>
        </div>
    </main>

    <script src="app.js"></script>
</body>
</html>