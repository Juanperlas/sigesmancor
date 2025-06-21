/**
 * Módulo de Gestión de Usuarios
 * SIGESMANCOR
 */

// Variables globales
let usuariosTable;
let usuarioSeleccionado = null;
let modoEdicion = false;
let usuarioActual = null;
let imageUploader;

// Declaración de variables globales
const $ = jQuery;
const bootstrap = window.bootstrap; // Asegúrate de que Bootstrap esté disponible globalmente

// Elementos DOM - AHORA DESPUÉS DE DECLARAR $
const $tablaUsuarios = $("#usuarios-table");
const $modalUsuario = $("#modal-usuario");
const $modalDetalleUsuario = $("#modal-detalle-usuario");
const $modalConfirmarEliminar = $("#modal-confirmar-eliminar");
const $formUsuario = $("#form-usuario");
const $panelDetalle = $("#usuario-detalle");

// Verificar si las variables ya están definidas en el objeto window
// Si no están definidas, no intentamos redeclararlas
if (!window.showErrorToast)
  window.showErrorToast = (msg) => {
    console.error(msg);
  };
if (!window.showSuccessToast)
  window.showSuccessToast = (msg) => {
    console.log(msg);
  };
if (!window.imageViewer)
  window.imageViewer = {
    show: () => {
      console.log("Visor de imágenes no disponible");
    },
  };
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

// Inicialización cuando el DOM está listo
$(document).ready(function () {
  // Inicializar componentes
  inicializarDataTable();
  inicializarEventos();
  initImageUploader();
  cargarUsuarioActual();

  // Aplicar filtros iniciales (si existen en localStorage)
  cargarFiltrosGuardados();
});

// Función para obtener la URL base
function getBaseUrl() {
  return window.location.pathname.split("/modulos/")[0] + "/";
}

// Función para construir URL completa
function getUrl(path) {
  return getBaseUrl() + path;
}

/**
 * Obtiene el usuario actual para verificaciones de permisos
 */
function cargarUsuarioActual() {
  // Simplemente verificar permisos basados en elementos del DOM
  // en lugar de hacer una llamada AJAX
  usuarioActual = {
    permisos: [],
  };

  // Añadir permisos basados en la presencia de elementos en el DOM
  if ($("#btn-nuevo-usuario").length > 0) {
    usuarioActual.permisos.push("administracion.usuarios.crear");
  }

  if ($("#btn-editar-desde-detalle").length > 0) {
    usuarioActual.permisos.push("administracion.usuarios.editar");
  }

  // Por defecto, permitir eliminar
  usuarioActual.permisos.push("administracion.usuarios.eliminar");
}

/**
 * Inicializa la tabla de usuarios con DataTables
 */
