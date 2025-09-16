// =========================
// ADMIN PANEL JAVASCRIPT
// =========================

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
});

function initializeAdminPanel() {
    initializeModals();
    initializeConfirmations();
    initializeFilters();
    initializeAnimations();
    console.log('🛡️ Panneau d\'administration initialisé');
}

// =========================
// GESTION DES MODALES
// =========================
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modal.classList.contains('active')) {
            closeModal(modalId);
        } else {
            openModal(modalId);
        }
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        const firstInput = modal.querySelector('input, textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

function initializeModals() {
    // Fermer les modales en cliquant sur le fond
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Fermer les modales avec Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

// =========================
// GESTION DES UTILISATEURS
// =========================
function editUser(userData) {
    document.getElementById('edit_user_id').value = userData.id;
    document.getElementById('edit_username').value = userData.username;
    document.getElementById('edit_nom').value = userData.nom;
    document.getElementById('edit_prenom').value = userData.prenom;
    document.getElementById('edit_email').value = userData.email;
    document.getElementById('edit_telephone').value = userData.numero_telephone || '';
    document.getElementById('edit_original_droit').value = userData.droit;
    
    const droitSelect = document.getElementById('edit_droit');
    if (droitSelect) {
        droitSelect.value = userData.droit;
    }
    
    openModal('editUserModal');
}

function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
    openModal('resetPasswordModal');
}

// =========================
// CONFIRMATIONS
// =========================
function initializeConfirmations() {
    // Confirmation pour les formulaires
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const msg = form.getAttribute('data-confirm') || "Confirmer l'action ?";
            if (!window.confirm(msg)) e.preventDefault();
        });
    });
    
    // Confirmation pour les boutons et liens
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-confirm]');
        if (target && target.tagName !== 'FORM') {
            const message = target.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });
}

// =========================
// FILTRES EN TEMPS RÉEL
// =========================
function initializeFilters() {
    const searchInputs = document.querySelectorAll('input[name="search"], input[name="user_search"]');
    
    searchInputs.forEach(input => {
        let debounceTimer;
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    });
}

// =========================
// ANIMATIONS
// =========================
function initializeAnimations() {
    // Auto-hide des notifications après 4s
    setTimeout(function() {
        document.querySelectorAll('.flash').forEach(function(el) {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 400);
        });
    }, 4000);
    
    // Animation des cartes au chargement
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// =========================
// NOTIFICATIONS
// =========================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('visible'), 10);
    setTimeout(() => notification.remove(), 5000);
}

// =========================
// UTILITAIRES
// =========================
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copié dans le presse-papiers', 'success');
        }).catch(() => {
            showNotification('Erreur lors de la copie', 'error');
        });
    } else {
        // Fallback pour les anciens navigateurs
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('Copié dans le presse-papiers', 'success');
        } catch (err) {
            showNotification('Erreur lors de la copie', 'error');
        }
        document.body.removeChild(textArea);
    }
}

// =========================
// RACCOURCIS CLAVIER
// =========================
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K pour ouvrir la recherche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
});

// Exposer les fonctions globalement
window.toggleModal = toggleModal;
window.editUser = editUser;
window.resetPassword = resetPassword;
window.showNotification = showNotification;
window.copyToClipboard = copyToClipboard;
