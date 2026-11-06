// Asegúrate de que este script se ejecute DESPUÉS de que Swiper.js y Jarallax.js estén cargados.
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializa Swiper para el carrusel principal
    const mainSlideshow = new Swiper('.slideshow', {
        speed: 1000, // Velocidad de la transición entre slides (en ms)
        loop: true,  // Hace que el carrusel sea infinito
        effect: 'slide', // Efecto de transición: 'slide' (desplazamiento) o 'fade' (desvanecimiento)
        
        // Configuración de auto-reproducción
        autoplay: {
            delay: 5000, // Tiempo en ms que un slide permanece visible (5 segundos)
            disableOnInteraction: false, // El autoplay no se detiene si el usuario interactúa (ej. hace clic en flechas)
        },

        // Paginación (los puntos en la parte inferior del carrusel)
        pagination: {
            el: '.slideshow-swiper-pagination', // Selector del elemento donde se renderizarán los puntos
            clickable: true, // Permite hacer clic en los puntos para ir a un slide específico
        },

        // Navegación (las flechas izquierda/derecha)
        navigation: {
            nextEl: '.icon-arrow-right', // Selector de la flecha siguiente
            prevEl: '.icon-arrow-left',  // Selector de la flecha anterior
        },
        
        // Si estás usando los efectos de texto txt-fx, es posible que Swiper necesite reevaluarlos
        on: {
            slideChangeTransitionEnd: function () {
                // Puedes agregar aquí lógica para reiniciar animaciones de texto si no se ven correctamente
                // Por ejemplo, añadiendo y quitando una clase que dispara la animación.
            }
        }
    });

    // Inicializa Jarallax para el efecto parallax en las imágenes de fondo
    // Esto es crucial para que el 'jarallax-img' funcione
    if (typeof jarallax !== 'undefined') {
        jarallax(document.querySelectorAll('.jarallax'), {
            speed: 0.2 // Velocidad del efecto parallax
        });
    } else {
        console.warn('Jarallax no está cargado. El efecto parallax no funcionará.');
    }
});