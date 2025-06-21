/**
 * Utilidades para peticiones AJAX
 * Proporciona funciones para realizar peticiones HTTP de forma sencilla
 */

class AjaxUtils {
  /**
   * Realiza una petición AJAX
   * @param {Object} options - Opciones de configuración
   * @param {string} options.url - URL del endpoint
   * @param {string} options.method - Método HTTP (GET, POST, PUT, DELETE)
   * @param {Object|FormData} options.data - Datos a enviar
   * @param {Function} options.success - Función de callback para respuesta exitosa
   * @param {Function} options.error - Función de callback para errores
   * @param {Function} options.complete - Función de callback al completar (exitoso o error)
   * @param {boolean} options.showLoader - Si se debe mostrar el loader
   * @param {boolean} options.showErrors - Si se deben mostrar errores automáticamente
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static request(options) {
    const {
      url,
      method = "GET",
      data = null,
      success = null,
      error = null,
      complete = null,
      showLoader = true,
      showErrors = true,
    } = options;

    // Validar URL
    if (!url) {
      console.error("URL es requerida para la petición AJAX");
      return;
    }

    // Crear objeto XMLHttpRequest
    const xhr = new XMLHttpRequest();

    // Mostrar loader si está habilitado
    if (showLoader) {
      AjaxUtils.showLoader();
    }

    // Configurar evento de carga
    xhr.onload = function () {
      // Ocultar loader
      if (showLoader) {
        AjaxUtils.hideLoader();
      }

      let response;

      // Intentar parsear respuesta como JSON
      try {
        response = JSON.parse(xhr.responseText);
      } catch (e) {
        response = xhr.responseText;
      }

      // Verificar estado de la respuesta
      if (xhr.status >= 200 && xhr.status < 300) {
        // Respuesta exitosa
        if (success) {
          success(response, xhr);
        }
      } else {
        // Error en la respuesta
        if (showErrors) {
          AjaxUtils.handleError(response, xhr);
        }

        if (error) {
          error(response, xhr);
        }
      }

      // Callback complete
      if (complete) {
        complete(response, xhr);
      }
    };

    // Configurar evento de error
    xhr.onerror = function () {
      // Ocultar loader
      if (showLoader) {
        AjaxUtils.hideLoader();
      }

      // Mostrar error de conexión
      if (showErrors) {
        showErrorToast("Error de conexión. Verifica tu conexión a internet.");
      }

      // Callback de error
      if (error) {
        error({ error: "connection_error" }, xhr);
      }

      // Callback complete
      if (complete) {
        complete({ error: "connection_error" }, xhr);
      }
    };

    // Abrir conexión
    xhr.open(method, url, true);

    // Configurar headers
    if (!(data instanceof FormData)) {
      xhr.setRequestHeader("Content-Type", "application/json");
    }

    // Enviar petición
    if (data) {
      if (data instanceof FormData) {
        xhr.send(data);
      } else {
        xhr.send(JSON.stringify(data));
      }
    } else {
      xhr.send();
    }

    return xhr;
  }

  /**
   * Realiza una petición GET
   * @param {string} url - URL del endpoint
   * @param {Object} params - Parámetros de la petición
   * @param {Function} success - Función de callback para respuesta exitosa
   * @param {Object} options - Opciones adicionales
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static get(url, params = {}, success = null, options = {}) {
    // Construir URL con parámetros
    const queryString = Object.keys(params)
      .map(
        (key) => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`
      )
      .join("&");

    const fullUrl = queryString ? `${url}?${queryString}` : url;

    return AjaxUtils.request({
      url: fullUrl,
      method: "GET",
      success,
      ...options,
    });
  }

  /**
   * Realiza una petición POST
   * @param {string} url - URL del endpoint
   * @param {Object|FormData} data - Datos a enviar
   * @param {Function} success - Función de callback para respuesta exitosa
   * @param {Object} options - Opciones adicionales
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static post(url, data = {}, success = null, options = {}) {
    return AjaxUtils.request({
      url,
      method: "POST",
      data,
      success,
      ...options,
    });
  }

  /**
   * Realiza una petición PUT
   * @param {string} url - URL del endpoint
   * @param {Object|FormData} data - Datos a enviar
   * @param {Function} success - Función de callback para respuesta exitosa
   * @param {Object} options - Opciones adicionales
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static put(url, data = {}, success = null, options = {}) {
    return AjaxUtils.request({
      url,
      method: "PUT",
      data,
      success,
      ...options,
    });
  }

  /**
   * Realiza una petición DELETE
   * @param {string} url - URL del endpoint
   * @param {Object} params - Parámetros de la petición
   * @param {Function} success - Función de callback para respuesta exitosa
   * @param {Object} options - Opciones adicionales
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static delete(url, params = {}, success = null, options = {}) {
    // Construir URL con parámetros
    const queryString = Object.keys(params)
      .map(
        (key) => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`
      )
      .join("&");

    const fullUrl = queryString ? `${url}?${queryString}` : url;

    return AjaxUtils.request({
      url: fullUrl,
      method: "DELETE",
      success,
      ...options,
    });
  }

  /**
   * Envía un formulario mediante AJAX
   * @param {HTMLFormElement} form - Elemento del formulario
   * @param {Function} success - Función de callback para respuesta exitosa
   * @param {Object} options - Opciones adicionales
   * @returns {XMLHttpRequest} - Objeto XMLHttpRequest
   */
  static submitForm(form, success = null, options = {}) {
    if (!form || !(form instanceof HTMLFormElement)) {
      console.error("Se requiere un elemento de formulario válido");
      return;
    }

    // Obtener método y URL del formulario
    const method = (form.getAttribute("method") || "POST").toUpperCase();
    const url = form.getAttribute("action") || window.location.href;

    // Crear FormData con los datos del formulario
    const formData = new FormData(form);

    return AjaxUtils.request({
      url,
      method,
      data: formData,
      success,
      ...options,
    });
  }

