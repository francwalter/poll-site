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
                        alert('Added!');
                        form.reset();
                        location.reload();
                    } else {
                        alert('Error: ' + d.error);
                    }
                })
                .catch(e => alert('Error'));
        });
    }
});
