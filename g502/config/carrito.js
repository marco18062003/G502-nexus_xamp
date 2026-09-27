// carrito.js - Lógica JavaScript para añadir productos al carrito
//
// NOTA: La lógica de los botones +/- de cantidad (.increase-btn / .decrease-btn)
// ya vive en el <script> inline de buscar.php, así que no se duplica aquí.
// Este archivo se encarga únicamente del botón "Agregar al carrito".

document.addEventListener('DOMContentLoaded', () => {

    // --- Función reutilizable para mostrar notificaciones tipo "toast" ---
    function showToast(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) {
            console.warn('No se encontró #toast-container, usando alert como respaldo.');
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

    // --- Manejo del botón "Agregar al carrito" ---
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const btn = event.currentTarget;
            const productId = btn.dataset.id;
            const quantityInput = document.getElementById(`quantity-${productId}`);

            if (!quantityInput) {
                console.warn(`No se encontró el input de cantidad para el producto ${productId}`);
                return;
            }

            const quantity = parseInt(quantityInput.value, 10);

            // Evita doble clic mientras la solicitud está en curso
            btn.disabled = true;

            console.log(`Producto ID: ${productId}, Cantidad: ${quantity} - Preparando envío a add_to_cart.php...`);

            fetch('../config/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                }),
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Error HTTP: ${response.status}. Respuesta del servidor: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    showToast('Producto añadido al carrito con éxito!', 'success');

                    // Actualiza el contador del carrito en el header
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
                console.error('Error en la solicitud Fetch o al procesar la respuesta:', error);
                showToast('Problema de conexión. Revisa la consola (F12) para más detalles.', 'error', 4000);
            })
            .finally(() => {
                btn.disabled = false;
            });
        });
    });
});