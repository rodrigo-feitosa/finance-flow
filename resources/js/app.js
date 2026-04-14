import './bootstrap';

function toastComponent() {
    return {
        show: false,
        message: '',
        type: 'success',

        init() {
            // 1. TOAST VIA SESSION (redirect/login/logout)
            const sessionToast = JSON.parse(this.$el.dataset.toast || 'null');

            if (sessionToast) {
                this.showToast(sessionToast);
            }

            // 2. TOAST VIA LIVEWIRE / JS EVENT (sem reload)
            window.addEventListener('toast', (event) => {
                this.showToast(event.detail);
            });
        },

        showToast(toast) {
            this.message = toast.message;
            this.type = toast.type || 'success';
            this.show = true;

            setTimeout(() => {
                this.show = false;
            }, 3000);
        }
    }
}

window.toastComponent = toastComponent;