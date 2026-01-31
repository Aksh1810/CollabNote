document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('Email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('error-text-email');
    const passwordError = document.getElementById('error-text-password');

    loginForm.addEventListener('submit', (e) => {
        let valid = true;

        if (!emailInput.value.includes('@')) {
            emailError.classList.remove('hidden');
            valid = false;
        } else {
            emailError.classList.add('hidden');
        }

        if (passwordInput.value.length < 6) {
            passwordError.classList.remove('hidden');
            valid = false;
        } else {
            passwordError.classList.add('hidden');
        }

        if (!valid) {
            e.preventDefault();
        }
    });
});
