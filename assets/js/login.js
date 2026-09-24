document.querySelector('.auth-form').addEventListener('submit', function () {
    const button = this.querySelector('button[type="submit"]');

    button.disabled = true;
    button.textContent = 'Signing in...';
});