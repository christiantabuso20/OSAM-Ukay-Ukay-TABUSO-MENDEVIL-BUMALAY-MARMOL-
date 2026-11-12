document.addEventListener('DOMContentLoaded', function () {
    // Show / hide password
    var toggle = document.getElementById('togglePassword');
    var pwd = document.getElementById('password');
    if (toggle && pwd) {
        toggle.addEventListener('click', function () {
            var show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            toggle.querySelector('i').className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    // Loading state on submit (only when the browser says the form is valid)
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) { return; }
            var btn = form.querySelector('button[type="submit"]');
            if (!btn) { return; }
            btn.disabled = true;
            btn.querySelector('.btn-label').classList.add('d-none');
            btn.querySelector('.spinner-border').classList.remove('d-none');
            btn.querySelector('.btn-loading').classList.remove('d-none');
        });
    });
});
