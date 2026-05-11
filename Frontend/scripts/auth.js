// Authentication Logic

const Auth = {
    redirectIfNotAuthenticated() {
        if (!Utils.isLoggedIn()) {
            window.location.href = '/Frontend/pages/login.html';
        }
    },

    redirectIfAuthenticated() {
        if (Utils.isLoggedIn()) {
            const user = Utils.getUser();
            const redirectUrl = user && user.role === 'admin' ? '/Frontend/pages/admin/index.html' : '/Frontend/pages/dashboard.html';
            window.location.href = redirectUrl;
        }
    },

    redirectIfNotAdmin() {
        if (!Utils.isLoggedIn() || !Utils.isAdmin()) {
            window.location.href = '/Frontend/pages/login.html';
        }
    },

    async handleRegister(formData) {
        try {
            const response = await API.register(formData);

            if (response.success) {
                // Store token and user data
                Utils.setToken(response.data.token);
                Utils.setUser(response.data);

                Utils.showNotification('Registration successful! Redirecting...', 'success', 2000);

                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = '/Frontend/pages/dashboard.html';
                }, 2000);

                return response.data;
            }
        } catch (error) {
            console.error('Registration error:', error);
            throw error;
        }
    },

    async handleLogin(email, password) {
        try {
            const response = await API.login(email, password);

            if (response.success) {
                // Store token and user data
                Utils.setToken(response.data.token);
                Utils.setUser(response.data);

                Utils.showNotification('Login successful!', 'success', 1000);

                // Redirect based on role
                const redirectUrl = response.data.role === 'admin' ? '/Frontend/pages/admin/index.html' : '/Frontend/pages/dashboard.html';

                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);

                return response.data;
            }
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    },

    async logout() {
        try {
            await API.logout();
            Utils.clearToken();
            Utils.showNotification('Logged out successfully', 'success', 1000);

            setTimeout(() => {
                window.location.href = '/Frontend/pages/login.html';
            }, 1000);
        } catch (error) {
            console.error('Logout error:', error);
            Utils.clearToken();
            window.location.href = '/Frontend/pages/login.html';
        }
    },

    updateNavigation() {
        const userMenu = document.getElementById('user-menu');
        if (!userMenu) return;

        if (Utils.isLoggedIn()) {
            const user = Utils.getUser();
            userMenu.innerHTML = `
                <span>Welcome, ${user.name}</span>
                <button class="btn btn-outline" onclick="Auth.logout()">Logout</button>
            `;
        } else {
            userMenu.innerHTML = `
                <a href="/Frontend/pages/login.html" class="btn btn-primary">Login</a>
                <a href="/Frontend/pages/register.html" class="btn btn-secondary">Register</a>
            `;
        }
    }
};

// Update navigation on page load
document.addEventListener('DOMContentLoaded', () => {
    Auth.updateNavigation();
});
