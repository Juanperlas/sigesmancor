/**
 * Sistema de notificaciones Toast
 * Proporciona notificaciones elegantes y modernas con animaciones
 */

class ToastNotification {
    constructor() {
      this.container = document.getElementById('toast-container');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
      }
    }
  
    /**
     * Muestra una notificación toast
     * @param {Object} options - Opciones de configuración
     * @param {string} options.type - Tipo de notificación: 'success', 'error', 'warning', 'info'
     * @param {string} options.title - Título de la notificación
     * @param {string} options.message - Mensaje de la notificación
     * @param {number} options.duration - Duración en milisegundos (por defecto: 5000)
     */
    show(options) {
      const { type = 'info', title = '', message = '', duration = 5000 } = options;
      
      // Crear el elemento toast
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      
      // Determinar el icono según el tipo
      let icon = '';
      switch (type) {
        case 'success':
          icon = '<i class="fas fa-check-circle"></i>';
          break;
        case 'error':
          icon = '<i class="fas fa-times-circle"></i>';
          break;
        case 'warning':
          icon = '<i class="fas fa-exclamation-triangle"></i>';
          break;
        case 'info':
        default:
          icon = '<i class="fas fa-info-circle"></i>';
          break;
      }
      
      // Construir el contenido del toast
      toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
          ${title ? `<div class="toast-title">${title}</div>` : ''}
          <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
        <div class="toast-progress"></div>
      `;
      
      // Agregar al contenedor
      this.container.appendChild(toast);
      
      // Animar entrada
      setTimeout(() => {
        toast.classList.add('show');
      }, 10);
      
      // Configurar la barra de progreso
      const progressBar = toast.querySelector('.toast-progress');
      progressBar.style.animation = `progress ${duration}ms linear forwards`;
      
      // Configurar el botón de cierre
      const closeButton = toast.querySelector('.toast-close');
      closeButton.addEventListener('click', () => {
        this.close(toast);
      });
      
      // Auto-cerrar después de la duración
      const timeoutId = setTimeout(() => {
        this.close(toast);
      }, duration);
      
      // Pausar el temporizador al pasar el mouse
      toast.addEventListener('mouseenter', () => {
        progressBar.style.animationPlayState = 'paused';
        clearTimeout(timeoutId);
      });
      
      // Reanudar el temporizador al quitar el mouse
      toast.addEventListener('mouseleave', () => {
        progressBar.style.animationPlayState = 'running';
        setTimeout(() => {
          this.close(toast);
        }, duration / 2);
      });
      
      return toast;
    }
    
    /**
     * Cierra una notificación toast
     * @param {HTMLElement} toast - Elemento toast a cerrar
     */
    close(toast) {
      if (!toast.classList.contains('hide')) {
        toast.classList.add('hide');
        toast.classList.remove('show');
        
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 300);
      }
    }
    
    /**
     * Muestra una notificación de éxito
     * @param {string} message - Mensaje de la notificación
     * @param {string} title - Título de la notificación
     * @param {number} duration - Duración en milisegundos
     */
    success(message, title = 'Éxito', duration = 5000) {
      return this.show({ type: 'success', title, message, duration });
    }
    
    /**
     * Muestra una notificación de error
     * @param {string} message - Mensaje de la notificación
     * @param {string} title - Título de la notificación
     * @param {number} duration - Duración en milisegundos
     */
    error(message, title = 'Error', duration = 5000) {
      return this.show({ type: 'error', title, message, duration });
    }
    
    /**
     * Muestra una notificación de advertencia
     * @param {string} message - Mensaje de la notificación
     * @param {string} title - Título de la notificación
     * @param {number} duration - Duración en milisegundos
     */
    warning(message, title = 'Advertencia', duration = 5000) {
      return this.show({ type: 'warning', title, message, duration });
    }
    
    /**
     * Muestra una notificación informativa
     * @param {string} message - Mensaje de la notificación
     * @param {string} title - Título de la notificación
     * @param {number} duration - Duración en milisegundos
     */
    info(message, title = 'Información', duration = 5000) {
      return this.show({ type: 'info', title, message, duration });
    }
  }
  
  // Crear instancia global
  const toast = new ToastNotification();
  
  // Exponer funciones globales para facilitar el uso
  function showToast(options) {
    return toast.show(options);
  }
  
  function showSuccessToast(message, title, duration) {
    return toast.success(message, title, duration);
  }
  
  function showErrorToast(message, title, duration) {
    return toast.error(message, title, duration);
  }
  
  function showWarningToast(message, title, duration) {
    return toast.warning(message, title, duration);
  }
  
  function showInfoToast(message, title, duration) {
    return toast.info(message, title, duration);
  }