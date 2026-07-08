/* ═══════════════════════════════════════════════════════
   UTILS.JS — Shared JS utilities for QuaTation
   Load on every authenticated page (defer)
   ═══════════════════════════════════════════════════════ */

window.QT = window.QT || {};

/* ──────────────────────────────────
   TOAST NOTIFICATION SYSTEM
   ────────────────────────────────── */
(function() {
    function getContainer() {
        let c = document.getElementById('qt-toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'qt-toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    QT.toast = function(message, type, duration) {
        type = type || 'info';
        duration = duration === undefined ? 3500 : duration;

        const icons = { success: '✓', error: '✕', warn: '⚠', info: 'ℹ' };
        const container = getContainer();

        const toast = document.createElement('div');
        toast.className = 'qt-toast ' + type;
        toast.innerHTML =
            '<span class="qt-toast-icon">' + (icons[type] || 'ℹ') + '</span>' +
            '<span class="qt-toast-msg">' + message + '</span>' +
            '<button class="qt-toast-close" aria-label="Dismiss">×</button>';

        toast.querySelector('.qt-toast-close').addEventListener('click', function() {
            dismiss(toast);
        });

        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(function() { dismiss(toast); }, duration);
        }
    };

    function dismiss(toast) {
        if (toast.classList.contains('hiding')) return;
        toast.classList.add('hiding');
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 250);
    }

    QT.toastSuccess = function(msg) { QT.toast(msg, 'success'); };
    QT.toastError   = function(msg) { QT.toast(msg, 'error'); };
    QT.toastWarn    = function(msg) { QT.toast(msg, 'warn'); };
    QT.toastInfo    = function(msg) { QT.toast(msg, 'info'); };
})();

/* ──────────────────────────────────
   DEBOUNCE
   ────────────────────────────────── */
QT.debounce = function(fn, delay) {
    delay = delay || 300;
    var timer;
    return function() {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function() { fn.apply(context, args); }, delay);
    };
};

/* ──────────────────────────────────
   CUSTOM CONFIRM DIALOG
   ────────────────────────────────── */
QT.confirm = function(message, onConfirm, onCancel) {
    var overlay = document.createElement('div');
    overlay.className = 'qt-confirm-overlay';
    overlay.innerHTML =
        '<div class="qt-confirm-box">' +
            '<span class="qt-confirm-icon">🗑️</span>' +
            '<div class="qt-confirm-title">Confirm Delete</div>' +
            '<div class="qt-confirm-message">' + message + '</div>' +
            '<div class="qt-confirm-btns">' +
                '<button class="qt-confirm-cancel">Cancel</button>' +
                '<button class="qt-confirm-ok">Delete</button>' +
            '</div>' +
        '</div>';

    document.body.appendChild(overlay);

    function close() {
        overlay.style.animation = 'fadeOut .15s ease forwards';
        setTimeout(function() {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }, 160);
    }

    overlay.querySelector('.qt-confirm-cancel').addEventListener('click', function() {
        close();
        if (onCancel) onCancel();
    });

    overlay.querySelector('.qt-confirm-ok').addEventListener('click', function() {
        close();
        if (onConfirm) onConfirm();
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            close();
            if (onCancel) onCancel();
        }
    });

    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            close();
            if (onCancel) onCancel();
            document.removeEventListener('keydown', escHandler);
        }
    });
};

/* ──────────────────────────────────
   CURRENCY FORMATTER (Indian locale)
   ────────────────────────────────── */
QT.formatINR = function(n) {
    n = parseFloat(n) || 0;
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2
    }).format(n);
};

/* ──────────────────────────────────
   THEME TOGGLE
   ────────────────────────────────── */
QT.toggleTheme = function() {
    var html = document.documentElement;
    var isLight = html.classList.toggle('light-mode');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');

    var btn = document.getElementById('themeToggleBtn');
    if (btn) {
        btn.textContent = isLight ? '☀️' : '🌙';
        btn.title = isLight ? 'Switch to dark mode' : 'Switch to light mode';
    }
};

/* ──────────────────────────────────
   PASSWORD VISIBILITY TOGGLE
   ────────────────────────────────── */
QT.togglePasswordVisibility = function(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    if (btn) {
        btn.textContent = isText ? '👁' : '🙈';
        btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
    }
};

/* ──────────────────────────────────
   SKELETON LOADERS
   ────────────────────────────────── */
QT.showSkeletons = function(containerId, count, type) {
    var container = document.getElementById(containerId);
    if (!container) return;
    type = type || 'card';
    var html = '';
    for (var i = 0; i < (count || 3); i++) {
        if (type === 'stat') {
            html += '<div class="skeleton skeleton-stat"></div>';
        } else {
            html += '<div class="skeleton skeleton-card"></div>';
        }
    }
    container.innerHTML = html;
};

QT.hideSkeletons = function(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var skeletons = container.querySelectorAll('.skeleton');
    skeletons.forEach(function(s) {
        if (s.parentNode) s.parentNode.removeChild(s);
    });
};

/* ──────────────────────────────────
   APPLY SAVED THEME ON LOAD
   ────────────────────────────────── */
(function() {
    var btn = document.getElementById('themeToggleBtn');
    if (btn) {
        var isLight = document.documentElement.classList.contains('light-mode');
        btn.textContent = isLight ? '☀️' : '🌙';
        btn.title = isLight ? 'Switch to dark mode' : 'Switch to light mode';
    }
})();
