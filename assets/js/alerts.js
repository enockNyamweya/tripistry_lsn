/**
 * Alert Controller: Manages flash messages and toasts
 */
class AlertController {
    static init() {
        const flashMessages = document.querySelectorAll('.alert-success, .alert-error');
        
        flashMessages.forEach(msg => {
            // Animate in
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            msg.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            
            requestAnimationFrame(() => {
                msg.style.opacity = '1';
                msg.style.transform = 'translateY(0)';
            });

            // Auto dismiss after 4 seconds
            setTimeout(() => this.dismiss(msg), 4000);
            
            // Allow manual dismiss on click
            msg.style.cursor = 'pointer';
            msg.addEventListener('click', () => this.dismiss(msg));
        });
    }

    static dismiss(element) {
        element.style.opacity = '0';
        element.style.transform = 'translateY(-10px)';
        setTimeout(() => element.remove(), 400);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    AlertController.init();
});
