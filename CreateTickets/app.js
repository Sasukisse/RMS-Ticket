// app.js — propre + dropdown custom pour #category
document.addEventListener("DOMContentLoaded", () => {
  const form      = document.querySelector("#ticketForm");
  const title     = document.querySelector("#title");
  const desc      = document.querySelector("#description");
  const counter   = document.querySelector("#descCounter");
  const category  = document.querySelector("#category");
  const typeInputs= document.querySelectorAll('input[name="type"]');
  const submitBtn = document.querySelector("#submitBtn");

  // --- Compteur de caractères ---
  if (desc && counter) {
    const updateCounter = () => {
      const len = desc.value.length;
      const maxLen = parseInt(desc.getAttribute("maxlength") || "500", 10);
      counter.textContent = `${len}/${maxLen}`;
      counter.style.color =
        len > maxLen * 0.9 ? "#ef4444" :
        len > maxLen * 0.7 ? "#f59e0b" :
        "#94a3b8";
    };
    desc.addEventListener("input", updateCounter);
    updateCounter();
  }

  // --- Validation en temps réel ---
  const validate = () => {
    const titleValid = title ? title.value.trim().length >= 4 : true;
    const descValid  = desc  ? desc.value.trim().length  >= 10 : true;
    const categoryValid = category ? category.value !== "" : true;
    const typeValid = typeInputs.length ? Array.from(typeInputs).some(i => i.checked) : true;

    const isValid = titleValid && descValid && categoryValid && typeValid;
    if (submitBtn) {
      submitBtn.disabled = !isValid;
      submitBtn.style.opacity = isValid ? "1" : "0.6";
    }
    return isValid;
  };

  [title, desc, category].forEach(el => {
    if (!el) return;
    el.addEventListener("input", validate);
    el.addEventListener("blur", validate);
    if (el.tagName === "SELECT") el.addEventListener("change", validate);
  });
  typeInputs.forEach(input => input.addEventListener("change", validate));
  validate();

  // --- Animation d'entrée de la carte ---
  const formCard = document.querySelector(".form-card");
  if (formCard) {
    formCard.style.opacity = "0";
    formCard.style.transform = "translateY(30px)";
    formCard.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    setTimeout(() => {
      formCard.style.opacity = "1";
      formCard.style.transform = "translateY(0)";
    }, 100);
  }

  // --- Auto-resize du textarea ---
  if (desc) {
    const autoresize = function () {
      this.style.height = "auto";
      this.style.height = Math.max(120, this.scrollHeight) + "px";
    };
    desc.addEventListener("input", autoresize);
    autoresize.call(desc);
  }

  // --- Focus styles (helper) ---
  document.querySelectorAll("input, textarea, select").forEach(input => {
    input.addEventListener("focus", function () {
      this.parentElement?.classList.add("focused");
    });
    input.addEventListener("blur", function () {
      this.parentElement?.classList.remove("focused");
    });
  });

  // --- Soumission du formulaire ---
  if (form) {
    form.addEventListener("submit", (e) => {
      if (!validate()) {
        e.preventDefault();
        showNotification("Veuillez remplir tous les champs obligatoires.", "error");
        return;
      }
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
          Création en cours...
        `;
      }
    });
  }

  // --- Notifications ---
  function showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        ${type === "error"
          ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
          : '<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>'}
      </svg>
      ${message}
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add("show"), 50);
    setTimeout(() => {
      notification.classList.remove("show");
      setTimeout(() => notification.remove(), 300);
    }, 4000);
  }

  // --- Styles embarqués (notifications + dropdown custom) ---
  const style = document.createElement("style");
  style.textContent = `
    .notification {
      position: fixed; top: 100px; right: 20px;
      background: var(--glass-bg);
      border: 1px solid var(--glass-stroke);
      border-radius: 12px; padding: 1rem 1.25rem;
      display: flex; align-items: center; gap: .75rem;
      backdrop-filter: blur(16px); z-index: 10000;
      transform: translateX(400px); opacity: 0; transition: all .3s ease;
      max-width: 400px; box-shadow: var(--shadow-strong);
    }
    .notification.show { transform: translateX(0); opacity: 1; }
    .notification-error { border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.15); color:#ef4444; }
    .notification-success { border-color: rgba(16,185,129,.3); background: rgba(16,185,129,.15); color:#10b981; }
    @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }
    .field.focused label { color: var(--primary); }
    .field.focused input:focus,
    .field.focused textarea:focus,
    .field.focused select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(99,102,241,.15);
    }

    /* ===== Styles du dropdown custom pour #category ===== */
    .enhanced-select { position: relative; width: 100%; }
    .enhanced-select > select.es-native-hidden{
      position: absolute !important; left:0 !important; top:0 !important;
      width:0 !important; height:0 !important; opacity:0 !important;
      pointer-events:none !important; margin:0 !important; padding:0 !important;
      border:0 !important; clip: rect(0 0 0 0) !important; clip-path: inset(50%) !important;
      overflow:hidden !important; white-space:nowrap !important; z-index:-1 !important;
    }
    .es-btn{
      width:100%; height:48px; display:flex; align-items:center; justify-content:space-between;
      gap:.75rem; padding:12px 16px; border-radius:12px;
      border:1px solid var(--input-border); background:var(--input-bg); color:var(--text);
      font-size:.95rem; font-weight:600; cursor:pointer;
      transition:border-color .2s, box-shadow .2s, background .2s, transform .06s;
    }
    .es-btn:hover{ border-color:rgba(255,255,255,.28); background:rgba(255,255,255,.08); }
    .es-btn:focus-visible{ outline:none; border-color:var(--primary); box-shadow:0 0 0 6px rgba(99,102,241,.06); }
    .es-btn:active{ transform: translateY(1px); }
    .es-caret{ width:18px; height:18px; flex:0 0 18px; opacity:.9;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23a78bfa' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
      background-repeat:no-repeat; background-position:center; transition:transform .18s ease;
    }
    .es-open .es-caret{ transform: rotate(180deg); }
    .es-panel{
      position:absolute; left:0; right:0; top:calc(100% + 8px);
      background: linear-gradient(180deg, rgba(15,23,42,.98), rgba(17,24,39,.98));
      border:1px solid rgba(255,255,255,.10); border-radius:12px;
      box-shadow:0 18px 50px rgba(0,0,0,.55); padding:6px; max-height:280px; overflow:auto;
      z-index:40; transform: translateY(6px); opacity:0; pointer-events:none; transition: transform .16s, opacity .16s;
    }
    .es-open .es-panel{ transform: translateY(0); opacity:1; pointer-events:auto; }
    .es-item{ display:flex; align-items:center; gap:.6rem; padding:10px 10px; border-radius:10px; color:var(--text); font-weight:600; cursor:pointer; }
    .es-item[aria-disabled="true"]{ opacity:.55; cursor:not-allowed; }
    .es-item:hover:not([aria-disabled="true"]), .es-item[aria-selected="true"]{
      background: linear-gradient(90deg, rgba(99,102,241,.12), rgba(79,70,229,.08));
    }
    .es-emoji{ width:1.05rem; display:inline-block; text-align:center; }
  `;
  document.head.appendChild(style);

 // ===== Dropdown custom pour <select id="category"> =====
(function enhanceCategorySelect(){
  const native = document.querySelector('#category');
  if (!native) return;

  const wrap = document.createElement('div');
  wrap.className = 'enhanced-select';
  native.parentNode.insertBefore(wrap, native);
  native.classList.add('es-native-hidden');
  wrap.appendChild(native);

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'es-btn';
  btn.setAttribute('aria-haspopup', 'listbox');
  btn.setAttribute('aria-expanded', 'false');

  const btnLabel = document.createElement('span');
  btnLabel.textContent = native.options[native.selectedIndex]?.text || 'Choisir une catégorie';

  const caret = document.createElement('span');
  caret.className = 'es-caret';

  btn.appendChild(btnLabel);
  btn.appendChild(caret);
  wrap.appendChild(btn);

  const panel = document.createElement('div');
  panel.className = 'es-panel';
  panel.setAttribute('role', 'listbox');
  wrap.appendChild(panel);

  // --- build items (placeholder exclus) ---
  const buildItems = () => {
    panel.innerHTML = '';
    Array.from(native.options).forEach((opt) => {
      if (opt.disabled && opt.value === '') return; // ne pas ajouter le placeholder

      const item = document.createElement('div');
      item.className = 'es-item';
      item.setAttribute('role', 'option');
      item.dataset.value = opt.value;

      // emoji + texte séparés par le premier espace
      const label = opt.text || '';
      const firstSpace = label.indexOf(' ');
      const emoji = document.createElement('span'); emoji.className = 'es-emoji';
      const text  = document.createElement('span');
      if (firstSpace > 0) { emoji.textContent = label.slice(0, firstSpace); text.textContent = label.slice(firstSpace + 1).trim(); }
      else { emoji.textContent = ''; text.textContent = label; }

      item.appendChild(emoji);
      item.appendChild(text);

      if (opt.selected) item.setAttribute('aria-selected', 'true');

      item.addEventListener('click', () => {
        selectValue(opt.value, opt.text);
        close(); // on fermera proprement (voir close() plus bas)
      });

      panel.appendChild(item);
    });
  };

  const selectValue = (value, text) => {
    native.value = value;
    btnLabel.textContent = text;
    panel.querySelectorAll('.es-item').forEach(it => it.removeAttribute('aria-selected'));
    const current = panel.querySelector(`.es-item[data-value="${CSS.escape(value)}"]`);
    if (current) current.setAttribute('aria-selected', 'true');
    native.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const open = () => {
    if (wrap.classList.contains('es-open')) return;
    wrap.classList.add('es-open');
    btn.setAttribute('aria-expanded', 'true');
    const current = panel.querySelector('.es-item[aria-selected="true"]') || panel.querySelector('.es-item:not([aria-disabled="true"])');
    current?.scrollIntoView({ block: 'nearest' });
  };

  // ⚠️ Ne re-focus le bouton QUE si on fermait vraiment le menu
  const close = (focusBtn = true) => {
    if (!wrap.classList.contains('es-open')) return;  // <-- évite de voler le focus quand déjà fermé
    wrap.classList.remove('es-open');
    btn.setAttribute('aria-expanded', 'false');
    if (focusBtn) btn.focus();
  };

  btn.addEventListener('click', () => {
    wrap.classList.contains('es-open') ? close() : open();
  });

  // Fermer uniquement si le menu est OUVERT, et sans re-focus si clic extérieur
  document.addEventListener('click', (e) => {
    if (wrap.classList.contains('es-open') && !wrap.contains(e.target)) {
      close(false); // <-- pas de focus forcé -> pas de scroll
    }
  });

  // Escape global : ne ferme que si ouvert (sinon rien)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  // (supprimé) : ne plus forcer le focus sur le panneau au clic
  // panel.addEventListener('click', () => panel.focus());  <-- ENLEVÉ

  // clavier dans le panneau (facultatif mais conservé)
  panel.tabIndex = -1;
  let activeIndex = -1;
  const focusMove = (dir) => {
    const items = Array.from(panel.querySelectorAll('.es-item:not([aria-disabled="true"])'));
    if (!items.length) return;
    if (activeIndex === -1) {
      const current = panel.querySelector('.es-item[aria-selected="true"]');
      activeIndex = Math.max(0, items.indexOf(current));
    } else {
      activeIndex = (activeIndex + dir + items.length) % items.length;
    }
    items[activeIndex].focus();
  };
  panel.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); focusMove(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); focusMove(-1); }
    else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const el = document.activeElement;
      if (el?.classList.contains('es-item')) el.click();
    } else if (e.key === 'Escape') { e.preventDefault(); close(); }
  });

  buildItems();

  native.addEventListener('change', () => {
    const opt = native.options[native.selectedIndex];
    btnLabel.textContent = opt?.text || 'Choisir une catégorie';
    panel.querySelectorAll('.es-item').forEach(it => it.removeAttribute('aria-selected'));
    const current = panel.querySelector(`.es-item[data-value="${CSS.escape(native.value)}"]`);
    current?.setAttribute('aria-selected', 'true');
  });
})();

});