function inicializarDataTable() {
  usuariosTable = $tablaUsuarios.DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/administracion/usuarios/listar.php"),
      type: "POST",
      data: function (d) {
        // Añadir filtros
        d.filtros = {
          rol_id: $("#filtro-rol").val(),
          estado: $("#filtro-estado").val(),
        };
        return d;
      },
      error: function (xhr, error, thrown) {
        console.error("Error en la solicitud AJAX:", error);
        console.error("Excepción:", thrown);
        console.error("Respuesta del servidor:", xhr.responseText);
        mostrarToast("error", "Error", "Error al cargar los datos de la tabla");
      },
    },
    columns: [
      {
        data: "fotografia",
        render: function (data, type, row) {
          let imgSrc = data
            ? data
            : getUrl("assets/img/administracion/usuarios/default.png");
          return `<img src="${imgSrc}" alt="Foto" class="usuario-fotografia-tabla" data-usuario-id="${row.id}">`;
        },
        orderable: false,
      },
      { data: "username" },
      { data: "nombre_completo" },
      { data: "correo" },
      { data: "area" },
      {
        data: "esta_activo",
        render: function (data, type, row) {
          if (type === "display") {
            let clase = data == 1 ? "estado-activo" : "estado-inactivo";
            let texto = data == 1 ? "Activo" : "Inactivo";
            return `<span class="estado-badge ${clase}">${texto}</span>`;
          }
          return data;
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          let html = "";

          // Botón Ver
          html += `<button type="button" class="btn-accion btn-ver-usuario" data-id="${row.id}" title="Ver detalles">
                                <i class="bi bi-eye"></i>
                            </button>`;

          // Botón Editar (si tiene permiso)
          html += `<button type="button" class="btn-accion btn-editar-usuario" data-id="${row.id}" title="Editar usuario">
                                <i class="bi bi-pencil"></i>
                            </button>`;

          // Botón Eliminar (si tiene permiso)
          html += `<button type="button" class="btn-accion btn-eliminar-usuario" data-id="${row.id}" title="Eliminar usuario">
                                <i class="bi bi-trash"></i>
                            </button>`;

          return html;
        },
        orderable: false,
      },
    ],
    order: [[1, "asc"]], // Ordenar por username
    language: {
      url: getUrl("assets/plugins/datatables/js/es-ES.json"),
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      zeroRecords: "No se encontraron registros",
      emptyTable: "No hay datos disponibles en la tabla",
    },
    dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
    buttons: [
      {
        extend: "copy",
        text: '<i class="bi bi-clipboard"></i> Copiar',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5],
        },
      },
      {
        extend: "excel",
        text: '<i class="bi bi-file-excel"></i> Excel',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5],
        },
      },
      {
        extend: "pdf",
        text: '<i class="bi bi-file-pdf"></i> PDF',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5],
        },
      },
      {
        extend: "print",
        text: '<i class="bi bi-printer"></i> Imprimir',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5],
        },
      },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    pageLength: 25,
    drawCallback: function () {
      // Actualizar eventos después de cada redibujado
      $(".btn-ver-usuario")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          verUsuario(id);
        });

      $(".btn-editar-usuario")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          editarUsuario(id);
        });

      $(".btn-eliminar-usuario")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          confirmarEliminarUsuario(id);
        });

      $(".usuario-fotografia-tabla")
        .off("click")
        .on("click", function () {
          if (window.imageViewer) {
            const imgSrc = $(this).attr("src");
            const usuarioNombre = $(this).attr("alt") || "Componente";
            window.imageViewer.show(imgSrc, usuarioNombre);
            return false; // Detener la propagación del evento
          }
        });

      // Selección de fila
      $tablaUsuarios
        .find("tbody tr")
        .off("click")
        .on("click", function () {
          if (!$(this).hasClass("selected")) {
            usuariosTable.$("tr.selected").removeClass("selected");
            $(this).addClass("selected");

            // Obtener el ID del usuario de la fila seleccionada
            let id = usuariosTable.row(this).data().id;
            cargarDetalleUsuario(id);
          }
        });
    },
  });
}

/**
 * Inicializa todos los eventos del módulo
 */
function inicializarEventos() {
  // Botón Nuevo Usuario
  $("#btn-nuevo-usuario").on("click", function () {
    nuevoUsuario();
  });

  // Botón Guardar Usuario
  $("#btn-guardar-usuario").on("click", function () {
    guardarUsuario();
  });

  // Botón Confirmar Eliminar
  $("#btn-confirmar-eliminar").on("click", function () {
    eliminarUsuario();
  });

  // Botón Editar desde Detalle
  $("#btn-editar-desde-detalle").on("click", function () {
    if (usuarioSeleccionado) {
      editarUsuario(usuarioSeleccionado);
    }
  });

  // Botón Ver Fotografía
  $("#btn-ver-fotografia").on("click", function () {
    if (usuarioSeleccionado) {
      let imgSrc = $("#detalle-fotografia").attr("src");
      let nombre = $("#detalle-nombre-completo").text();

      // Usar el componente image-viewer
      if (window.imageViewer) {
        window.imageViewer.show(imgSrc, nombre);
      }
    }
  });

  // Botón Generar Reporte
  $("#btn-generar-reporte").on("click", function () {
    if (usuarioSeleccionado) {
      window.open(
        getUrl(
          "api/administracion/usuarios/generar_reporte.php?id=" +
            usuarioSeleccionado
        ),
        "_blank"
      );
    } else {
      mostrarToast("error", "Error", "No se ha seleccionado un usuario");
    }
  });

  // Filtros
  $("#btn-aplicar-filtros").on("click", function () {
    aplicarFiltros();
  });

  $("#btn-limpiar-filtros").on("click", function () {
    limpiarFiltros();
  });

  // Validación del formulario
  inicializarValidacionFormulario();
}

