/**
 * Script para la página de módulo en desarrollo
 */
$(document).ready(function() {
    // Inicializar animación de progreso
    iniciarAnimacionProgreso();
    
    // Inicializar eventos
    inicializarEventos();
    
    // Inicializar tooltips
    inicializarTooltips();
    
    // Animación de entrada para las tarjetas
    animarTarjetas();
});

/**
 * Inicializa la animación de la barra de progreso
 */
function iniciarAnimacionProgreso() {
    // Valores aleatorios para simular progreso
    const progreso = Math.floor(Math.random() * 30) + 40; // Entre 40% y 70%
    
    // Actualizar barra de progreso
    setTimeout(function() {
        $('.progress-fill').css('width', progreso + '%');
        $('#progress-percentage').text(progreso);
    }, 500);
    
    // Animación de pulso para la barra de progreso
    setInterval(function() {
        const currentWidth = parseInt($('.progress-fill').css('width'));
        const maxWidth = parseInt($('.progress-bar').css('width'));
        const percentage = Math.round((currentWidth / maxWidth) * 100);
        
        // Pequeña variación aleatoria en el progreso
        const newProgress = Math.min(percentage + (Math.random() > 0.5 ? 1 : -1), progreso + 3);
        
        $('.progress-fill').css('width', newProgress + '%');
        $('#progress-percentage').text(newProgress);
    }, 3000);
}

/**
 * Inicializa los eventos de la página
 */
function inicializarEventos() {
    // Evento para el botón de sugerencia
    $('#btn-sugerencia').on('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('modal-sugerencia'));
        modal.show();
    });
    
    // Evento para enviar sugerencia
    $('#btn-enviar-sugerencia').on('click', function() {
        const tipo = $('#sugerencia-tipo').val();
        const descripcion = $('#sugerencia-descripcion').val();
        
        if (!tipo || !descripcion) {
            // Validación básica
            alert('Por favor complete todos los campos');
            return;
        }
        
        // Simular envío de sugerencia
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');
        $(this).prop('disabled', true);
        
        setTimeout(function() {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modal-sugerencia')).hide();
            
            // Mostrar toast de confirmación
            const toastEl = document.getElementById('sugerencia-toast');
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            
            // Resetear formulario
            $('#form-sugerencia')[0].reset();
            $('#btn-enviar-sugerencia').html('Enviar');
            $('#btn-enviar-sugerencia').prop('disabled', false);
        }, 1500);
    });
    
    // Animación para los engranajes al hacer hover
    $('.gear-container').on('mouseenter', function() {
        $('.gear-large, .gear-medium, .gear-small').css('animation-duration', '3s');
    }).on('mouseleave', function() {
        $('.gear-large').css('animation-duration', '10s');
        $('.gear-medium').css('animation-duration', '7s');
        $('.gear-small').css('animation-duration', '5s');
    });
}

/**
 * Inicializa los tooltips de Bootstrap
 */
function inicializarTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Anima las tarjetas de información con efecto de entrada
 */
function animarTarjetas() {
    // Ocultar tarjetas inicialmente
    $('.info-card').css({
        opacity: 0,
        transform: 'translateY(20px)'
    });
    
    // Mostrar tarjetas con animación
    $('.info-card').each(function(index) {
        const card = $(this);
        setTimeout(function() {
            card.css({
                transition: 'opacity 0.5s ease, transform 0.5s ease',
                opacity: 1,
                transform: 'translateY(0)'
            });
        }, 300 * (index + 1));
    });
    
    // Animar timeline
    $('.timeline-item').css({
        opacity: 0,
        transform: 'translateX(-20px)'
    });
    
    $('.timeline-item').each(function(index) {
        const item = $(this);
        setTimeout(function() {
            item.css({
                transition: 'opacity 0.5s ease, transform 0.5s ease',
                opacity: 1,
                transform: 'translateX(0)'
            });
        }, 200 * (index + 1) + 1000); // Empezar después de las tarjetas
    });
}

/**
 * Efecto de partículas para los engranajes (opcional, activar si se desea)
 */
function iniciarEfectoParticulas() {
    // Esta función se puede implementar si se desea añadir un efecto de partículas
    // Requiere una librería adicional como particles.js
    console.log('Efecto de partículas no implementado');
}