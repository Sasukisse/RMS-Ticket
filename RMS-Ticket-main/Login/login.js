document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    if (!passwordInput || !togglePassword) {
        return;
    }

    const toggle = () => {
        const isPasswordType = passwordInput.type === 'password';
        passwordInput.type = isPasswordType ? 'text' : 'password';
        togglePassword.setAttribute('aria-label', isPasswordType ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        togglePassword.innerHTML = isPasswordType
            ? '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-6 0-10-8-10-8a19.31 19.31 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c6 0 10 8 10 8a19.38 19.38 0 0 1-3.16 4.19M14.12 9.88A3 3 0 0 1 12 9c-1.66 0-3 1.34-3 3 0 .54.14 1.05.38 1.49"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
            : '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>';
    };

    togglePassword.addEventListener('click', toggle);
    togglePassword.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggle();
        }
    });
});


