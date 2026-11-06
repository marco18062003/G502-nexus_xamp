// ofertas.js - Lógica de cantidad y "agregar al carrito" para la página de ofertas

document.addEventListener('DOMContentLoaded', () => {

    // --- Botones de cantidad ---
    document.querySelectorAll('.increase-btn').forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.dataset.id;
            const input = document.getElementById(`quantity-oferta-${productId}`);
            if (!input) return;
            input.value = parseInt(input.value, 10) + 1;
        });
    });

    document.querySelectorAll('.decrease-btn').forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.dataset.id;
            const input = document.getElementById(`quantity-oferta-${productId}`);
            if (!input) return;
            const current = parseInt(input.value, 10);
            if (current > 1) input.value = current - 1;
        });
    });

    // --- Toast reutilizable (usa el mismo #toast-container de header.php) ---
    function showToast(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) {
            alert(message);
            return;
        }
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    }

    // --- Agregar al carrito ---
    document.querySelectorAll('.add-oferta-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const btn = event.currentTarget;
            const productId = btn.dataset.id;
            const input = document.getElementById(`quantity-oferta-${productId}`);
            if (!input) return;

            const quantity = parseInt(input.value, 10);
            btn.disabled = true;

            fetch('add_to_cart_oferta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    oferta_id: productId,
                    quantity: quantity
                }),
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Error HTTP: ${response.status}. Respuesta: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('Oferta añadida al carrito!', 'success');
                    if (data.cart_total_items !== undefined) {
                        const counter = document.getElementById('cart-counter');
                        if (counter) {
                            counter.textContent = data.cart_total_items;
                            counter.style.display = data.cart_total_items > 0 ? '' : 'none';
                        }
                    }
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch((error) => {
                console.error('Error en la solicitud de oferta:', error);
                showToast('Problema de conexión. Revisa la consola (F12).', 'error', 4000);
            })
            .finally(() => {
                btn.disabled = false;
            });
        });
    });
});