/**
 * Inicializa el componente de carga de imágenes
 */
function initImageUploader() {
  // Verificar si el contenedor existe
  if (document.getElementById("container-usuario-fotografia")) {
    // Inicializar el componente
    try {
      imageUploader = new ImageUpload("container-usuario-fotografia", {
        maxSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "fotografia",
        defaultImage: getUrl("assets/img/administracion/usuarios/default.png"),
        existingImage: "",
        uploadPath: "assets/img/usuarios/",
        position: "center", // Posicionar la cámara en el centro
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
  $formUsuario.validate({
    rules: {
      username: {
        required: true,
        minlength: 3,
        maxlength: 50,
      },
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
      contrasena: {
        required: function () {
          return !modoEdicion; // Requerido solo si es nuevo usuario
        },
        minlength: 6,
      },
      confirmar_contrasena: {
        equalTo: "#usuario-contrasena",
      },
    },
    messages: {
      username: {
        required: "El nombre de usuario es obligatorio",
        minlength: "El nombre de usuario debe tener al menos 3 caracteres",
        maxlength: "El nombre de usuario no puede tener más de 50 caracteres",
      },
      nombre_completo: {
        required: "El nombre completo es obligatorio",
        minlength: "El nombre completo debe tener al menos 3 caracteres",
        maxlength: "El nombre completo no puede tener más de 100 caracteres",
      },
      correo: {
        email: "Ingrese un correo electrónico válido",
      },
      contrasena: {
        required: "La contraseña es obligatoria para nuevos usuarios",
        minlength: "La contraseña debe tener al menos 6 caracteres",
      },
      confirmar_contrasena: {
        equalTo: "Las contraseñas no coinciden",
      },
    },
    errorElement: "span",
    errorPlacement: function (error, element) {
      error.addClass("invalid-feedback");
      element.closest(".form-group").append(error);
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass("is-invalid");
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass("is-invalid");
    },
    submitHandler: function (form) {
      guardarUsuario();
    },
  });
}

/**
 * Prepara el formulario para un nuevo usuario
 */
function nuevoUsuario() {
  modoEdicion = false;
  resetearFormulario();

  // Mostrar mensaje de contraseña requerida
  $(".contrasena-requerida").show();
  $("#contrasena-ayuda").hide();

  // Cambiar título del modal
  $("#modal-usuario-titulo").text("Nuevo Usuario");

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById("modal-usuario"));
  modal.show();
}

/**
 * Prepara el formulario para editar un usuario existente
 * @param {number} id - ID del usuario a editar
 */
function editarUsuario(id) {
  modoEdicion = true;
  resetearFormulario();

  // Ocultar mensaje de contraseña requerida
  $(".contrasena-requerida").hide();
  $("#contrasena-ayuda").show();

  // Cambiar título del modal
  $("#modal-usuario-titulo").text("Editar Usuario");

  // Cargar datos del usuario
  $.ajax({
    url: getUrl("api/administracion/usuarios/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const usuario = response.data;

        // Llenar el formulario con los datos del usuario
        $("#usuario-id").val(usuario.id);
        $("#usuario-username").val(usuario.username);
        $("#usuario-nombre-completo").val(usuario.nombre_completo);
        $("#usuario-correo").val(usuario.correo);
        $("#usuario-dni").val(usuario.dni);
        $("#usuario-telefono").val(usuario.telefono);
        $("#usuario-area").val(usuario.area);
        $("#usuario-direccion").val(usuario.direccion);
        $("#usuario-estado").val(usuario.esta_activo);

        // Limpiar contraseñas
        $("#usuario-contrasena").val("");
        $("#usuario-confirmar-contrasena").val("");

        // Marcar roles
        if (usuario.roles && usuario.roles.length > 0) {
          $('input[name="roles[]"]').prop("checked", false);
          usuario.roles.forEach(function (rol) {
            $(`#rol-${rol.id}`).prop("checked", true);
          });
        }

        // Inicializar componente de carga de imágenes con la imagen existente
        if (imageUploader) {
          try {
            // Reiniciar el componente con la imagen existente
            imageUploader = new ImageUpload("container-usuario-fotografia", {
              maxSize: 2 * 1024 * 1024,
              acceptedTypes: [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
              ],
              inputName: "fotografia",
              defaultImage: getUrl(
                "assets/img/administracion/usuarios/default.png"
              ),
              existingImage: usuario.fotografia || "",
              uploadPath: "assets/img/usuarios/",
              position: "center", // Posicionar la cámara en el centro
            });
          } catch (e) {
            console.warn(
              "Error al reiniciar el componente de carga de imágenes:",
              e
            );
          }
        } else {
          // Inicializar si no existe
          initImageUploader();
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-usuario")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los datos del usuario"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al cargar los datos del usuario");
      console.error(error);
    },
  });
}

/**
 * Muestra el modal de detalles de un usuario
 * @param {number} id - ID del usuario a ver
 */
function verUsuario(id) {
  $.ajax({
    url: getUrl("api/administracion/usuarios/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const usuario = response.data;

        // Llenar los detalles del usuario
        $("#detalle-nombre-completo").text(usuario.nombre_completo);
        $("#detalle-username").text(usuario.username);
        $("#detalle-correo").text(usuario.correo || "-");
        $("#detalle-dni").text(usuario.dni || "-");
        $("#detalle-telefono").text(usuario.telefono || "-");
        $("#detalle-area").text(usuario.area || "-");
        $("#detalle-direccion").text(usuario.direccion || "-");
        $("#detalle-creado-en").text(formatearFecha(usuario.creado_en));

        // Estado
        if (usuario.esta_activo == 1) {
          $("#detalle-estado")
            .removeClass("bg-danger")
            .addClass("bg-success")
            .text("Activo");
        } else {
          $("#detalle-estado")
            .removeClass("bg-success")
            .addClass("bg-danger")
            .text("Inactivo");
        }

        // Roles
        let rolesHTML = "";
        if (usuario.roles && usuario.roles.length > 0) {
          usuario.roles.forEach(function (rol) {
            rolesHTML += `<span class="badge bg-primary me-1">${rol.nombre}</span>`;
          });
        } else {
          rolesHTML = '<span class="text-muted">Sin roles asignados</span>';
        }
        $("#detalle-roles").html(rolesHTML);

        // Lista detallada de roles
        let rolesListaHTML = "";
        if (usuario.roles && usuario.roles.length > 0) {
          rolesListaHTML = '<ul class="mb-0 ps-3">';
          usuario.roles.forEach(function (rol) {
            rolesListaHTML += `<li><strong>${rol.nombre}</strong>`;
            if (rol.descripcion) {
              rolesListaHTML += `: <span class="text-muted">${rol.descripcion}</span>`;
            }
            rolesListaHTML += "</li>";
          });
          rolesListaHTML += "</ul>";
        } else {
          rolesListaHTML =
            '<span class="text-muted">Este usuario no tiene roles asignados</span>';
        }
        $("#detalle-roles-lista").html(rolesListaHTML);

        // Fotografía
        if (usuario.fotografia) {
          $("#detalle-fotografia").attr("src", usuario.fotografia);
        } else {
          $("#detalle-fotografia").attr(
            "src",
            getUrl("assets/img/administracion/usuarios/default.png")
          );
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-detalle-usuario")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del usuario"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del usuario"
      );
      console.error(error);
    },
  });
}

/**
 * Guarda un usuario (nuevo o existente)
 */
function guardarUsuario() {
  // Validar el formulario
  if (!$formUsuario.valid()) {
    return;
  }

  // Verificar si al menos un rol está seleccionado
  if ($('input[name="roles[]"]:checked').length === 0) {
    mostrarToast(
      "warning",
      "Atención",
      "Debe seleccionar al menos un rol para el usuario"
    );
    return;
  }

  // Preparar datos del formulario
  let formData = new FormData($formUsuario[0]);

  // Añadir modo (nuevo o edición)
  formData.append("modo", modoEdicion ? "editar" : "nuevo");

  // Mostrar indicador de carga
  $("#btn-guardar-usuario")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud
  $.ajax({
    url: getUrl("api/administracion/usuarios/guardar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-usuario")
        ).hide();

        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Usuario guardado correctamente"
        );

        // Recargar tabla
        usuariosTable.ajax.reload();

        // Si estamos editando el usuario seleccionado, actualizar panel de detalles
        if (modoEdicion && usuarioSeleccionado == $("#usuario-id").val()) {
          cargarDetalleUsuario(usuarioSeleccionado);
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar el usuario"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al guardar el usuario");
      console.error(error);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-guardar-usuario").prop("disabled", false).html("Guardar");
    },
  });
}

/**
 * Muestra el modal de confirmación para eliminar un usuario
 * @param {number} id - ID del usuario a eliminar
 */
function confirmarEliminarUsuario(id) {
  usuarioSeleccionado = id;
  const modal = new bootstrap.Modal(
    document.getElementById("modal-confirmar-eliminar")
  );
  modal.show();
}

/**
 * Elimina un usuario
 */
function eliminarUsuario() {
  if (!usuarioSeleccionado) {
    return;
  }

  // Mostrar indicador de carga
  $("#btn-confirmar-eliminar")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...'
    );

  $.ajax({
    url: getUrl("api/administracion/usuarios/eliminar.php"),
    type: "POST",
    data: { id: usuarioSeleccionado },
    dataType: "json",
    success: function (response) {
      // Cerrar modal de confirmación
      bootstrap.Modal.getInstance(
        document.getElementById("modal-confirmar-eliminar")
      ).hide();

      if (response.success) {
        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Usuario eliminado correctamente"
        );

        // Recargar tabla
        usuariosTable.ajax.reload();

        // Si el usuario eliminado es el seleccionado, limpiar panel de detalles
        if (usuarioSeleccionado == usuarioSeleccionado) {
          limpiarPanelDetalle();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al eliminar el usuario"
        );
      }
    },
    error: function (xhr, status, error) {
      bootstrap.Modal.getInstance(
        document.getElementById("modal-confirmar-eliminar")
      ).hide();
      mostrarToast("error", "Error", "Error al eliminar el usuario");
      console.error(error);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-confirmar-eliminar")
        .prop("disabled", false)
        .html('<i class="bi bi-trash me-2"></i>Eliminar Definitivamente');
    },
  });
}

