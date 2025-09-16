// app.js - Amélioré pour une meilleure UX
document.addEventListener("DOMContentLoaded", () => {
    const desc = document.querySelector("#description");
    const counter = document.querySelector("#descCounter");
    const submitBtn = document.querySelector("#submitBtn");
    const title = document.querySelector("#title");
    const category = document.querySelector("#category");
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const form = document.querySelector("#ticketForm");

    // Compteur de caractères avec couleur
    desc.addEventListener("input", () => {
        const len = desc.value.length;
        const maxLen = 500;
        counter.textContent = `${len}/${maxLen}`;
        
        // Changer la couleur selon le nombre de caractères
        if (len > maxLen * 0.9) {
            counter.style.color = '#ef4444'; // Rouge
        } else if (len > maxLen * 0.7) {
            counter.style.color = '#f59e0b'; // Orange
        } else {
            counter.style.color = '#94a3b8'; // Gris par défaut
        }
    });

    // Validation en temps réel
    const validate = () => {
        const titleValid = title.value.trim().length >= 4;
        const descValid = desc.value.trim().length >= 10;
        const categoryValid = category.value !== '';
        const typeValid = Array.from(typeInputs).some(input => input.checked);
        
        const isValid = titleValid && descValid && categoryValid && typeValid;
        
        submitBtn.disabled = !isValid;
        
        // Mettre à jour l'apparence du bouton
        if (isValid) {
            submitBtn.style.opacity = '1';
        } else {
            submitBtn.style.opacity = '0.6';
        }
    };

    // Ajouter des événements de validation
    [title, desc, category].forEach(el => {
        el.addEventListener("input", validate);
        el.addEventListener("blur", validate);
    });

    typeInputs.forEach(input => {
        input.addEventListener("change", validate);
    });

    // Validation initiale
    validate();

    // Animation d'entrée pour le formulaire
    const formCard = document.querySelector('.form-card');
    if (formCard) {
        formCard.style.opacity = '0';
        formCard.style.transform = 'translateY(30px)';
        formCard.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            formCard.style.opacity = '1';
            formCard.style.transform = 'translateY(0)';
        }, 100);
    }

    // Auto-resize pour le textarea
    desc.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.max(120, this.scrollHeight) + 'px';
    });

    // Feedback visuel pour les champs
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Gestion de la soumission du formulaire
    form.addEventListener('submit', function(e) {
        // Validation finale
        const titleValid = title.value.trim().length >= 4;
        const descValid = desc.value.trim().length >= 10;
        const categoryValid = category.value !== '';
        const typeValid = Array.from(typeInputs).some(input => input.checked);

        if (!titleValid || !descValid || !categoryValid || !typeValid) {
            e.preventDefault();
            
            // Afficher un message d'erreur
            showNotification('Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        // Désactiver le bouton et afficher un loader
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                <path d="M21 12a9 9 0 11-6.219-8.56"/>
            </svg>
            Création en cours...
        `;
    });

    // Fonction pour afficher des notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'error' ? 
                    '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>' :
                    '<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>'
                }
            </svg>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Supprimer après 4 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // Ajouter les styles CSS pour les notifications et animations
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-stroke);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            backdrop-filter: blur(16px);
            z-index: 10000;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 400px;
            box-shadow: var(--shadow-strong);
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-error {
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        
        .notification-success {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .field.focused label {
            color: var(--primary);
        }
        
        .field.focused input:focus,
        .field.focused textarea:focus,
        .field.focused select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.15);
        }
    `;
    document.head.appendChild(style);

    // Pré-remplir les données utilisateur si disponibles
    const userInfo = document.querySelector('.user-info');
    if (userInfo) {
        userInfo.style.opacity = '0';
        userInfo.style.transform = 'translateY(-10px)';
        userInfo.style.transition = 'opacity 0.4s ease 0.2s, transform 0.4s ease 0.2s';
        
        setTimeout(() => {
            userInfo.style.opacity = '1';
            userInfo.style.transform = 'translateY(0)';
        }, 300);
    }

    // ---- Custom select initialization ----
    function initCustomSelects() {
        document.querySelectorAll('select').forEach(select => {
            // only apply to our form selects (avoid select elements elsewhere)
            if (!select.closest('.form-card')) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select';

            // move the native select into the wrapper and hide it visually
            select.classList.add('custom-hidden-select');
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            const selected = document.createElement('div');
            selected.className = 'selected';
            const labelSpan = document.createElement('span');
            labelSpan.className = 'label';
            labelSpan.textContent = select.options[select.selectedIndex]?.text || select.options[0]?.text || '';
            selected.appendChild(labelSpan);
            wrapper.appendChild(selected);

            const optionsBox = document.createElement('div');
            optionsBox.className = 'options';

            Array.from(select.options).forEach((opt, idx) => {
                const o = document.createElement('div');
                o.className = 'option';
                o.dataset.value = opt.value;
                o.innerHTML = `<span class="label">${opt.text}</span>`;
                if (opt.disabled) o.classList.add('disabled');
                if (opt.selected) o.classList.add('selected');
                o.addEventListener('click', () => {
                    // update native select value and displayed label
                    select.value = opt.value;
                    selected.querySelector('.label').textContent = opt.text;
                    optionsBox.querySelectorAll('.option').forEach(el => el.classList.remove('selected'));
                    o.classList.add('selected');
                    wrapper.classList.remove('open');
                    // trigger change on original select for validation listeners
                    const event = new Event('change', { bubbles: true });
                    select.dispatchEvent(event);
                });
                optionsBox.appendChild(o);
            });

            wrapper.appendChild(optionsBox);

            selected.addEventListener('click', (e) => {
                e.stopPropagation();
                // close any other open
                document.querySelectorAll('.custom-select.open').forEach(el => { if (el !== wrapper) el.classList.remove('open'); });
                wrapper.classList.toggle('open');
            });
        });

        // close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select.open').forEach(el => el.classList.remove('open'));
        });
    }

    initCustomSelects();
});