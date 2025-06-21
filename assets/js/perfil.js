/**
 * Módulo de Perfil de Usuario
 * SIGESMANCOR
 */

// Variables globales
let imageUploader;
let perfilData = {};

// Elementos DOM
const $ = jQuery;
const $modalPerfil = $("#modal-editar-perfil");
const $formPerfil = $("#form-perfil");

// Verificar si las funciones de toast están disponibles
if (!window.showErrorToast) window.showErrorToast = (msg) => console.error(msg);
if (!window.showSuccessToast)
  window.showSuccessToast = (msg) => console.log(msg);
if (!window.showInfoToast) window.showInfoToast = (msg) => console.log(msg);
if (!window.imageViewer)
  window.imageViewer = {
    show: () => console.log("Visor de imágenes no disponible"),
  };

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  inicializarEventos();
  cargarDatosPerfil();
  initImageUploader();
});

// Función para obtener la URL base
function getBaseUrl() {
  return window.location.pathname.split("/perfil.php")[0] + "/";
}

// Función para construir URL completa
function getUrl(path) {
  return getBaseUrl() + path;
}

/**
 * Inicializa todos los eventos del módulo
 */
function inicializarEventos() {
  // Botón Editar Perfil
  $("#btn-editar-perfil").on("click", () => {
    abrirModalEdicion();
  });

  // Botón Guardar Perfil
  $("#btn-guardar-perfil").on("click", () => {
    guardarPerfil();
  });

  // Click en avatar para ver imagen completa
  $("#perfil-avatar-display").on("click", function () {
    if (window.imageViewer) {
      const imgSrc = $(this).attr("src");
      const nombre = $(".perfil-nombre").text();
      window.imageViewer.show(imgSrc, nombre);
    }
  });

  // Validación del formulario
  inicializarValidacionFormulario();
}

/**
 * Inicializa el componente de carga de imágenes
 */
function initImageUploader() {
  if (document.getElementById("container-perfil-fotografia")) {
    try {
      imageUploader = new ImageUpload("container-perfil-fotografia", {
        maxSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "fotografia",
        defaultImage: getUrl("assets/img/administracion/usuarios/default.png"),
        existingImage: "",
        uploadPath: "assets/img/usuarios/",
        position: "center",
      });
    } catch (e) {
      console.warn(
        "Error al inicializar el componente de carga de imágenes:",
        e
      );
    }
  }
}

/**
 * Inicializa la validación del formulario
 */
function inicializarValidacionFormulario() {
  $formPerfil.validate({
    rules: {
      nombre_completo: {
        required: true,
        minlength: 3,
        maxlength: 100,
      },
      correo: {
        email: true,
      },
      dni: {
        maxlength: 20,
      },
      telefono: {
        maxlength: 20,
      },
      area: {
        maxlength: 100,
      },
      direccion: {
        maxlength: 255,
      },
      contrasena_actual: {
        required: () => $("#perfil-contrasena-nueva").val().length > 0,
      },
      contrasena_nueva: {
        minlength: 6,
        required: () => $("#perfil-contrasena-actual").val().length > 0,
      },
      confirmar_contrasena: {
        equalTo: "#perfil-contrasena-nueva",
        required: () => $("#perfil-contrasena-nueva").val().length > 0,
      },
    },
    messages: {
      nombre_completo: {
        required: "El nombre completo es obligatorio",
        minlength: "El nombre completo debe tener al menos 3 caracteres",
        maxlength: "El nombre completo no puede tener más de 100 caracteres",
      },
      correo: {
        email: "Ingrese un correo electrónico válido",
      },
      contrasena_actual: {
        required: "Debe ingresar su contraseña actual para cambiarla",
      },
      contrasena_nueva: {
        required: "Debe ingresar una nueva contraseña",
        minlength: "La nueva contraseña debe tener al menos 6 caracteres",
      },
      confirmar_contrasena: {
        equalTo: "Las contraseñas no coinciden",
        required: "Debe confirmar la nueva contraseña",
      },
    },
    errorElement: "span",
    errorPlacement: (error, element) => {
      error.addClass("invalid-feedback");
      element.closest(".form-group").append(error);
    },
    highlight: (element, errorClass, validClass) => {
      $(element).addClass("is-invalid");
    },
    unhighlight: (element, errorClass, validClass) => {
      $(element).removeClass("is-invalid");
    },
  });
}

/**
 * Carga los datos del perfil del usuario actual
 */
function cargarDatosPerfil() {
  $.ajax({
    url: getUrl("api/perfil/obtener.php"),
    type: "GET",
    dataType: "json",
    success: (response) => {
      if (response.success) {
        perfilData = response.data;
        actualizarVisualizacionPerfil(perfilData);
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los datos del perfil"
        );
      }
    },
    error: (xhr, status, error) => {
      mostrarToast("error", "Error", "Error al cargar los datos del perfil");
      console.error(error);
    },
  });
}