/**
 * Carga los detalles de un usuario en el panel lateral
 * @param {number} id - ID del usuario
 */
function cargarDetalleUsuario(id) {
  usuarioSeleccionado = id;

  // Añadir clase de carga
  $panelDetalle.removeClass("loaded").addClass("loading");

  $.ajax({
    url: getUrl("api/administracion/usuarios/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const usuario = response.data;

        // Construir HTML para el panel de detalles
        let html = `
                    <div class="detail-header d-flex align-items-center">
                        <div class="detail-header-image me-3">
                            <img src="${
                              usuario.fotografia
                                ? usuario.fotografia
                                : getUrl(
                                    "assets/img/administracion/usuarios/default.png"
                                  )
                            }" 
                                alt="Foto" class="detail-header-img" id="detalle-lateral-img">
                        </div>
                        <div>
                            <h2 class="detail-title">${
                              usuario.nombre_completo
                            }</h2>
                            <p class="detail-subtitle">@${usuario.username}</p>
                        </div>
                    </div>
                    <div class="detail-content">
                        <!--<div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-person-circle"></i> Información Básica
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Correo:</span>
                                <span class="detail-value">${
                                  usuario.correo || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">DNI:</span>
                                <span class="detail-value">${
                                  usuario.dni || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${
                                      usuario.esta_activo == 1
                                        ? "bg-success"
                                        : "bg-danger"
                                    }">
                                        ${
                                          usuario.esta_activo == 1
                                            ? "Activo"
                                            : "Inactivo"
                                        }
                                    </span>
                                </span>
                            </div>
                        </div>-->
                        
                        <div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-telephone"></i> Contacto
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Teléfono:</span>
                                <span class="detail-value">${
                                  usuario.telefono || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Área:</span>
                                <span class="detail-value">${
                                  usuario.area || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Dirección:</span>
                                <span class="detail-value">${
                                  usuario.direccion || "-"
                                }</span>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-person-badge"></i> Roles
                            </div>
                            <div class="detail-item">
                                <div class="detail-value">`;

        // Añadir roles
        if (usuario.roles && usuario.roles.length > 0) {
          usuario.roles.forEach(function (rol) {
            html += `<span class="badge bg-primary me-1 mb-1">${rol.nombre}</span>`;
          });
        } else {
          html += '<span class="text-muted">Sin roles asignados</span>';
        }

        html += `
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-actions">`;

        // Botones de acción
        if (tienePermiso("administracion.usuarios.editar")) {
          html += `
                            <button type="button" class="btn btn-sm btn-primary" id="btn-editar-lateral">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </button>`;
        }

        if (tienePermiso("administracion.usuarios.eliminar")) {
          html += `
                            <button type="button" class="btn btn-sm btn-danger" id="btn-eliminar-lateral">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>`;
        }

        html += `
                            <button type="button" class="btn btn-sm btn-info" id="btn-ver-completo-lateral">
                                <i class="bi bi-eye me-1"></i> Ver Completo
                            </button>
                        </div>
                    </div>
                `;

        // Actualizar contenido
        $panelDetalle.html(html);

        // Añadir eventos a los botones
        $("#btn-editar-lateral").on("click", function () {
          editarUsuario(usuarioSeleccionado);
        });

        $("#btn-eliminar-lateral").on("click", function () {
          confirmarEliminarUsuario(usuarioSeleccionado);
        });

        $("#btn-ver-completo-lateral").on("click", function () {
          verUsuario(usuarioSeleccionado);
        });

        $("#detalle-lateral-img").on("click", function () {
          let imgSrc = $(this).attr("src");
          let nombre = usuario.nombre_completo;

          // Usar el componente image-viewer
          if (window.imageViewer) {
            window.imageViewer.show(imgSrc, nombre);
          }
        });

        // Quitar clase de carga y añadir clase de cargado
        setTimeout(function () {
          $panelDetalle.removeClass("loading").addClass("loaded");
        }, 300);
      } else {
        limpiarPanelDetalle();
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del usuario"
        );
      }
    },
    error: function (xhr, status, error) {
      limpiarPanelDetalle();
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del usuario"
      );
      console.error(error);
    },
  });
}

