/**
 * UI Controls: Handles tabs, accordions, and basic interactivity
 */
class UIController {
    static initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetId = e.target.getAttribute('onclick').match(/'([^']+)'/)[1];
                this.showTab(targetId);
            });
        });
    }

    static showTab(tabId) {
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(el => {
            el.style.display = 'none';
        });
        
        // Remove active state from buttons
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show target content
        const target = document.getElementById(`tab-${tabId}`);
        if (target) {
            target.style.display = 'block';
            target.style.animation = 'fadeIn 0.3s ease-in-out';
        }
        
        // Add active state to corresponding button
        const btn = document.querySelector(`[onclick*="${tabId}"]`);
        if (btn) btn.classList.add('active');
    }
}

// Global exposure for inline onclick handlers (legacy support)
window.showTab = (tabId) => UIController.showTab(tabId);

document.addEventListener('DOMContentLoaded', () => {
    // UIController.initTabs(); // Uncomment if removing inline onclicks
});
