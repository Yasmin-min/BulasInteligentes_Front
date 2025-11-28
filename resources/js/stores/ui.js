import { defineStore } from 'pinia';

let notificationSeed = 0;

export const useUiStore = defineStore('ui', {
    state: () => ({
        sidebarOpen: false,
        notifications: [],
    }),
    actions: {
        toggleSidebar(force) {
            if (typeof force === 'boolean') {
                this.sidebarOpen = force;
                return;
            }
            this.sidebarOpen = !this.sidebarOpen;
        },
        notify({ title, message, tone = 'info', timeout = 5000 }) {
            const id = ++notificationSeed;
            this.notifications.push({ id, title, message, tone });

            if (timeout) {
                setTimeout(() => this.dismiss(id), timeout);
            }
        },
        dismiss(id) {
            this.notifications = this.notifications.filter((item) => item.id !== id);
        },
        hydrate() {
            // Reserved for future persisted UI preferences
        },
    },
});
