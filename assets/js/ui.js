class UIController {

    static init() {
        this.initTabs();
    }

    static initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const match = btn.getAttribute('onclick')?.match(/'([^']+)'/);
                if (match) this.showTab(match[1]);
            });
        });
    }

    static showTab(tabId) {
        document.querySelectorAll('.tab-content')
            .forEach(el => el.style.display = 'none');

        document.querySelectorAll('.tab-btn')
            .forEach(el => el.classList.remove('active'));

        const target = document.getElementById('tab-' + tabId);
        if (target) {
            target.style.display = 'block';
        }

        document.querySelector(`[onclick*="${tabId}"]`)
            ?.classList.add('active');
    }
}

window.showTab = (id) => UIController.showTab(id);

document.addEventListener('DOMContentLoaded', () => {
    UIController.init();
});