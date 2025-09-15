<?php
// create_ticket.php
include '../Database/connection.php';

session_start();

// Simuler un utilisateur connecté (à remplacer par ta logique d'authentification)
$_SESSION['user_firstname'] = "Mehdi";
$_SESSION['user_lastname']  = "Chahada";
$_SESSION['user_email']     = "mehdi@example.com";

// Gestion soumission du formulaire
$success = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = htmlspecialchars(trim($_POST["title"]));
    $description = htmlspecialchars(trim($_POST["description"]));
    $category    = htmlspecialchars(trim($_POST["category"]));
    $type        = htmlspecialchars(trim($_POST["type"]));

    // Ici tu pourras insérer en base de données
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Création d'un ticket</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <h2>Créer un ticket</h2>
      <p class="subtitle">Remplissez les informations ci-dessous</p>

      <?php if ($success): ?>
        <div class="alert success">✅ Ticket créé avec succès !</div>
      <?php endif; ?>

      <form method="post" id="ticketForm">
        <div class="field">
          <label>Nom & Prénom</label>
          <input type="text" value="<?= $_SESSION['user_firstname'].' '.$_SESSION['user_lastname'] ?>" disabled>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" value="<?= $_SESSION['user_email'] ?>" disabled>
        </div>
        <div class="field">
          <label for="title">Intitulé</label>
          <input type="text" id="title" name="title" required placeholder="Ex: Problème d'accès Wi-Fi">
        </div>
        <div class="field">
          <label for="description">Description</label>
          <textarea id="description" name="description" required placeholder="Décrivez le problème..."></textarea>
          <div class="char-counter" id="descCounter">0/500</div>
        </div>
        <div class="field">
          <label for="category">Catégorie</label>
          <select id="category" name="category" required>
            <option value="">-- Choisir une catégorie --</option>
            <option value="materiel">Matériel</option>
            <option value="logiciel">Logiciel</option>
            <option value="reseau">Réseau</option>
          </select>
        </div>
        <div class="field">
          <label>Type</label>
          <div class="radio-group">
            <label><input type="radio" name="type" value="incident"> Incident</label>
            <label><input type="radio" name="type" value="demande"> Demande</label>
          </div>
        </div>
        <button type="submit" id="submitBtn">Créer le ticket</button>
      </form>
    </div>
  </div>
  <script src="app.js"></script>
</body>
</html>
