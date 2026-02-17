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
  // Recharger statut actuel depuis la BD pour éviter la modification concurrente
  $st = $pdo->prepare("SELECT status FROM tickets WHERE id = ? AND user_id = ?");
  $st->execute([$ticket['id'], $_SESSION['user_id']]);
  $currentStatus = $st->fetchColumn();
  if ($currentStatus === false) {
    http_response_code(404);
    exit('Ticket introuvable.');
  }
  $ticket['status'] = $currentStatus; // synchroniser

  // Si fermé, aucune modification n'est autorisée
  if ($ticket['status'] === 'closed') {
    $error = "Ce ticket est fermé : aucune modification n'est autorisée.";
  } else {
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
  <title>Ticket #<?= (int)$ticket['id'] ?> - RMS Ticket</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../HomePage/assets/css/homepage.css">
  <link rel="stylesheet" href="../CreateTickets/style.css">
</head>
<body>
  <div class="noise" aria-hidden="true"></div>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="../HomePage/index.php" class="nav-brand" style="text-decoration:none;color:inherit;">
        <div class="brand-logo"><span>R</span></div>
        <span class="brand-text">RMS Ticket</span>
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
        <?php $isClosed = ($ticket['status'] === 'closed'); ?>
        <form method="post" class="ticket-form" style="margin-top:.5rem;">
          <div class="field">
            <label for="title">Intitulé <span class="required">*</span></label>
            <input id="title" name="title" type="text" required maxlength="255"
                   value="<?= htmlspecialchars($ticket['title']) ?>"
                   placeholder="Intitulé du ticket" <?= $isClosed ? 'disabled' : '' ?>>
          </div>

          <div class="field">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description" name="description" required maxlength="2000"
                      placeholder="Décrivez le problème..." <?= $isClosed ? 'disabled' : '' ?>><?= htmlspecialchars($ticket['description']) ?></textarea>
          </div>

          <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
            <button class="submit-btn" type="submit" name="action" value="update" <?= $isClosed ? 'disabled aria-disabled="true"' : '' ?>>
              Enregistrer
            </button>
            <?php if (!$isClosed): ?>
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

        <!-- Chat lié au ticket -->
        <div id="ticket-chat" style="margin-top:1.25rem;">
          <h3>Discussion</h3>
          <div id="chat-box" style="border:1px solid rgba(255,255,255,0.07);padding:.8rem;height:300px;overflow:auto;background:rgba(0,0,0,0.03);">
            <!-- messages injectés ici -->
            <div style="color:var(--muted);">Chargement des messages…</div>
          </div>

          <form id="chat-form" style="display:flex;gap:.5rem;margin-top:.6rem;align-items:center;" onsubmit="return false;">
            <input id="chat-input" name="message" type="text" placeholder="Écrire un message..." style="flex:1;min-width:0;padding:.6rem;border-radius:6px;border:1px solid rgba(255,255,255,0.06);" autocomplete="off" <?= $isClosed ? 'disabled' : '' ?> />
            <button id="chat-send" type="button" class="submit-btn" style="width:auto;min-width:88px;flex-shrink:0;padding:10px 14px;" <?= $isClosed ? 'disabled aria-disabled="true"' : '' ?>>Envoyer</button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="../CreateTickets/app.js"></script>
  <script>
  (function(){
    const ticketId = <?= (int)$ticket['id'] ?>;
    const api = 'chat_api.php';
    const box = document.getElementById('chat-box');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');

    function esc(s){
      return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    function render(messages){
      if (!Array.isArray(messages)) return;
      box.innerHTML = messages.map(m=>{
        const who = m.username ? esc(m.username) : ('ID:'+m.sender_id);
        const time = new Date(m.created_at).toLocaleString();
        return '<div style="margin-bottom:.6rem;"><strong>'+who+'</strong> <small style="color:#777;margin-left:.4rem;">'+time+'</small><div style="margin-top:.25rem;">'+esc(m.message)+'</div></div>';
      }).join('');
      box.scrollTop = box.scrollHeight;
    }

    async function fetchMessages(){
      try{
        const res = await fetch(api+'?ticket_id='+ticketId);
        if (!res.ok) return;
        const data = await res.json();
        render(data);
        // après avoir chargé les messages, marquer comme lus
        try { fetch('mark_read.php', { method: 'POST', body: new URLSearchParams({ ticket_id: ticketId }) }); } catch(e){}
      }catch(e){console.error(e)}
    }

    async function sendMessage(){
      const v = input.value.trim();
      if (!v) return;
      try{
        const params = new URLSearchParams();
        params.append('ticket_id', ticketId);
        params.append('message', v);
        const res = await fetch(api, { method: 'POST', body: params });
        if (!res.ok) {
          alert('Erreur lors de l\'envoi');
          return;
        }
        input.value = '';
        fetchMessages();
      }catch(e){console.error(e)}
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function(e){ if (e.key === 'Enter') sendMessage(); });

    // polling régulier
  fetchMessages();
  // aussi marquer comme lu dès l'ouverture de la page
  try { fetch('mark_read.php', { method: 'POST', body: new URLSearchParams({ ticket_id: ticketId }) }); } catch(e){}
    setInterval(fetchMessages, 2500);
  })();
  </script>
</body>
</html>
