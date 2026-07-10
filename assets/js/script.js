function toggleTheme() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('theme', newTheme);
    const button = document.getElementById('themeToggle');
    if (button) button.textContent = newTheme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode';
}

function initTheme() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        const button = document.getElementById('themeToggle');
        if (button) button.textContent = '☀️ Light Mode';
    }
}

function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-50 start-50 translate-middle p-3';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-white bg-' + type + ' border-0 fs-5 shadow-lg rounded-3';
    toastEl.style.minWidth = '360px';
    toastEl.style.maxWidth = '90vw';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    toastEl.innerHTML = '<div class="d-flex">'
        + '<div class="toast-body">' + message + '</div>'
        + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
        + '</div>';

    container.appendChild(toastEl);

    if (window.bootstrap && window.bootstrap.Toast) {
        const toast = new window.bootstrap.Toast(toastEl, { delay: 3200 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    } else {
        alert(message);
        toastEl.remove();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    const form = document.getElementById('addEntryForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch((window.BASE_PATH || '') + '/api/add_entry.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast('Added!', 'success');
                        form.reset();
                        setTimeout(() => location.reload(), 3400);
                    } else {
                        alert('Error: ' + d.error);
                    }
                })
                .catch(() => alert('Error'));
        });
    }
});