/**
 * Actualiza la visualización del perfil con los datos cargados
 */
function actualizarVisualizacionPerfil(datos) {
  // Actualizar campos de visualización
  $("#display-nombre").text(datos.nombre_completo || "-");
  $("#display-username").text(datos.username || "-");
  $("#display-correo").text(datos.correo || "-");
  $("#display-dni").text(datos.dni || "-");
  $("#display-telefono").text(datos.telefono || "-");
  $("#display-area").text(datos.area || "-");
  $("#display-direccion").text(datos.direccion || "-");
  $("#display-actualizado").text(
    datos.actualizado_en ? formatearFecha(datos.actualizado_en) : "-"
  );

  // Actualizar avatar
  if (datos.fotografia) {
    $("#perfil-avatar-display").attr("src", datos.fotografia);
  }

  // Actualizar nombre en el header
  $(".perfil-nombre").text(datos.nombre_completo || datos.username);
}

/**
 * Abre el modal de edición con los datos actuales
 */
function abrirModalEdicion() {
  // Llenar el formulario con los datos actuales
  $("#perfil-nombre").val(perfilData.nombre_completo || "");
  $("#perfil-username").val(perfilData.username || "");
  $("#perfil-correo").val(perfilData.correo || "");
  $("#perfil-dni").val(perfilData.dni || "");
  $("#perfil-telefono").val(perfilData.telefono || "");
  $("#perfil-area").val(perfilData.area || "");
  $("#perfil-direccion").val(perfilData.direccion || "");

  // Limpiar campos de contraseña
  $("#perfil-contrasena-actual").val("");
  $("#perfil-contrasena-nueva").val("");
  $("#perfil-confirmar-contrasena").val("");

  // Reinicializar componente de imagen
  if (imageUploader) {
    try {
      imageUploader = new ImageUpload("container-perfil-fotografia", {
        maxSize: 2 * 1024 * 1024,
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "fotografia",
        defaultImage: getUrl("assets/img/administracion/usuarios/default.png"),
        existingImage: perfilData.fotografia || "",
        uploadPath: "assets/img/usuarios/",
        position: "center",
      });
    } catch (e) {
      console.warn("Error al reiniciar el componente de carga de imágenes:", e);
    }
  }

  // Mostrar modal
  const modal = new bootstrap.Modal(
    document.getElementById("modal-editar-perfil")
  );
  modal.show();
}

/**
 * Guarda los cambios del perfil
 */
function guardarPerfil() {
  // Validar el formulario
  if (!$formPerfil.valid()) {
    return;
  }

  // Preparar datos del formulario
  const formData = new FormData($formPerfil[0]);

  // Mostrar indicador de carga
  $("#btn-guardar-perfil")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud
  $.ajax({
    url: getUrl("api/perfil/actualizar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: (response) => {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-editar-perfil")
        ).hide();

        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Perfil actualizado correctamente"
        );

        // Recargar datos del perfil
        cargarDatosPerfil();

        // Actualizar avatar en el header si cambió
        if (response.fotografia) {
          $("#perfil-avatar-display").attr("src", response.fotografia);
          // También actualizar en el topbar si existe
          $(".avatar").attr("src", response.fotografia);
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al actualizar el perfil"
        );
      }
    },
    error: (xhr, status, error) => {
      mostrarToast("error", "Error", "Error al actualizar el perfil");
      console.error(error);
    },
    complete: () => {
      // Restaurar botón
      $("#btn-guardar-perfil")
        .prop("disabled", false)
        .html('<i class="bi bi-check-lg me-1"></i> Guardar Cambios');
    },
  });
}

/**
 * Formatea una fecha para mostrarla
 */
function formatearFecha(fecha) {
  if (!fecha) return "-";

  const options = {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };

  return new Date(fecha).toLocaleDateString("es-ES", options);
}

/**
 * Muestra un mensaje toast
 */
function mostrarToast(tipo, titulo, mensaje, duracion) {
  if (typeof showToast === "function") {
    showToast(tipo, titulo, mensaje, duracion);
  } else if (window.showSuccessToast && tipo === "success") {
    window.showSuccessToast(mensaje);
  } else if (window.showErrorToast && tipo === "error") {
    window.showErrorToast(mensaje);
  } else if (window.showInfoToast && (tipo === "info" || tipo === "warning")) {
    window.showInfoToast(mensaje);
  } else {
    console.log(`${titulo}: ${mensaje}`);
  }
}