/**
 * Limpia el panel de detalles
 */
function limpiarPanelDetalle() {
  usuarioSeleccionado = null;

  // Restaurar contenido original
  $panelDetalle.html(`
        <div class="detail-header">
            <h2 class="detail-title">Detalles del Usuario</h2>
            <p class="detail-subtitle">Seleccione un usuario para ver información</p>
        </div>
        <div class="detail-content">
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un usuario para ver sus detalles
                </div>
            </div>
        </div>
    `);
}

/**
 * Resetea el formulario
 */
function resetearFormulario() {
  // Limpiar formulario
  $formUsuario[0].reset();
  $formUsuario.validate().resetForm();

  // Limpiar ID
  $("#usuario-id").val("");

  // Desmarcar todos los roles
  $('input[name="roles[]"]').prop("checked", false);

  // Quitar clases de error
  $(".is-invalid").removeClass("is-invalid");
}

/**
 * Aplica los filtros seleccionados
 */
function aplicarFiltros() {
  // Guardar filtros en localStorage
  guardarFiltros();

  // Recargar tabla con filtros
  usuariosTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros aplicados");
}

/**
 * Limpia los filtros
 */
function limpiarFiltros() {
  // Limpiar selectores
  $("#filtro-rol").val("");
  $("#filtro-estado").val("");

  // Limpiar localStorage
  localStorage.removeItem("usuarios_filtros");

  // Recargar tabla
  usuariosTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros eliminados");
}

