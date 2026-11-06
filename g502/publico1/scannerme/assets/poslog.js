let carritoPos = [];
const inputBusqueda = document.getElementById('pos-search');
const resultadosDiv = document.getElementById('resultados-pos');
const listaItems = document.getElementById('pos-items-list');
const totalDisplay = document.getElementById('pos-total');

// Inicializar el Modal
let modalCobro;
document.addEventListener('DOMContentLoaded', () => {
    modalCobro = new bootstrap.Modal(document.getElementById('modalCobro'));
});

// Búsqueda dinámica
inputBusqueda.addEventListener('input', (e) => {
    const q = e.target.value.trim();
    if (q.length < 2) { 
        resultadosDiv.innerHTML = ''; 
        return; 
    }
    
    fetch(`./buscarpos.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            resultadosDiv.innerHTML = '';
            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'resultado-item';
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1; padding-right: 10px;">
                            <strong>${p.producto}</strong>
                            <small class="d-block text-muted">${p.caracteristica || 'Sin descripción'}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success" style="font-size: 0.9rem;">$${parseFloat(p.precio).toLocaleString()}</span>
                            <div style="font-size: 0.7rem; color: #999; margin-top: 5px;">PLU: ${p.plu || 'N/A'}</div>
                        </div>
                    </div>`;
                div.onclick = () => agregarAlPos(p);
                resultadosDiv.appendChild(div);
            });
        });
});

inputBusqueda.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        const valor = inputBusqueda.value.trim();
        if (!valor) return;
        let cant = 1, busqueda = valor;
        if (valor.includes('*')) {
            const partes = valor.split('*');
            cant = parseInt(partes[0]) || 1;
            busqueda = partes[1];
        }
        fetch(`./buscarpos.php?q=${encodeURIComponent(busqueda)}`)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) { agregarAlPos(data[0], cant); } 
                else { alert("Producto no encontrado"); }
            });
    }
});

function agregarAlPos(producto, multiplicador = 1) {
    const existe = carritoPos.find(item => item.id === producto.id);
    if (existe) { existe.cantidad += multiplicador; } 
    else {
        carritoPos.push({
            id: producto.id,
            nombre: producto.producto,
            caracteristica: producto.caracteristica,
            precio: parseFloat(producto.precio),
            cantidad: multiplicador
        });
    }
    inputBusqueda.value = '';
    resultadosDiv.innerHTML = '';
    actualizarVista();
}

function actualizarVista() {
    listaItems.innerHTML = '';
    let total = 0;
    if (carritoPos.length === 0) {
        listaItems.innerHTML = `<div class="text-center text-muted mt-5"><i class="fas fa-shopping-cart fa-3x"></i><p class="mt-2">Carrito vacío</p></div>`;
        totalDisplay.innerText = '$ 0';
        return;
    }
    carritoPos.forEach((item, index) => {
        let subtotal = item.precio * item.cantidad;
        total += subtotal;
        listaItems.innerHTML += `
            <div class="product-card">
                <div class="d-flex justify-content-between">
                    <div><span class="p-name">${item.nombre}</span><small class="text-muted d-block">${item.caracteristica || ''}</small></div>
                    <button class="btn-delete" onclick="eliminar(${index})"><i class="fas fa-times"></i></button>
                </div>
                <div class="card-controls">
                    <div class="qty-group">
                        <button onclick="cambiarCant(${index}, -1)">-</button>
                        <span>${item.cantidad}</span>
                        <button onclick="cambiarCant(${index}, 1)">+</button>
                    </div>
                    <div class="p-subtotal">$${subtotal.toLocaleString()}</div>
                </div>
            </div>`;
    });
    totalDisplay.innerText = '$ ' + total.toLocaleString();
}

function cambiarCant(index, valor) {
    carritoPos[index].cantidad += valor;
    if (carritoPos[index].cantidad <= 0) eliminar(index);
    actualizarVista();
}

function eliminar(index) {
    carritoPos.splice(index, 1);
    actualizarVista();
}

// --- NUEVAS FUNCIONES DE COBRO ---

