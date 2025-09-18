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
    // Ensure custom selects are enhanced immediately after initialization
    try { enhanceCustomSelects(); } catch (e) { console && console.error && console.error('enhanceCustomSelects', e); }
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

// =========================
// CUSTOM SELECT ENHANCEMENT
// =========================
function enhanceCustomSelects() {
    document.querySelectorAll('select[data-custom="true"]').forEach(function(original) {
        // Skip if already enhanced
        if (original.dataset.enhanced === '1') return;
        original.dataset.enhanced = '1';

    // Wrap already exists in markup (select-wrapper), we will attach an overlay
    const wrapper = original.closest('.select-wrapper') || original.parentElement;
    wrapper.style.position = 'relative';

    // Create a trigger button to replace the native visual of the select
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'select-trigger btn btn-secondary btn-sm';
    trigger.style.display = 'inline-flex';
    trigger.style.alignItems = 'center';
    trigger.style.gap = '8px';
    trigger.style.padding = '6px 10px';
    trigger.style.fontSize = '13px';
    trigger.style.cursor = 'pointer';
    trigger.textContent = original.options[original.selectedIndex] ? original.options[original.selectedIndex].text : '';
    // make trigger focusable for keyboard interaction
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    // Hide native select visually but keep it in the DOM for forms
    original.style.position = 'absolute';
    original.style.opacity = '0';
    original.style.pointerEvents = 'none';
    original.style.zIndex = '0';

    // Insert trigger before the original select
    wrapper.insertBefore(trigger, original);

        // Create overlay list
        const overlay = document.createElement('div');
        overlay.className = 'custom-select-overlay';
        overlay.style.position = 'absolute';
        overlay.style.left = '0';
        overlay.style.right = '0';
        overlay.style.top = '100%';
        overlay.style.background = '#0f172a';
        overlay.style.border = '1px solid rgba(255,255,255,0.06)';
        overlay.style.boxShadow = '0 10px 30px rgba(0,0,0,0.6)';
        overlay.style.zIndex = '3000';
        overlay.style.maxHeight = '220px';
        overlay.style.overflow = 'auto';
        overlay.style.borderRadius = '8px';
        overlay.style.padding = '6px 0';

        // Build options
        Array.from(original.options).forEach(function(opt, idx) {
            const item = document.createElement('div');
            item.className = 'custom-select-item';
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;
            item.style.padding = '8px 12px';
            item.style.cursor = 'pointer';
            item.style.color = '#e5e7eb';
            item.style.fontSize = '14px';
            item.style.whiteSpace = 'nowrap';
            item.style.overflow = 'hidden';
            item.style.textOverflow = 'ellipsis';
            if (opt.selected) item.style.background = 'rgba(255,255,255,0.06)';

            item.addEventListener('click', function(e) {
                original.value = this.dataset.value;
                // Trigger change event
                const ev = new Event('change', { bubbles: true });
                original.dispatchEvent(ev);
                // update trigger label
                const opt = original.options[original.selectedIndex];
                trigger.textContent = opt ? opt.text : '';
                // close overlay and update aria
                overlay.classList.remove('open');
                try { trigger.setAttribute('aria-expanded', 'false'); } catch (err) {}
            });

            overlay.appendChild(item);
        });

    // Append overlay to body and position it absolutely to avoid affecting layout
    document.body.appendChild(overlay);

        // Toggle overlay on trigger click (we use a button to avoid native select behavior)
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Close other overlays first
            document.querySelectorAll('.custom-select-overlay.open').forEach(function(o) {
                if (o !== overlay) o.classList.remove('open');
            });

            const rect = trigger.getBoundingClientRect();
            const isOpen = overlay.classList.contains('open');
            if (isOpen) {
                overlay.classList.remove('open');
            } else {
                overlay.style.position = 'absolute';
                overlay.style.left = Math.max(8, rect.left + window.scrollX) + 'px';
                overlay.style.top = Math.max(8, rect.bottom + window.scrollY) + 'px';
                overlay.style.width = rect.width + 'px';
                overlay.style.right = 'auto';
                overlay.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                overlay.style.zIndex = '99999';

                // focus first selected or first item
                const items = overlay.querySelectorAll('.custom-select-item');
                let focusIndex = Array.from(items).findIndex(i => i.dataset.value === original.value);
                if (focusIndex < 0) focusIndex = 0;
                items.forEach(i => i.classList.remove('focused'));
                if (items[focusIndex]) items[focusIndex].classList.add('focused');
                items[focusIndex] && items[focusIndex].scrollIntoView({ block: 'nearest' });
                // focus the trigger so key events work predictably
                trigger.focus();
            }
        });

        // Close on outside click — ensure the overlay (appended to body) closes when clicking elsewhere
        document.addEventListener('click', function(e){
            if (!overlay.classList.contains('open')) return;
            if (!overlay.contains(e.target) && !original.contains(e.target) && !trigger.contains(e.target)) {
                overlay.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        }, true);

        // Close overlay on scroll or resize to avoid mispositioning
        const closeOnScrollOrResize = function() {
            if (overlay.classList.contains('open')) {
                overlay.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        };
        window.addEventListener('scroll', closeOnScrollOrResize, true);
        window.addEventListener('resize', closeOnScrollOrResize);

        // Keyboard navigation: up/down to move, Enter to select, Escape to close
        trigger.addEventListener('keydown', function(e) {
            const isOpen = overlay.classList.contains('open');
            const items = overlay.querySelectorAll('.custom-select-item');
            if (!items.length) return;
            let idx = Array.from(items).findIndex(i => i.classList.contains('focused'));
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isOpen) { trigger.click(); return; }
                idx = Math.min(items.length - 1, (idx < 0 ? 0 : idx + 1));
                items.forEach(i => i.classList.remove('focused'));
                items[idx].classList.add('focused');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!isOpen) { trigger.click(); return; }
                idx = Math.max(0, (idx < 0 ? 0 : idx - 1));
                items.forEach(i => i.classList.remove('focused'));
                items[idx].classList.add('focused');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (!isOpen) { trigger.click(); return; }
                if (idx >= 0) {
                    items[idx].click();
                }
            } else if (e.key === 'Escape') {
                if (isOpen) { overlay.classList.remove('open'); trigger.setAttribute('aria-expanded', 'false'); }
            }
        });

        // Update trigger text when original select value changes
        original.addEventListener('change', function() {
            const opt = original.options[original.selectedIndex];
            trigger.textContent = opt ? opt.text : '';
        });
    });
}

// Enhance selects after DOM ready and after animations
setTimeout(enhanceCustomSelects, 200);
window.enhanceCustomSelects = enhanceCustomSelects;