/**
 * Guarda los filtros en localStorage
 */
function guardarFiltros() {
  const filtros = {
    rol_id: $("#filtro-rol").val(),
    estado: $("#filtro-estado").val(),
  };

  localStorage.setItem("usuarios_filtros", JSON.stringify(filtros));
}

/**
 * Carga los filtros guardados en localStorage
 */
function cargarFiltrosGuardados() {
  const filtrosGuardados = localStorage.getItem("usuarios_filtros");

  if (filtrosGuardados) {
    const filtros = JSON.parse(filtrosGuardados);

    $("#filtro-rol").val(filtros.rol_id);
    $("#filtro-estado").val(filtros.estado);

    // Aplicar filtros
    usuariosTable.ajax.reload();
  }
}

/**
 * Verifica si el usuario tiene un permiso específico
 * @param {string} permiso - Nombre del permiso
 * @return {boolean}
 */
function tienePermiso(permiso) {
  // Verificación basada en elementos del DOM
  if (permiso === "administracion.usuarios.crear") {
    return $("#btn-nuevo-usuario").length > 0;
  } else if (permiso === "administracion.usuarios.editar") {
    return $("#btn-editar-desde-detalle").length > 0;
  } else if (permiso === "administracion.usuarios.eliminar") {
    return true; // Por defecto permitir eliminar
  }

  return false;
}