  /**
   * Maneja errores de respuesta
   * @param {Object|string} response - Respuesta del servidor
   * @param {XMLHttpRequest} xhr - Objeto XMLHttpRequest
   */
  static handleError(response, xhr) {
    let errorMessage = "Ha ocurrido un error en la solicitud.";

    if (typeof response === "object" && response !== null) {
      if (response.error) {
        errorMessage = response.error;
      } else if (response.message) {
        errorMessage = response.message;
      }
    }

    // Mostrar mensaje de error
    showErrorToast(errorMessage);
  }

  /**
   * Muestra el loader global
   */
  static showLoader() {
    // Verificar si existe el loader
    let loader = document.getElementById("ajax-loader");

    // Crear loader si no existe
    if (!loader) {
      loader = document.createElement("div");
      loader.id = "ajax-loader";
      loader.className = "ajax-loader";
      loader.innerHTML = `
          <div class="ajax-loader-spinner">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
        `;
      document.body.appendChild(loader);

      // Agregar estilos si no existen
      if (!document.getElementById("ajax-loader-styles")) {
        const style = document.createElement("style");
        style.id = "ajax-loader-styles";
        style.textContent = `
            .ajax-loader {
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background-color: rgba(0, 0, 0, 0.3);
              display: flex;
              justify-content: center;
              align-items: center;
              z-index: 9999;
              opacity: 0;
              transition: opacity 0.2s ease;
            }
            .ajax-loader.show {
              opacity: 1;
            }
            .ajax-loader-spinner {
              background-color: white;
              padding: 20px;
              border-radius: 10px;
              box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
          `;
        document.head.appendChild(style);
      }
    }

    // Mostrar loader
    setTimeout(() => {
      loader.classList.add("show");
    }, 10);
  }

  /**
   * Oculta el loader global
   */
  static hideLoader() {
    const loader = document.getElementById("ajax-loader");
    if (loader) {
      loader.classList.remove("show");

      // Eliminar después de la animación
      setTimeout(() => {
        if (loader.parentNode && !loader.classList.contains("show")) {
          loader.parentNode.removeChild(loader);
        }
      }, 200);
    }
  }
}

// Alias para facilitar el uso
const ajax = AjaxUtils;