function finalizarVenta() {
    if (carritoPos.length === 0) return alert("El carrito está vacío");
    document.getElementById('modal-total-display').innerText = totalDisplay.innerText;
    document.getElementById('seccion-efectivo').style.display = 'none';
    document.getElementById('mensaje-virtual').style.display = 'none';
    document.getElementById('monto-pagado').value = '';
    document.getElementById('cambio-display').innerText = '$ 0';
    modalCobro.show();
}

function seleccionarMetodo(tipo) {
    const sec = document.getElementById('seccion-efectivo');
    const msg = document.getElementById('mensaje-virtual');
    if (tipo === 'efectivo') {
        sec.style.display = 'block';
        msg.style.display = 'none';
        setTimeout(() => document.getElementById('monto-pagado').focus(), 500);
    } else {
        sec.style.display = 'none';
        msg.style.display = 'block';
    }
}

function calcularCambio() {
    // 1. Limpiamos el total de puntos, comas y signos para operar matemáticamente
    const totalTexto = totalDisplay.innerText.replace(/[^\d]/g, '');
    const total = parseInt(totalTexto) || 0;
    
    // 2. Obtenemos lo que ingresó el empleado
    const pagado = parseInt(document.getElementById('monto-pagado').value) || 0;
    
    const cambio = pagado - total;
    const display = document.getElementById('cambio-display');
    const btnConfirmar = document.querySelector('.btn-confirmar-final');
    
    if (pagado === 0) {
        display.innerText = '$ 0';
        display.className = 'h3 m-0 fw-bold text-muted';
        btnConfirmar.disabled = true; // Bloqueado
    } else if (cambio < 0) {
        // Dinero insuficiente
        display.innerText = 'Faltan: $ ' + Math.abs(cambio).toLocaleString();
        display.className = 'h3 m-0 fw-bold text-danger';
        btnConfirmar.disabled = true; // Bloqueado
    } else {
        // Dinero suficiente
        display.innerText = '$ ' + cambio.toLocaleString();
        display.className = 'h3 m-0 fw-bold text-success';
        btnConfirmar.disabled = false; // Habilitado para cobrar
    }
}

function procesarVentaFinal() {
    const totalTexto = totalDisplay.innerText.replace(/[^\d]/g, '');
    const total = parseInt(totalTexto) || 0;
    const pagado = parseInt(document.getElementById('monto-pagado').value) || 0;
    const cambio = pagado - total;

    // 1. Verificación de seguridad
    if (pagado < total) {
        alert("¡Error! El dinero recibido es menor al total de la venta.");
        return;
    }

    // 2. Preparar los datos para enviar a las tablas factura_base y factura
    const datosVenta = {
        total: total,
        metodo_pago: 'Efectivo', // Puedes dinamizar esto con una variable
        cambio: cambio.toString(),
        productos: carritoPos // Enviamos todo el array de productos
    };

    // 3. Enviar datos al servidor mediante Fetch
    fetch('facturas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datosVenta)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert("Venta #" + data.numero_factura + " guardada con éxito.");
            
            // Limpiar todo después del éxito
            carritoPos = [];
            actualizarVista();
            modalCobro.hide();
            inputBusqueda.focus();
        } else {
            alert("Error del servidor: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Hubo un error al conectar con el servidor.");
    });
}
// Añade esto a tu pos.js
function generarPagoWompi() {
    // 1. Obtenemos el total limpio (sin símbolos)
    const totalTexto = document.getElementById('pos-total').innerText.replace(/[^\d]/g, '');
    const total = parseInt(totalTexto) || 0;

    if (total === 0) {
        alert("El carrito está vacío");
        return;
    }

    // 2. Pedimos el nombre del cliente (opcional)
    const nombreCliente = prompt("Nombre del cliente para el recibo:", "Cliente G502");
    
    if (nombreCliente) {
        // 3. Redirigimos a tu archivo paymeth2.php con los parámetros automáticos
        const url = `paymeth2.php?customer_name=${encodeURIComponent(nombreCliente)}&precio=${total}`;
        
        // Abrimos en una pestaña nueva para no cerrar el POS
        window.open(url, '_blank');
    }
}