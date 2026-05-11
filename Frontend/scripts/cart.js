// Cart Management with Drug Conflict Detection

const Cart = {
    currentCart: null,
    currentConflicts: [],

    async loadCart() {
        try {
            const response = await API.getCart();
            if (response.success) {
                this.currentCart = response.data;
                await this.checkConflicts();
                return response.data;
            }
        } catch (error) {
            console.error('Failed to load cart:', error);
            throw error;
        }
    },

    async addToCart(medId, quantity = 1) {
        try {
            const response = await API.addToCart(medId, quantity);

            if (response.success) {
                Utils.showNotification('Item added to cart', 'success');
                await this.loadCart();
                return response.data;
            }
        } catch (error) {
            console.error('Failed to add to cart:', error);
            throw error;
        }
    },

    async removeFromCart(itemId) {
        try {
            const response = await API.removeFromCart(itemId);

            if (response.success) {
                Utils.showNotification('Item removed from cart', 'success');
                await this.loadCart();
                return response.data;
            }
        } catch (error) {
            console.error('Failed to remove from cart:', error);
            throw error;
        }
    },

    async clearCart(orderId) {
        try {
            const response = await API.clearCart(orderId);

            if (response.success) {
                Utils.showNotification('Cart cleared', 'success');
                await this.loadCart();
                return response.data;
            }
        } catch (error) {
            console.error('Failed to clear cart:', error);
            throw error;
        }
    },

    // CRITICAL: Check for drug conflicts
    async checkConflicts() {
        if (!this.currentCart || this.currentCart.items.length === 0) {
            this.currentConflicts = [];
            return;
        }

        const medicineIds = this.currentCart.items.map(item => item.med_id);
        const user = Utils.getUser();
        const userId = user ? user.id : null;

        try {
            const response = await API.checkConflicts(medicineIds, userId);

            if (response.success) {
                this.currentConflicts = response.conflicts || [];

                if (this.currentConflicts.length > 0) {
                    this.displayConflictWarning();
                }

                return this.currentConflicts;
            }
        } catch (error) {
            console.error('Failed to check conflicts:', error);
            return [];
        }
    },

    displayConflictWarning() {
        const conflictContainer = document.getElementById('conflict-warnings');
        if (!conflictContainer) return;

        conflictContainer.innerHTML = '';

        this.currentConflicts.forEach(conflict => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning';
            alert.innerHTML = `
                <strong>⚠️ Drug Interaction Warning</strong><br>
                <strong>${conflict.drug1_name}</strong> conflicts with <strong>${conflict.drug2_name}</strong><br>
                ${conflict.description}<br>
                <small>Severity: ${conflict.severity || 'Moderate'}</small>
                ${conflict.note ? `<br><small>${conflict.note}</small>` : ''}
            `;
            conflictContainer.appendChild(alert);
        });

        // Disable checkout button if conflicts exist
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = true;
            checkoutBtn.title = 'Please resolve conflicts before checkout';
        }
    },

    clearConflictWarnings() {
        const conflictContainer = document.getElementById('conflict-warnings');
        if (conflictContainer) {
            conflictContainer.innerHTML = '';
        }

        // Enable checkout button
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = false;
        }
    },

    hasConflicts() {
        return this.currentConflicts.length > 0;
    },

    async checkout(orderId, prescriptionFile = null) {
        if (this.hasConflicts()) {
            Utils.showNotification('Please resolve conflicts before checkout', 'error');
            return false;
        }

        try {
            const formData = new FormData();
            formData.append('order_id', orderId);

            if (prescriptionFile) {
                formData.append('prescription', prescriptionFile);
            }

            // Use fetch directly for FormData
            const token = Utils.getToken();
            const headers = {};
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(API.BASE_URL + '/orders/checkout.php', {
                method: 'POST',
                headers,
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Utils.showNotification('Order placed successfully!', 'success');
                return result.data;
            } else {
                Utils.showNotification(result.error || 'Checkout failed', 'error');
                return false;
            }
        } catch (error) {
            console.error('Checkout error:', error);
            Utils.showNotification('Checkout failed: ' + error.message, 'error');
            return false;
        }
    },

    renderCart(containerId) {
        const container = document.getElementById(containerId);
        if (!container || !this.currentCart) return;

        if (this.currentCart.items.length === 0) {
            container.innerHTML = '<p>Your cart is empty</p>';
            return;
        }

        let html = '<table><thead><tr><th>Medicine</th><th>Brand</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th></tr></thead><tbody>';

        this.currentCart.items.forEach(item => {
            const total = (item.price * item.quantity).toFixed(2);
            html += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.brand}</td>
                    <td>${Utils.formatCurrency(item.price)}</td>
                    <td>${item.quantity}</td>
                    <td>${Utils.formatCurrency(total)}</td>
                    <td>
                        <button class="btn btn-danger" onclick="Cart.removeFromCart(${item.order_item_id})">Remove</button>
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table>
                <div class="mt-2">
                    <strong>Cart Total: ${Utils.formatCurrency(this.currentCart.cart_total)}</strong>
                </div>`;

        container.innerHTML = html;
    }
};
