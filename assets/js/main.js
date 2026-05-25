function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.style.display = 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(function(el) {
        el.classList.remove('active');
    });
    var target = document.getElementById('tab-' + tabId);
    if (target) target.style.display = 'block';
    var btn = document.querySelector('[onclick="showTab(\'' + tabId + '\')"]');
    if (btn) btn.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
    var flashMessages = document.querySelectorAll('.alert-success, .alert-error');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.opacity = '0';
            msg.style.transition = 'opacity 0.5s';
            setTimeout(function() { msg.remove(); }, 500);
        }, 4000);
    });
});

document.addEventListener('click', (e) => {

    const stat = e.target.closest('.stat-card h3');

    if (!stat) return;

    stat.classList.toggle('expanded');
});