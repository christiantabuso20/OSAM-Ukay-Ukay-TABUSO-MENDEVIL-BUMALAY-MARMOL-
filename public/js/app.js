document.addEventListener('DOMContentLoaded', function () {
    var shell = document.getElementById('appShell');
    var toggle = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');
    if (!shell || !toggle) { return; }

    var isMobile = function () { return window.innerWidth < 992; };

    var collapseBtn = document.getElementById('sidebarCollapse');

    // Restore the collapsed state on desktop
    try {
        if (!isMobile() && localStorage.getItem('osamSidebarCollapsed') === '1') {
            shell.classList.add('sidebar-collapsed');
        }
    } catch (e) {}

    // Collapse / expand button inside the sidebar (desktop)
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            shell.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('osamSidebarCollapsed', shell.classList.contains('sidebar-collapsed') ? '1' : '0'); } catch (e) {}
        });
    }

    // Menu button in the top bar (mobile / tablet only)
    toggle.addEventListener('click', function () {
        if (isMobile()) {
            shell.classList.toggle('sidebar-open');
        }
    });

    if (overlay) {
        overlay.addEventListener('click', function () { shell.classList.remove('sidebar-open'); });
    }
});

// Disable the submit button after a valid form is submitted (prevents double saves).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-submit').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) { return; }
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }
        });
    });
});

// ---------------------------------------------------------------------------
// Styled confirmation dialog.
// Add data-confirm-* attributes to any <form> and it will ask first:
//   data-confirm-title, data-confirm-text, data-confirm-name (bold line),
//   data-confirm-ok (button text), data-confirm-variant (danger | warning | berry),
//   data-confirm-icon (Font Awesome name, e.g. fa-trash-can).
// Add data-confirm-dynamic and listen for a "confirm:prepare" event on the form
// to fill these attributes just before the dialog opens.
// ---------------------------------------------------------------------------
(function () {
    var DEFAULT_ICONS = { danger: 'fa-trash-can', warning: 'fa-triangle-exclamation', berry: 'fa-circle-question' };
    var bypass = new WeakSet();
    var els = null, bs = null, activeForm = null, parentModal = null, confirmed = false;

    function setup() {
        if (els) { return true; }
        var modal = document.getElementById('confirmModal');
        if (!modal || typeof bootstrap === 'undefined') { return false; }
        els = {
            modal: modal,
            icon: document.getElementById('confirmIcon'),
            title: document.getElementById('confirmTitle'),
            text: document.getElementById('confirmText'),
            name: document.getElementById('confirmName'),
            ok: document.getElementById('confirmOk'),
            cancel: document.getElementById('confirmCancel')
        };
        bs = bootstrap.Modal.getOrCreateInstance(modal);

        els.cancel.addEventListener('click', function () { bs.hide(); });

        els.ok.addEventListener('click', function () {
            if (!activeForm) { return; }
            confirmed = true;
            els.ok.disabled = true;
            els.cancel.disabled = true;
            els.ok.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...';
            bypass.add(activeForm);
            activeForm.requestSubmit();
        });

        modal.addEventListener('shown.bs.modal', function () {
            (els.ok.classList.contains('btn-danger') ? els.cancel : els.ok).focus();
        });

        // Closed without confirming (Cancel, X, Esc, backdrop): go back to the form popup if there was one.
        modal.addEventListener('hidden.bs.modal', function () {
            if (!confirmed && parentModal) {
                bootstrap.Modal.getOrCreateInstance(parentModal).show();
            }
            activeForm = null; parentModal = null; confirmed = false;
            els.ok.disabled = false; els.cancel.disabled = false;
        });

        // Coming back with the browser's Back button: reset the dialog.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                els.ok.disabled = false; els.cancel.disabled = false;
                activeForm = null; parentModal = null; confirmed = false;
                bs.hide();
            }
        });
        return true;
    }

    function open(form) {
        form.dispatchEvent(new CustomEvent('confirm:prepare'));
        var d = form.dataset;
        var variant = d.confirmVariant || 'berry';

        els.icon.className = 'modal-icon is-' + variant;
        els.icon.innerHTML = '<i class="fa-solid ' + (d.confirmIcon || DEFAULT_ICONS[variant] || DEFAULT_ICONS.berry) + '"></i>';
        els.title.textContent = d.confirmTitle || 'Are you sure?';
        els.text.textContent = d.confirmText || '';
        els.text.classList.toggle('d-none', !d.confirmText);
        els.name.textContent = d.confirmName || '';
        els.name.classList.toggle('d-none', !d.confirmName);
        els.ok.className = 'btn px-4 ' + (variant === 'danger' ? 'btn-danger' : 'btn-berry');
        els.ok.textContent = d.confirmOk || 'Yes, continue';

        activeForm = form;
        confirmed = false;
        bs.show();
    }

    // Capture phase: runs before the form's own submit handlers (e.g. the "Saving..." button).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) { return; }
        if (!form.hasAttribute('data-confirm-title') && !form.hasAttribute('data-confirm-dynamic')) { return; }

        if (bypass.has(form)) { bypass.delete(form); return; } // already confirmed
        if (!setup()) { return; } // dialog missing: submit normally

        e.preventDefault();
        e.stopPropagation();

        // If the form lives inside another popup, close that first and bring it back on Cancel.
        var parent = form.closest('.modal.show');
        if (parent && parent !== els.modal) {
            parentModal = parent;
            parent.addEventListener('hidden.bs.modal', function () { open(form); }, { once: true });
            bootstrap.Modal.getInstance(parent).hide();
        } else {
            parentModal = null;
            open(form);
        }
    }, true);
})();
