<?php
session_start();
include '../Database/connection.php';

// Sécurité : connecté
if (!isset($_SESSION['user_id'])) {
  header('Location: ../Login/login.php');
  exit();
}

// Récup ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  exit('Ticket invalide.');
}

// Charger le ticket (et s’assurer qu’il appartient à l’utilisateur)
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
  http_response_code(404);
  exit('Ticket introuvable.');
}

$success = null;
$error = null;

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Fermer
  if (isset($_POST['action']) && $_POST['action'] === 'close') {
    if ($ticket['status'] !== 'closed') {
      $up = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ? AND user_id = ?");
      $up->execute([$ticket['id'], $_SESSION['user_id']]);
      $ticket['status'] = 'closed';
      $success = "Le ticket a été clôturé.";
    }
  }

  // Mettre à jour titre + description
  if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $description === '') {
      $error = "Veuillez remplir l’intitulé et la description.";
    } elseif (mb_strlen($title) < 4) {
      $error = "L’intitulé doit contenir au moins 4 caractères.";
    } elseif (mb_strlen($description) < 10) {
      $error = "La description doit contenir au moins 10 caractères.";
    } else {
      $up = $pdo->prepare("UPDATE tickets SET title = ?, description = ? WHERE id = ? AND user_id = ?");
      $up->execute([$title, $description, $ticket['id'], $_SESSION['user_id']]);
      $ticket['title'] = $title;
      $ticket['description'] = $description;
      $success = "Modifications enregistrées.";
    }
  }
}

// Helper badge
function status_badge($s) {
  $map = ['open'=>'Ouvert','in_progress'=>'En cours','resolved'=>'Résolu','closed'=>'Fermé'];
  $label = $map[$s] ?? $s;
  return '<span class="badge badge-'.$s.'">'.$label.'</span>';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Ticket #<?= (int)$ticket['id'] ?> - RMS-Ticket</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../CreateTickets/style.css">
</head>
<body>
  <div class="noise" aria-hidden="true"></div>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="../HomePage/index.php" class="nav-brand" style="text-decoration:none;color:inherit;">
        <div class="brand-logo"><span>R</span></div>
        <span class="brand-text">RMS-Ticket</span>
      </a>
      <div class="nav-menu">
        <a href="../HomePage/index.php" class="nav-link">Accueil</a>
        <a href="../CreateTickets/create_ticket.php" class="nav-link">Créer un ticket</a>
        <a href="../Tickets/my_tickets.php" class="nav-link active">Mes tickets</a>
        <?php if (!empty($_SESSION['droit']) && $_SESSION['droit']>=1): ?>
          <a href="../AdminPanel/adminpanel.php" class="nav-link">Administration</a>
        <?php endif; ?>
        <a href="../HomePage/logout.php" class="nav-link">Déconnexion</a>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="form-container">
      <div class="form-card">
        <div class="form-header">
          <h1>Ticket #<?= (int)$ticket['id'] ?></h1>
          <p class="form-subtitle">
            Statut : <?= status_badge($ticket['status']) ?>
          </p>
        </div>

        <?php if ($success): ?>
          <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Infos non éditables -->
        <div class="user-info" style="margin-bottom:1rem;">
          <div class="field">
            <label>Catégorie</label>
            <input type="text" value="<?= htmlspecialchars(ucfirst($ticket['category'])) ?>" disabled>
          </div>
          <div class="field">
            <label>Type</label>
            <input type="text" value="<?= htmlspecialchars(ucfirst($ticket['type'])) ?>" disabled>
          </div>
        </div>

        <!-- Formulaire d’édition limité -->
        <form method="post" class="ticket-form" style="margin-top:.5rem;">
          <div class="field">
            <label for="title">Intitulé <span class="required">*</span></label>
            <input id="title" name="title" type="text" required maxlength="255"
                   value="<?= htmlspecialchars($ticket['title']) ?>"
                   placeholder="Intitulé du ticket">
          </div>

          <div class="field">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description" name="description" required maxlength="2000"
                      placeholder="Décrivez le problème..."><?= htmlspecialchars($ticket['description']) ?></textarea>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button class="submit-btn" type="submit" name="action" value="update">
              Enregistrer
            </button>
            <?php if ($ticket['status'] !== 'closed'): ?>
              <button class="submit-btn" type="submit" name="action" value="close" style="background:rgba(239,68,68,.9)">
                Clôturer le ticket
              </button>
            <?php endif; ?>
            <a href="my_tickets.php" class="submit-btn" style="background:rgba(255,255,255,0.08);text-decoration:none;display:inline-flex;align-items:center;">
              Retour à la liste
            </a>
          </div>
        </form>

        <!-- Métadonnées -->
        <div style="margin-top:1.25rem; color:var(--muted); font-size:.9rem;">
          <?php $c=new DateTime($ticket['created_at']); ?>
          Créé le : <strong><?= $c->format('d/m/Y H:i') ?></strong>
          <?php if (!empty($ticket['updated_at'])): $u=new DateTime($ticket['updated_at']); ?>
            — Dernière mise à jour : <strong><?= $u->format('d/m/Y H:i') ?></strong>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script src="../CreateTickets/app.js"></script>
</body>
</html>
