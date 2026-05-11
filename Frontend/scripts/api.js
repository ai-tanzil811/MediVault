// API Wrapper - Handles all API calls

const API = {
    BASE_URL: 'http://localhost/api', // Update to your server URL

    // Helper to make requests
    async request(endpoint, method = 'GET', data = null) {
        const url = this.BASE_URL + endpoint;
        const headers = {
            'Content-Type': 'application/json'
        };

        // Add authorization token if available
        const token = Utils.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const options = {
            method,
            headers
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                Utils.showNotification(result.error || 'An error occurred', 'error');
                throw new Error(result.error || 'API Error');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // AUTH ENDPOINTS
    register(formData) {
        return this.request('/auth/register.php', 'POST', formData);
    },

    login(email, password) {
        return this.request('/auth/login.php', 'POST', { email, password });
    },

    logout() {
        return this.request('/auth/logout.php', 'POST');
    },

    // PASSWORD RECOVERY ENDPOINTS
    requestPasswordReset(clinicalId) {
        return this.request('/auth/forgot-password.php', 'POST', { clinical_id: clinicalId });
    },

    verifyOTP(otp) {
        return this.request('/auth/verify-otp.php', 'POST', { otp });
    },

    resetPassword(newPassword, confirmPassword) {
        return this.request('/auth/reset-password.php', 'POST', { new_password: newPassword, confirm_password: confirmPassword });
    },

    resendOTP() {
        return this.request('/auth/resend-otp.php', 'POST');
    },

    // MEDICINES ENDPOINTS
    getMedicines(page = 1, limit = 20) {
        return this.request(`/medicines/list.php?page=${page}&limit=${limit}`);
    },

    searchMedicines(query, page = 1, limit = 20) {
        return this.request(`/medicines/search.php?q=${encodeURIComponent(query)}&page=${page}&limit=${limit}`);
    },

    getMedicine(id) {
        return this.request(`/medicines/get.php?id=${id}`);
    },

    createMedicine(medicineData) {
        return this.request('/medicines/create.php', 'POST', medicineData);
    },

    updateMedicine(id, medicineData) {
        return this.request(`/medicines/update.php?id=${id}`, 'PUT', medicineData);
    },

    deleteMedicine(id) {
        return this.request(`/medicines/delete.php?id=${id}`, 'DELETE');
    },

    // CART ENDPOINTS
    addToCart(medId, quantity = 1) {
        return this.request('/cart/add.php', 'POST', { med_id: medId, quantity });
    },

    getCart() {
        return this.request('/cart/get.php');
    },

    removeFromCart(itemId) {
        return this.request(`/cart/remove.php?item_id=${itemId}`, 'DELETE');
    },

    clearCart(orderId) {
        return this.request(`/cart/clear.php?order_id=${orderId}`, 'DELETE');
    },

    // INTERACTIONS ENDPOINTS
    checkConflicts(medicineIds, userId = null) {
        const data = { medicine_ids: medicineIds };
        if (userId) data.user_id = userId;
        return this.request('/interactions/check.php', 'POST', data);
    },

    addConflict(drug1Id, drug2Id, description, classification) {
        return this.request('/interactions/add.php', 'POST', {
            drug1_id: drug1Id,
            drug2_id: drug2Id,
            description,
            classification
        });
    },

    getConflicts() {
        return this.request('/interactions/list.php');
    },

    // ORDERS ENDPOINTS
    checkout(orderId) {
        const data = { order_id: orderId };
        return this.request('/orders/checkout.php', 'POST', data);
    },

    getOrder(orderId) {
        return this.request(`/orders/get.php?id=${orderId}`);
    },

    getOrderHistory(page = 1, limit = 20) {
        return this.request(`/orders/history.php?page=${page}&limit=${limit}`);
    },

    // PRESCRIPTIONS ENDPOINTS
    reviewPrescription(reviewId, decision, notes) {
        return this.request('/prescriptions/review.php', 'POST', {
            review_id: reviewId,
            decision,
            notes
        });
    },

    // ADMIN ENDPOINTS
    getDashboard() {
        return this.request('/admin/dashboard.php');
    },

    getPendingReviews(page = 1, limit = 20) {
        return this.request(`/admin/pending-reviews.php?page=${page}&limit=${limit}`);
    },

    getActivityLogs(page = 1, limit = 50, logType = null, severity = null) {
        let url = `/admin/activity-logs.php?page=${page}&limit=${limit}`;
        if (logType) url += `&log_type=${logType}`;
        if (severity) url += `&severity=${severity}`;
        return this.request(url);
    },

    getInventoryAlerts() {
        return this.request('/admin/inventory.php');
    }
};
