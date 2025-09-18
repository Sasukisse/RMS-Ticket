<?php
session_start();
include '../Database/connection.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RMS Ticket - Accueil</title>
    <meta name="color-scheme" content="light dark">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/homepage.css">
</head>
<body>
    <div class="noise" aria-hidden="true"></div>
    
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="brand-logo">
                    <span>R</span>
                </div>
                <span class="brand-text">RMS Ticket</span>
            </div>
            
            <div class="nav-menu">
    <a href="index.php" class="nav-link active">Accueil</a>
    <a href="../CreateTickets/create_ticket.php" class="nav-link">Créer un ticket</a>

        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- ✅ Nouveau lien en haut -->
        <a href="../Tickets/my_tickets.php" class="nav-link" id="nav-mes-tickets" style="position:relative;">Mes tickets
            <span id="tickets-unread-badge" style="display:none;position:absolute;top:-6px;right:-10px;background:#e11d48;color:#fff;border-radius:999px;padding:2px 6px;font-size:12px;line-height:1;">0</span>
        </a>

        <?php if (!empty($_SESSION['droit']) && $_SESSION['droit'] >= 1): ?>
            <a href="../AdminPanel/adminpanel.php" class="nav-link">Administration</a>
        <?php endif; ?>

        <a href="logout.php" class="nav-link">Déconnexion</a>
    <?php else: ?>
        <a href="../Login/login.php" class="nav-link">Connexion</a>
    <?php endif; ?>
</div>

    </nav>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Bienvenue sur RMS Ticket</h1>
                <p class="hero-subtitle">
                    Votre solution complète de gestion des tickets et support technique
                </p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="welcome-user">
                        <p>Bonjour <strong><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></strong> !</p>
                        <p>Vous êtes connecté en tant que <em><?php echo htmlspecialchars($_SESSION['username']); ?></em></p>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="../CreateTickets/create_ticket.php" class="btn btn-primary">
    Créer un ticket
</a>

    <?php else: ?>
        <a href="../Login/login.php" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M21 12H9"/>
            </svg>
            Se connecter
        </a>
    <?php endif; ?>
</div>

            </div>
        </div>
        
        <div class="features-section">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"/>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                            <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                            <path d="M3 12h6"/>
                            <path d="M15 12h6"/>
                        </svg>
                    </div>
                    <h3>Gestion Simplifiée</h3>
                    <p>Créez et gérez vos tickets de support de manière intuitive et efficace.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3>Suivi en Temps Réel</h3>
                    <p>Suivez l'évolution de vos demandes en temps réel avec des notifications.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3>Sécurisé</h3>
                    <p>Vos données sont protégées avec les dernières technologies de sécurité.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Poll notifications API and update unread badge for "Mes tickets"
        (function(){
            const badge = document.getElementById('tickets-unread-badge');
            if (!badge) return;

            async function fetchUnread() {
                try {
                    const res = await fetch('../Tickets/notifications_api.php');
                    if (!res.ok) return;
                    const data = await res.json();
                    // API may return either { unread: N } or { unread_count: N } (debug/info variants)
                    const count = parseInt(data.unread_count || data.unread || 0, 10);
                    if (count > 0) {
                        badge.style.display = 'inline-block';
                        badge.textContent = count > 99 ? '99+' : String(count);
                    } else {
                        badge.style.display = 'none';
                    }
                } catch (e) {
                    // silently ignore network errors
                    console.error('Notif fetch error', e);
                }
            }

            // Initial fetch and periodic polling every 10s
            fetchUnread();
            setInterval(fetchUnread, 10000);
        })();
    </script>
    <script src="assets/js/homepage.js"></script>
</body>
</html>
