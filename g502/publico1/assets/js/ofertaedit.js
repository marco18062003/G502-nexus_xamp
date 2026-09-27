// ofertaedit.js - Lógica del panel de gestión de ofertas

document.addEventListener('DOMContentLoaded', () => {

    // --- Elementos del DOM ---
    const searchInput     = document.getElementById('oe-product-search');
    const searchResults   = document.getElementById('oe-search-results');
    const selectedBox     = document.getElementById('oe-selected-product');
    const selectedImg     = document.getElementById('oe-selected-img');
    const selectedName    = document.getElementById('oe-selected-name');
    const selectedMeta    = document.getElementById('oe-selected-meta');
    const clearProductBtn = document.getElementById('oe-clear-product');
    const productoIdInput = document.getElementById('oe-producto-id');

    const form             = document.getElementById('oe-form');
    const ofertaIdInput    = document.getElementById('oe-oferta-id');
    const descriptionInput = document.getElementById('oe-description');
    const discountTypeRadios = document.querySelectorAll('input[name="oe-discount-type"]');
    const discountValueInput = document.getElementById('oe-discount-value');
    const discountLabel     = document.getElementById('oe-discount-label');
    const pricePreview      = document.getElementById('oe-price-preview');
    const activeCheckbox    = document.getElementById('oe-active');
    const submitBtn         = document.getElementById('oe-submit-btn');
    const cancelEditBtn     = document.getElementById('oe-cancel-edit');
    const formTitle         = document.getElementById('oe-form-title');

    let selectedProductPrice = null; // precio original (value_final) del producto elegido
    let searchDebounceTimer = null;
    let lastSearchResults = []; // últimos resultados mostrados, para poder seleccionar con Enter

    // --- Toast reutilizable ---
    function showToast(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) { alert(message); return; }
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

    // --- Evita que la tecla Enter en el buscador envíe el formulario ---
    // (el input está dentro del <form>, así que Enter lo enviaría antes de
    // que el usuario alcance a hacer clic en un resultado del dropdown).
    // Si hay resultados visibles, Enter selecciona el primero automáticamente
    // (útil cuando se escanea o pega un EAN exacto).
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (lastSearchResults.length > 0) {
                selectProduct(lastSearchResults[0]);
            }
        }
    });

    // --- Buscador de productos (AJAX con debounce) ---
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        clearTimeout(searchDebounceTimer);

        if (query.length < 2) {
            searchResults.classList.remove('show');
            searchResults.innerHTML = '';
            lastSearchResults = [];
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            fetch(`assets/js/buscar_producto_admin.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(productos => {
                    renderSearchResults(productos);
                })
                .catch(error => {
                    console.error('Error buscando productos:', error);
                });
        }, 300);
    });

    function renderSearchResults(productos) {
        searchResults.innerHTML = '';
        lastSearchResults = Array.isArray(productos) ? productos : [];

        if (lastSearchResults.length === 0) {
            searchResults.innerHTML = '<div class="oe-search-empty">Sin resultados</div>';
            searchResults.classList.add('show');
            return;
        }

        lastSearchResults.forEach(p => {
            const item = document.createElement('div');
            item.className = 'oe-search-result-item';

            const imgSrc = p.imagen ? `../Donjorgitofinal/${p.imagen}` : '';
            item.innerHTML = `
                ${imgSrc ? `<img src="${imgSrc}" alt="">` : ''}
                <div>
                    <div class="oe-search-result-name">${escapeHtml(p.producto)}</div>
                    <div class="oe-search-result-meta">${escapeHtml(p.marca || '')} · $${Number(p.value_final).toLocaleString('es-CO')}</div>
                </div>
            `;
            item.addEventListener('click', () => selectProduct(p));
            searchResults.appendChild(item);
        });

        searchResults.classList.add('show');
    }

    function selectProduct(p) {
        productoIdInput.value = p.id;
        selectedProductPrice = Number(p.value_final);

        selectedImg.src = p.imagen ? `../Donjorgitofinal/${p.imagen}` : '';
        selectedImg.style.display = p.imagen ? '' : 'none';
        selectedName.textContent = p.producto;
        selectedMeta.textContent = `${p.marca || ''} · $${selectedProductPrice.toLocaleString('es-CO')}`;

        selectedBox.style.display = 'flex';
        searchInput.value = '';
        searchResults.classList.remove('show');
        searchResults.innerHTML = '';
        lastSearchResults = [];

        updatePricePreview();
    }

    clearProductBtn.addEventListener('click', () => {
        productoIdInput.value = '';
        selectedProductPrice = null;
        selectedBox.style.display = 'none';
        pricePreview.textContent = '';
    });

    // Cierra el dropdown de resultados si se hace clic fuera
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('show');
        }
    });

    // --- Tipo de descuento: cambia la etiqueta y recalcula la vista previa ---
    discountTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            const type = getDiscountType();
            discountLabel.textContent = type === 'percent' ? 'Porcentaje de descuento' : 'Precio fijo de oferta';
            discountValueInput.placeholder = type === 'percent' ? 'Ej: 50' : 'Ej: 25000';
            updatePricePreview();
        });
    });

    discountValueInput.addEventListener('input', updatePricePreview);

    function getDiscountType() {
        return document.querySelector('input[name="oe-discount-type"]:checked').value;
    }

    function updatePricePreview() {
        if (selectedProductPrice === null) {
            pricePreview.textContent = '';
            return;
        }

        const type = getDiscountType();
        const value = parseFloat(discountValueInput.value);

        if (isNaN(value) || value <= 0) {
            pricePreview.textContent = '';
            return;
        }

        let finalPrice;
        if (type === 'percent') {
            if (value > 100) {
                pricePreview.textContent = 'El porcentaje no puede ser mayor a 100.';
                return;
            }
            finalPrice = Math.round(selectedProductPrice * (1 - value / 100));
        } else {
            finalPrice = value;
        }

        pricePreview.textContent = `Precio final: $${finalPrice.toLocaleString('es-CO')} (antes $${selectedProductPrice.toLocaleString('es-CO')})`;
    }

    // --- Enviar formulario (crear o editar) ---
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        if (!productoIdInput.value) {
            showToast('Selecciona un producto primero.', 'error');
            return;
        }
        if (!descriptionInput.value.trim()) {
            showToast('Escribe una descripción para la oferta.', 'error');
            return;
        }
        if (!discountValueInput.value || parseFloat(discountValueInput.value) <= 0) {
            showToast('Ingresa un valor de descuento válido.', 'error');
            return;
        }

        const payload = {
            oferta_id: ofertaIdInput.value || null,
            producto_id: productoIdInput.value,
            description: descriptionInput.value.trim(),
            discount_type: getDiscountType(),
            discount_value: discountValueInput.value,
            active: activeCheckbox.checked
        };

        submitBtn.disabled = true;

        fetch('assets/js/guardar_oferta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 900);
            } else {
                showToast('Error: ' + data.message, 'error');
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al guardar la oferta:', error);
            showToast('Problema de conexión al guardar la oferta.', 'error');
            submitBtn.disabled = false;
        });
    });

    // --- Editar oferta existente (rellena el formulario desde la fila de la tabla) ---
    document.querySelectorAll('.oe-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const d = row.dataset;

            ofertaIdInput.value = d.ofertaId;
            formTitle.textContent = 'Editar oferta';
            cancelEditBtn.style.display = '';

            selectProduct({
                id: d.productoId,
                producto: d.productoNombre,
                marca: d.productoMarca,
                imagen: d.productoImagen,
                value_final: d.productoPrecio
            });

            descriptionInput.value = d.description;
            activeCheckbox.checked = d.active === '1';

            if (d.discountedPrice) {
                document.querySelector('input[name="oe-discount-type"][value="fixed"]').checked = true;
                discountValueInput.value = d.discountedPrice;
                discountLabel.textContent = 'Precio fijo de oferta';
            } else {
                document.querySelector('input[name="oe-discount-type"][value="percent"]').checked = true;
                discountValueInput.value = d.discountPercent;
                discountLabel.textContent = 'Porcentaje de descuento';
            }

            updatePricePreview();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    cancelEditBtn.addEventListener('click', () => {
        form.reset();
        ofertaIdInput.value = '';
        productoIdInput.value = '';
        selectedProductPrice = null;
        selectedBox.style.display = 'none';
        pricePreview.textContent = '';
        formTitle.textContent = 'Nueva oferta';
        cancelEditBtn.style.display = 'none';
        discountLabel.textContent = 'Porcentaje de descuento';
    });

    // --- Eliminar oferta ---
    document.querySelectorAll('.oe-delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const ofertaId = row.dataset.ofertaId;
            const nombre = row.dataset.productoNombre;

            if (!confirm(`¿Eliminar la oferta de "${nombre}"? Esta acción no se puede deshacer.`)) {
                return;
            }

            fetch('assets/js/eliminar_oferta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ oferta_id: ofertaId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    row.remove();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error al eliminar la oferta:', error);
                showToast('Problema de conexión al eliminar la oferta.', 'error');
            });
        });
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});