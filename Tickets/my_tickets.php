<?php
session_start();
include '../Database/connection.php';

// Sécurité : utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}

// Récupération des tickets de l'utilisateur
$sql = "SELECT id, title, status, type, category, created_at
        FROM tickets
        WHERE user_id = ?
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Badge de statut
function status_badge($s) {
    $map = ['open'=>'Ouvert','in_progress'=>'En cours','resolved'=>'Résolu','closed'=>'Fermé'];
    $label = $map[$s] ?? $s;
    return '<span class="badge badge-'.$s.'">'.$label.'</span>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Mes tickets - RMS-Ticket</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- On réutilise le CSS de CreateTickets (il contient déjà la navbar) -->
   <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="my_tickets.css">
</head>
<body>
  <div class="noise" aria-hidden="true"></div>

  <!-- NAVBAR (toujours visible) -->
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
  </div> <!-- ✅ fermeture nav-container -->
</nav>


  <!-- CONTENU -->
  <main class="main-content">
    <div class="form-container">
      <div class="form-card">
        <div class="form-header">
          <h1>📂 Mes tickets</h1>
          <p class="form-subtitle">Liste de vos demandes et incidents</p>
        </div>

        <?php if (!$tickets): ?>
          <div class="alert" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);">
            Vous n’avez pas encore de ticket.
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="tickets-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Intitulé</th>
                  <th>Catégorie</th>
                  <th>Type</th>
                  <th>Statut</th>
                  <th>Créé le</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tickets as $t): ?>
                  <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td class="title"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(ucfirst($t['category']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(ucfirst($t['type']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td><?= htmlspecialchars($t['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div style="margin-top:1rem; display:flex; gap:.5rem;">
          <a class="btn submit-btn" href="../CreateTickets/create_ticket.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
            ➕ Créer un ticket
          </a>
          <a class="btn submit-btn" href="../HomePage/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,0.08);">
            ⬅️ Retour accueil
          </a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
