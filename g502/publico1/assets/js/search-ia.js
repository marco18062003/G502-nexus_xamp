$(document).ready(function() {
    var debounceTimer; 

    $('#buscador').on('keyup', function() {
        var termino = $(this).val();
        clearTimeout(debounceTimer);

        if (termino.length < 2) {
            $('#resultados-busqueda').hide().empty();
            return;
        }

        debounceTimer = setTimeout(function() {
            $.ajax({
                url: 'buscar_productos.php', 
                type: 'GET',
                data: {
                    q: termino 
                },
                dataType: 'json',
                success: function(data) {
                    var htmlResultados = '';
                    
                    if (data.length > 0) {
                        $.each(data, function(index, producto) {
                            htmlResultados += `
                                <a href="producto.php?id=${producto.id_producto}" class="resultado-item">
                                    <img src="../Donjorgitofinal/${producto.imagen}" 
                                         alt="${producto.nombre}" 
                                         style="width:40px;height:40px;object-fit:cover;margin-right:10px;">
                                    <span>${producto.nombre}</span>
                                </a>
                            `;
                        });
                    } else {
                        htmlResultados = '<p style="padding: 10px; text-align: center;">No hay coincidencias.</p>';
                    }
                    
                    $('#resultados-busqueda').html(htmlResultados).show();
                },
                error: function() {
                    $('#resultados-busqueda').html('<p style="padding: 10px; color: red;">Error al buscar.</p>').show();
                }
            });
        }, 300);
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-wrapper').length) {
            $('#resultados-busqueda').hide().empty();
        }
    });
});