/**
 * Formatea una fecha para mostrarla
 * @param {string} fecha - Fecha en formato ISO
 * @return {string} Fecha formateada
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
 * @param {string} tipo - Tipo de mensaje (success, error, warning, info)
 * @param {string} titulo - Título del mensaje
 * @param {string} mensaje - Texto del mensaje
 * @param {number} duracion - Duración en milisegundos (opcional)
 */
function mostrarToast(tipo, titulo, mensaje, duracion) {
  // Verificar si existe la función showToast (del componente toast)
  if (typeof showToast === "function") {
    showToast(tipo, titulo, mensaje, duracion);
  } else if (window.showSuccessToast && tipo === "success") {
    window.showSuccessToast(mensaje);
  } else if (window.showErrorToast && tipo === "error") {
    window.showErrorToast(mensaje);
  } else if (window.showInfoToast && (tipo === "info" || tipo === "warning")) {
    window.showInfoToast(mensaje);
  } else {
    // Fallback si no existe el componente toast
    console.log(`${titulo}: ${mensaje}`);
  }
}

// Añadir función showInfoToast si no existe
if (!window.showInfoToast) {
  window.showInfoToast = (msg) => {
    console.log(msg);
    // Si existe showSuccessToast, usarlo con un estilo diferente
    if (window.showSuccessToast) {
      try {
        // Intentar llamar a la función con un tipo diferente
        window.showSuccessToast(msg, "info");
      } catch (e) {
        console.log("Info toast:", msg);
      }
    }
  };
}
