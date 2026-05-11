// Utility Functions

const Utils = {
    // Token Management
    getToken() {
        return localStorage.getItem('auth_token');
    },

    setToken(token) {
        localStorage.setItem('auth_token', token);
    },

    clearToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
    },

    // User Management
    getUser() {
        const user = localStorage.getItem('user_data');
        return user ? JSON.parse(user) : null;
    },

    setUser(user) {
        localStorage.setItem('user_data', JSON.stringify(user));
    },

    isLoggedIn() {
        return !!this.getToken();
    },

    isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    // Notification System
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container') || this._createNotificationContainer();

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.style.margin = '1rem';
        alert.style.animation = 'slideIn 0.3s ease-in';

        container.appendChild(alert);

        if (duration > 0) {
            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            }, duration);
        }

        return alert;
    },

    _createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '60px';
        container.style.right = '0';
        container.style.zIndex = '999';
        container.style.maxWidth = '500px';
        document.body.appendChild(container);
        return container;
    },

    // Currency Formatting
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Date Formatting
    formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    },

    // Form Validation
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    validatePassword(password) {
        if (password.length < 8) return false;
        if (!/[A-Z]/.test(password)) return false;
        if (!/[0-9]/.test(password)) return false;
        return true;
    },

    validateNID(nid) {
        return /^\d{10,20}$/.test(nid);
    },

    validateAge(age) {
        const ageNum = parseInt(age);
        return ageNum >= 18 && ageNum <= 120;
    },

    // Utility
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    generateId() {
        return Math.random().toString(36).substr(2, 9);
    },

    parseFormData(form) {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        return data;
    }
};

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
