/**
 * Módulo de Gestión de Roles y Permisos
 * SIGESMANCOR
 */

// Variables globales
let rolesTable;
let permisosTable;
let rolSeleccionado = null;
let permisoSeleccionado = null;
let modoEdicion = false;
let seccionActual = "roles"; // 'roles' o 'permisos'

// Declaración de variables globales
const $ = jQuery;
const bootstrap = window.bootstrap; // Asegúrate de que Bootstrap esté disponible globalmente

// Elementos DOM
const $tablaRoles = $("#roles-table");
const $tablaPermisos = $("#permisos-table");
const $modalRol = $("#modal-rol");
const $modalPermiso = $("#modal-permiso");
const $modalDetalleRol = $("#modal-detalle-rol");
const $modalDetallePermiso = $("#modal-detalle-permiso");
const $modalConfirmarEliminar = $("#modal-confirmar-eliminar");
const $formRol = $("#form-rol");
const $formPermiso = $("#form-permiso");
const $panelDetalleRol = $("#rol-detalle");
const $panelDetallePermiso = $("#permiso-detalle");
const $seccionRoles = $("#roles-content");
const $seccionPermisos = $("#permisos-content");
const $btnSeccionRoles = $("#roles-tab");
const $btnSeccionPermisos = $("#permisos-tab");

// Verificar si las variables ya están definidas en el objeto window
if (!window.showErrorToast)
  window.showErrorToast = (msg) => {
    console.error(msg);
  };
if (!window.showSuccessToast)
  window.showSuccessToast = (msg) => {
    console.log(msg);
  };
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

// Inicialización cuando el DOM está listo
$(document).ready(function () {
  // Inicializar componentes
  inicializarDataTables();
  inicializarEventos();

  // Aplicar filtros iniciales (si existen en localStorage)
  cargarFiltrosGuardados();

  // Mostrar sección inicial (roles por defecto)
  mostrarSeccion("roles");
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
 * Inicializa las tablas de roles y permisos con DataTables
 */
function inicializarDataTables() {
  // Inicializar tabla de roles
  rolesTable = $tablaRoles.DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/administracion/rolespermisos/roles/listar.php"),
      type: "POST",
      data: function (d) {
        // Añadir filtros
        d.filtros = {
          estado: $("#filtro-estado-rol").val(),
        };
        return d;
      },
      error: function (xhr, error, thrown) {
        console.error("Error en la solicitud AJAX:", error);
        console.error("Excepción:", thrown);
        console.error("Respuesta del servidor:", xhr.responseText);
        mostrarToast(
          "error",
          "Error",
          "Error al cargar los datos de la tabla de roles"
        );
      },
    },
    columns: [
      { data: "id" },
      { data: "nombre" },
      { data: "descripcion" },
      {
        data: "permisos_count",
        render: function (data) {
          return `<span class="badge bg-info">${data}</span>`;
        },
      },
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
          html += `<button type="button" class="btn-accion btn-ver-rol" data-id="${row.id}" title="Ver detalles">
                      <i class="bi bi-eye"></i>
                  </button>`;

          // Botón Editar
          html += `<button type="button" class="btn-accion btn-editar-rol" data-id="${row.id}" title="Editar rol">
                      <i class="bi bi-pencil"></i>
                  </button>`;

          // Botón Eliminar
          html += `<button type="button" class="btn-accion btn-eliminar-rol" data-id="${row.id}" title="Eliminar rol">
                      <i class="bi bi-trash"></i>
                  </button>`;

          return html;
        },
        orderable: false,
      },
    ],
    order: [[0, "asc"]], // Ordenar por nombre
    language: {
      url: getUrl("assets/plugins/datatables/js/es-ES.json"),
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      zeroRecords: "No se encontraron registros",
      emptyTable: "No hay datos disponibles en la tabla",
    },
    dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    pageLength: 25,
    drawCallback: function () {
      // Actualizar eventos después de cada redibujado
      $(".btn-ver-rol")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          verRol(id);
        });

      $(".btn-editar-rol")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          editarRol(id);
        });

      $(".btn-eliminar-rol")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          confirmarEliminarRol(id);
        });

      // Selección de fila
      $tablaRoles
        .find("tbody tr")
        .off("click")
        .on("click", function () {
          if (!$(this).hasClass("selected")) {
            rolesTable.$("tr.selected").removeClass("selected");
            $(this).addClass("selected");

            // Obtener el ID del rol de la fila seleccionada
            let id = rolesTable.row(this).data().id;
            cargarDetalleRol(id);
          }
        });
    },
  });

  // Inicializar tabla de permisos
  permisosTable = $tablaPermisos.DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/administracion/rolespermisos/permisos/listar.php"),
      type: "POST",
      data: function (d) {
        // Añadir filtros
        d.filtros = {
          modulo_id: $("#filtro-modulo").val(),
        };
        return d;
      },
      error: function (xhr, error, thrown) {
        console.error("Error en la solicitud AJAX:", error);
        console.error("Excepción:", thrown);
        console.error("Respuesta del servidor:", xhr.responseText);
        mostrarToast(
          "error",
          "Error",
          "Error al cargar los datos de la tabla de permisos"
        );
      },
    },
    columns: [
      { data: "id" },
      { data: "nombre" },
      { data: "modulo_nombre" },
      { data: "descripcion" },
      {
        data: "roles_count",
        render: function (data) {
          return `<span class="badge bg-info">${data}</span>`;
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          let html = "";

          // Botón Ver
          html += `<button type="button" class="btn-accion btn-ver-permiso" data-id="${row.id}" title="Ver detalles">
                      <i class="bi bi-eye"></i>
                  </button>`;

          // Botón Editar
          html += `<button type="button" class="btn-accion btn-editar-permiso" data-id="${row.id}" title="Editar permiso">
                      <i class="bi bi-pencil"></i>
                  </button>`;

          // Botón Eliminar
          html += `<button type="button" class="btn-accion btn-eliminar-permiso" data-id="${row.id}" title="Eliminar permiso">
                      <i class="bi bi-trash"></i>
                  </button>`;

          return html;
        },
        orderable: false,
      },
    ],
    order: [
      [1, "asc"],
      [0, "asc"],
    ], // Ordenar por módulo y luego por nombre
    language: {
      url: getUrl("assets/plugins/datatables/js/es-ES.json"),
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      zeroRecords: "No se encontraron registros",
      emptyTable: "No hay datos disponibles en la tabla",
    },
    dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    pageLength: 25,
    drawCallback: function () {
      // Actualizar eventos después de cada redibujado
      $(".btn-ver-permiso")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          verPermiso(id);
        });

      $(".btn-editar-permiso")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          editarPermiso(id);
        });

      $(".btn-eliminar-permiso")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          confirmarEliminarPermiso(id);
        });

      // Selección de fila
      $tablaPermisos
        .find("tbody tr")
        .off("click")
        .on("click", function () {
          if (!$(this).hasClass("selected")) {
            permisosTable.$("tr.selected").removeClass("selected");
            $(this).addClass("selected");

            // Obtener el ID del permiso de la fila seleccionada
            let id = permisosTable.row(this).data().id;
            cargarDetallePermiso(id);
          }
        });
    },
  });
}

/**
 * Inicializa todos los eventos del módulo
 */
function inicializarEventos() {
  // Botones de cambio de sección
  $btnSeccionRoles.on("click", function () {
    mostrarSeccion("roles");
  });

  $btnSeccionPermisos.on("click", function () {
    mostrarSeccion("permisos");
  });

  // Botones para Roles
  $("#btn-nuevo-rol").on("click", function () {
    nuevoRol();
  });

  $("#btn-guardar-rol").on("click", function () {
    guardarRol();
  });

  $("#btn-editar-desde-detalle-rol").on("click", function () {
    if (rolSeleccionado) {
      editarRol(rolSeleccionado);
    }
  });

  // Botones para Permisos
  $("#btn-nuevo-permiso").on("click", function () {
    nuevoPermiso();
  });

  $("#btn-guardar-permiso").on("click", function () {
    guardarPermiso();
  });

  $("#btn-editar-desde-detalle-permiso").on("click", function () {
    if (permisoSeleccionado) {
      editarPermiso(permisoSeleccionado);
    }
  });

  // Botón Confirmar Eliminar (compartido)
  $("#btn-confirmar-eliminar").on("click", function () {
    if (seccionActual === "roles") {
      eliminarRol();
    } else {
      eliminarPermiso();
    }
  });

  // Filtros para Roles
  $("#btn-aplicar-filtros-roles").on("click", function () {
    aplicarFiltrosRoles();
  });

  $("#btn-limpiar-filtros-roles").on("click", function () {
    limpiarFiltrosRoles();
  });

  // Filtros para Permisos
  $("#btn-aplicar-filtros-permisos").on("click", function () {
    aplicarFiltrosPermisos();
  });

  $("#btn-limpiar-filtros-permisos").on("click", function () {
    limpiarFiltrosPermisos();
  });

  // Implementar búsqueda de permisos en el modal
  $("#buscar-permisos").on("keyup", function () {
    const valor = $(this).val().toLowerCase();
    $(".permiso-modulo").each(function () {
      const modulo = $(this);
      const permisosVisibles = modulo.find(".form-check").filter(function () {
        const texto = $(this).text().toLowerCase();
        return texto.indexOf(valor) > -1;
      });

      if (permisosVisibles.length > 0) {
        modulo.show();
        modulo.find(".form-check").each(function () {
          const texto = $(this).text().toLowerCase();
          if (texto.indexOf(valor) > -1) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      } else {
        modulo.hide();
      }
    });
  });

  // Validación de formularios
  inicializarValidacionFormularios();
}

/**
 * Inicializa la validación de los formularios
 */
function inicializarValidacionFormularios() {
  // Validación del formulario de roles
  $formRol.validate({
    rules: {
      nombre: {
        required: true,
        minlength: 2,
        maxlength: 50,
      },
      descripcion: {
        maxlength: 255,
      },
    },
    messages: {
      nombre: {
        required: "El nombre del rol es obligatorio",
        minlength: "El nombre debe tener al menos 2 caracteres",
        maxlength: "El nombre no puede tener más de 50 caracteres",
      },
      descripcion: {
        maxlength: "La descripción no puede tener más de 255 caracteres",
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
      guardarRol();
    },
  });

  // Validación del formulario de permisos
  $formPermiso.validate({
    rules: {
      nombre: {
        required: true,
        minlength: 2,
        maxlength: 100,
      },
      modulo_id: {
        required: true,
      },
      descripcion: {
        maxlength: 255,
      },
    },
    messages: {
      nombre: {
        required: "El nombre del permiso es obligatorio",
        minlength: "El nombre debe tener al menos 2 caracteres",
        maxlength: "El nombre no puede tener más de 100 caracteres",
      },
      modulo_id: {
        required: "El módulo es obligatorio",
      },
      descripcion: {
        maxlength: "La descripción no puede tener más de 255 caracteres",
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
      guardarPermiso();
    },
  });
}

/**
 * Muestra la sección especificada (roles o permisos)
 * @param {string} seccion - 'roles' o 'permisos'
 */
function mostrarSeccion(seccion) {
  seccionActual = seccion;

  if (seccion === "roles") {
    // Activar la pestaña de roles usando Bootstrap
    $("#roles-tab").tab("show");

    // Actualizar tabla de roles
    if (rolesTable) {
      rolesTable.ajax.reload(null, false);
    }
  } else {
    // Activar la pestaña de permisos usando Bootstrap
    $("#permisos-tab").tab("show");

    // Actualizar tabla de permisos
    if (permisosTable) {
      permisosTable.ajax.reload(null, false);
    }
  }
}

// ==================== FUNCIONES PARA ROLES ====================

/**
 * Prepara el formulario para un nuevo rol
 */
function nuevoRol() {
  modoEdicion = false;
  resetearFormularioRol();

  // Cambiar título del modal
  $("#modal-rol-titulo").text("Nuevo Rol");

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById("modal-rol"));
  modal.show();
}

/**
 * Prepara el formulario para editar un rol existente
 * @param {number} id - ID del rol a editar
 */
function editarRol(id) {
  modoEdicion = true;
  resetearFormularioRol();

  // Cambiar título del modal
  $("#modal-rol-titulo").text("Editar Rol");

  // Cargar datos del rol
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/roles/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const rol = response.data;

        // Llenar el formulario con los datos del rol
        $("#rol-id").val(rol.id);
        $("#rol-nombre").val(rol.nombre);
        $("#rol-descripcion").val(rol.descripcion);
        $("#rol-estado").val(rol.esta_activo);

        // Marcar permisos
        if (rol.permisos && rol.permisos.length > 0) {
          $('input[name="permisos[]"]').prop("checked", false);
          rol.permisos.forEach(function (permiso) {
            $(`#permiso-${permiso.id}`).prop("checked", true);
          });
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById("modal-rol"));
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los datos del rol"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al cargar los datos del rol");
      console.error(error);
    },
  });
}

/**
 * Muestra el modal de detalles de un rol
 * @param {number} id - ID del rol a ver
 */
function verRol(id) {
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/roles/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const rol = response.data;

        // Llenar los detalles del rol
        $("#detalle-rol-nombre").text(rol.nombre);
        $("#detalle-rol-descripcion").text(rol.descripcion || "-");
        $("#detalle-rol-creado-en").text(formatearFecha(rol.creado_en));

        // Estado
        if (rol.esta_activo == 1) {
          $("#detalle-rol-estado")
            .removeClass("bg-danger")
            .addClass("bg-success")
            .text("Activo");
        } else {
          $("#detalle-rol-estado")
            .removeClass("bg-success")
            .addClass("bg-danger")
            .text("Inactivo");
        }

        // Lista de permisos
        let permisosHTML = "";
        if (rol.permisos && rol.permisos.length > 0) {
          permisosHTML = '<ul class="mb-0 ps-3">';
          rol.permisos.forEach(function (permiso) {
            permisosHTML += `<li><strong>${permiso.nombre}</strong>`;
            if (permiso.descripcion) {
              permisosHTML += `: <span class="text-muted">${permiso.descripcion}</span>`;
            }
            permisosHTML += `<br><small class="text-muted">Módulo: ${permiso.modulo_nombre}</small>`;
            permisosHTML += "</li>";
          });
          permisosHTML += "</ul>";
        } else {
          permisosHTML =
            '<span class="text-muted">Este rol no tiene permisos asignados</span>';
        }
        $("#detalle-rol-permisos-lista").html(permisosHTML);

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-detalle-rol")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del rol"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al cargar los detalles del rol");
      console.error(error);
    },
  });
}

/**
 * Guarda un rol (nuevo o existente)
 */
function guardarRol() {
  // Validar el formulario
  if (!$formRol.valid()) {
    return;
  }

  // Preparar datos del formulario
  let formData = new FormData($formRol[0]);

  // Añadir modo (nuevo o edición)
  formData.append("modo", modoEdicion ? "editar" : "nuevo");

  // Mostrar indicador de carga
  $("#btn-guardar-rol")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/roles/guardar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-rol")
        ).hide();

        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Rol guardado correctamente"
        );

        // Recargar tabla
        rolesTable.ajax.reload();

        // Si estamos editando el rol seleccionado, actualizar panel de detalles
        if (modoEdicion && rolSeleccionado == $("#rol-id").val()) {
          cargarDetalleRol(rolSeleccionado);
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar el rol"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al guardar el rol");
      console.error(error);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-guardar-rol").prop("disabled", false).html("Guardar");
    },
  });
}

/**
 * Muestra el modal de confirmación para eliminar un rol
 * @param {number} id - ID del rol a eliminar
 */
function confirmarEliminarRol(id) {
  rolSeleccionado = id;

  // Configurar modal para eliminar rol
  $("#modal-confirmar-eliminar-titulo").text("Confirmar Eliminación de Rol");
  $("#modal-confirmar-eliminar-mensaje").text(
    "¿Está seguro que desea eliminar este rol?"
  );
  $("#modal-confirmar-eliminar-submensaje").text(
    "Esta acción eliminará permanentemente el rol y todas sus asociaciones."
  );

  const modal = new bootstrap.Modal(
    document.getElementById("modal-confirmar-eliminar")
  );
  modal.show();
}

/**
 * Elimina un rol
 */
function eliminarRol() {
  if (!rolSeleccionado) {
    return;
  }

  // Mostrar indicador de carga
  $("#btn-confirmar-eliminar")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...'
    );

  $.ajax({
    url: getUrl("api/administracion/rolespermisos/roles/eliminar.php"),
    type: "POST",
    data: { id: rolSeleccionado },
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
          response.message || "Rol eliminado correctamente"
        );

        // Recargar tabla
        rolesTable.ajax.reload();

        // Si el rol eliminado es el seleccionado, limpiar panel de detalles
        if (rolSeleccionado == rolSeleccionado) {
          limpiarPanelDetalleRol();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al eliminar el rol"
        );
      }
    },
    error: function (xhr, status, error) {
      bootstrap.Modal.getInstance(
        document.getElementById("modal-confirmar-eliminar")
      ).hide();
      mostrarToast("error", "Error", "Error al eliminar el rol");
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
 * Carga los detalles de un rol en el panel lateral
 * @param {number} id - ID del rol
 */
function cargarDetalleRol(id) {
  rolSeleccionado = id;

  // Añadir clase de carga
  $panelDetalleRol.removeClass("loaded").addClass("loading");

  $.ajax({
    url: getUrl("api/administracion/rolespermisos/roles/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const rol = response.data;

        // Construir HTML para el panel de detalles
        let html = `
          <div class="detail-header">
            <h2 class="detail-title">${rol.nombre}</h2>
            <p class="detail-subtitle">
              <span class="badge ${
                rol.esta_activo == 1 ? "bg-success" : "bg-danger"
              }">
                ${rol.esta_activo == 1 ? "Activo" : "Inactivo"}
              </span>
            </p>
          </div>
          <div class="detail-content">
            <div class="detail-section">
              <div class="detail-section-title">
                <i class="bi bi-info-circle"></i> Información Básica
              </div>
              <div class="detail-item">
                <span class="detail-label">Descripción:</span>
                <span class="detail-value">${rol.descripcion || "-"}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fecha Creación:</span>
                <span class="detail-value">${formatearFecha(
                  rol.creado_en
                )}</span>
              </div>
            </div>
            
            <div class="detail-section">
              <div class="detail-section-title">
                <i class="bi bi-shield-lock"></i> Permisos Asignados
              </div>
              <div class="detail-item">
                <div class="detail-value">`;

        // Añadir permisos
        if (rol.permisos && rol.permisos.length > 0) {
          html += '<ul class="mb-0 ps-3">';
          rol.permisos.forEach(function (permiso) {
            html += `<li><strong>${permiso.nombre}</strong>`;
            if (permiso.descripcion) {
              html += `<br><small class="text-muted">${permiso.descripcion}</small>`;
            }
            html += `<br><small class="text-muted">Módulo: ${permiso.modulo_nombre}</small>`;
            html += "</li>";
          });
          html += "</ul>";
        } else {
          html += '<span class="text-muted">Sin permisos asignados</span>';
        }

        html += `
                </div>
              </div>
            </div>
            
            <div class="detail-actions">
              <button type="button" class="btn btn-sm btn-primary" id="btn-editar-lateral-rol">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
              <button type="button" class="btn btn-sm btn-danger" id="btn-eliminar-lateral-rol">
                <i class="bi bi-trash me-1"></i> Eliminar
              </button>
              <button type="button" class="btn btn-sm btn-info" id="btn-ver-completo-lateral-rol">
                <i class="bi bi-eye me-1"></i> Ver Completo
              </button>
            </div>
          </div>
        `;

        // Actualizar contenido
        $panelDetalleRol.html(html);

        // Añadir eventos a los botones
        $("#btn-editar-lateral-rol").on("click", function () {
          editarRol(rolSeleccionado);
        });

        $("#btn-eliminar-lateral-rol").on("click", function () {
          confirmarEliminarRol(rolSeleccionado);
        });

        $("#btn-ver-completo-lateral-rol").on("click", function () {
          verRol(rolSeleccionado);
        });

        // Quitar clase de carga y añadir clase de cargado
        setTimeout(function () {
          $panelDetalleRol.removeClass("loading").addClass("loaded");
        }, 300);
      } else {
        limpiarPanelDetalleRol();
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del rol"
        );
      }
    },
    error: function (xhr, status, error) {
      limpiarPanelDetalleRol();
      mostrarToast("error", "Error", "Error al cargar los detalles del rol");
      console.error(error);
    },
  });
}

/**
 * Limpia el panel de detalles de roles
 */
function limpiarPanelDetalleRol() {
  rolSeleccionado = null;

  // Restaurar contenido original
  $panelDetalleRol.html(`
    <div class="detail-header">
      <h2 class="detail-title">Detalles del Rol</h2>
      <p class="detail-subtitle">Seleccione un rol para ver información</p>
    </div>
    <div class="detail-content">
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-shield"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un rol para ver sus detalles
        </div>
      </div>
    </div>
  `);
}

/**
 * Resetea el formulario de roles
 */
function resetearFormularioRol() {
  // Limpiar formulario
  $formRol[0].reset();
  $formRol.validate().resetForm();

  // Limpiar ID
  $("#rol-id").val("");

  // Desmarcar todos los permisos
  $('input[name="permisos[]"]').prop("checked", false);

  // Quitar clases de error
  $(".is-invalid").removeClass("is-invalid");
}

/**
 * Aplica los filtros seleccionados para roles
 */
function aplicarFiltrosRoles() {
  // Guardar filtros en localStorage
  guardarFiltrosRoles();

  // Recargar tabla con filtros
  rolesTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros aplicados");
}

/**
 * Limpia los filtros de roles
 */
function limpiarFiltrosRoles() {
  // Limpiar selectores
  $("#filtro-estado-rol").val("");

  // Limpiar localStorage
  localStorage.removeItem("roles_filtros");

  // Recargar tabla
  rolesTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros eliminados");
}

/**
 * Guarda los filtros de roles en localStorage
 */
function guardarFiltrosRoles() {
  const filtros = {
    estado: $("#filtro-estado-rol").val(),
  };

  localStorage.setItem("roles_filtros", JSON.stringify(filtros));
}

// ==================== FUNCIONES PARA PERMISOS ====================

/**
 * Prepara el formulario para un nuevo permiso
 */
function nuevoPermiso() {
  modoEdicion = false;
  resetearFormularioPermiso();

  // Cambiar título del modal
  $("#modal-permiso-titulo").text("Nuevo Permiso");

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById("modal-permiso"));
  modal.show();
}

/**
 * Prepara el formulario para editar un permiso existente
 * @param {number} id - ID del permiso a editar
 */
function editarPermiso(id) {
  modoEdicion = true;
  resetearFormularioPermiso();

  // Cambiar título del modal
  $("#modal-permiso-titulo").text("Editar Permiso");

  // Cargar datos del permiso
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/permisos/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const permiso = response.data;

        // Llenar el formulario con los datos del permiso
        $("#permiso-id").val(permiso.id);
        $("#permiso-nombre").val(permiso.nombre);
        $("#permiso-modulo-id").val(permiso.modulo_id);
        $("#permiso-descripcion").val(permiso.descripcion);

        // Marcar roles
        if (permiso.roles && permiso.roles.length > 0) {
          $('input[name="roles[]"]').prop("checked", false);
          permiso.roles.forEach(function (rol) {
            $(`#rol-permiso-${rol.id}`).prop("checked", true);
          });
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-permiso")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los datos del permiso"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al cargar los datos del permiso");
      console.error(error);
    },
  });
}

/**
 * Muestra el modal de detalles de un permiso
 * @param {number} id - ID del permiso a ver
 */
function verPermiso(id) {
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/permisos/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const permiso = response.data;

        // Llenar los detalles del permiso
        $("#detalle-permiso-nombre").text(permiso.nombre);
        $("#detalle-permiso-modulo").text(permiso.modulo_nombre);
        $("#detalle-permiso-descripcion").text(permiso.descripcion || "-");
        $("#detalle-permiso-creado-en").text(formatearFecha(permiso.creado_en));

        // Lista de roles
        let rolesHTML = "";
        if (permiso.roles && permiso.roles.length > 0) {
          rolesHTML = '<ul class="mb-0 ps-3">';
          permiso.roles.forEach(function (rol) {
            rolesHTML += `<li><strong>${rol.nombre}</strong>`;
            if (rol.descripcion) {
              rolesHTML += `: <span class="text-muted">${rol.descripcion}</span>`;
            }
            rolesHTML += "</li>";
          });
          rolesHTML += "</ul>";
        } else {
          rolesHTML =
            '<span class="text-muted">Este permiso no está asignado a ningún rol</span>';
        }
        $("#detalle-permiso-roles-lista").html(rolesHTML);

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-detalle-permiso")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del permiso"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del permiso"
      );
      console.error(error);
    },
  });
}

/**
 * Guarda un permiso (nuevo o existente)
 */
function guardarPermiso() {
  // Validar el formulario
  if (!$formPermiso.valid()) {
    return;
  }

  // Preparar datos del formulario
  let formData = new FormData($formPermiso[0]);

  // Añadir modo (nuevo o edición)
  formData.append("modo", modoEdicion ? "editar" : "nuevo");

  // Mostrar indicador de carga
  $("#btn-guardar-permiso")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud
  $.ajax({
    url: getUrl("api/administracion/rolespermisos/permisos/guardar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-permiso")
        ).hide();

        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Permiso guardado correctamente"
        );

        // Recargar tabla
        permisosTable.ajax.reload();

        // Si estamos editando el permiso seleccionado, actualizar panel de detalles
        if (modoEdicion && permisoSeleccionado == $("#permiso-id").val()) {
          cargarDetallePermiso(permisoSeleccionado);
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar el permiso"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al guardar el permiso");
      console.error(error);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-guardar-permiso").prop("disabled", false).html("Guardar");
    },
  });
}

/**
 * Muestra el modal de confirmación para eliminar un permiso
 * @param {number} id - ID del permiso a eliminar
 */
function confirmarEliminarPermiso(id) {
  permisoSeleccionado = id;

  // Configurar modal para eliminar permiso
  $("#modal-confirmar-eliminar-titulo").text(
    "Confirmar Eliminación de Permiso"
  );
  $("#modal-confirmar-eliminar-mensaje").text(
    "¿Está seguro que desea eliminar este permiso?"
  );
  $("#modal-confirmar-eliminar-submensaje").text(
    "Esta acción eliminará permanentemente el permiso y todas sus asociaciones."
  );

  const modal = new bootstrap.Modal(
    document.getElementById("modal-confirmar-eliminar")
  );
  modal.show();
}

/**
 * Elimina un permiso
 */
function eliminarPermiso() {
  if (!permisoSeleccionado) {
    return;
  }

  // Mostrar indicador de carga
  $("#btn-confirmar-eliminar")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...'
    );

  $.ajax({
    url: getUrl("api/administracion/rolespermisos/permisos/eliminar.php"),
    type: "POST",
    data: { id: permisoSeleccionado },
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
          response.message || "Permiso eliminado correctamente"
        );

        // Recargar tabla
        permisosTable.ajax.reload();

        // Si el permiso eliminado es el seleccionado, limpiar panel de detalles
        if (permisoSeleccionado == permisoSeleccionado) {
          limpiarPanelDetallePermiso();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al eliminar el permiso"
        );
      }
    },
    error: function (xhr, status, error) {
      bootstrap.Modal.getInstance(
        document.getElementById("modal-confirmar-eliminar")
      ).hide();
      mostrarToast("error", "Error", "Error al eliminar el permiso");
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
 * Carga los detalles de un permiso en el panel lateral
 * @param {number} id - ID del permiso
 */
function cargarDetallePermiso(id) {
  permisoSeleccionado = id;

  // Añadir clase de carga
  $panelDetallePermiso.removeClass("loaded").addClass("loading");

  $.ajax({
    url: getUrl("api/administracion/rolespermisos/permisos/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const permiso = response.data;

        // Construir HTML para el panel de detalles
        let html = `
          <div class="detail-header">
            <h2 class="detail-title">${permiso.nombre}</h2>
            <p class="detail-subtitle">Módulo: ${permiso.modulo_nombre}</p>
          </div>
          <div class="detail-content">
            <div class="detail-section">
              <div class="detail-section-title">
                <i class="bi bi-info-circle"></i> Información Básica
              </div>
              <div class="detail-item">
                <span class="detail-label">Descripción:</span>
                <span class="detail-value">${permiso.descripcion || "-"}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fecha Creación:</span>
                <span class="detail-value">${formatearFecha(
                  permiso.creado_en
                )}</span>
              </div>
            </div>
            
            <div class="detail-section">
              <div class="detail-section-title">
                <i class="bi bi-people"></i> Roles Asignados
              </div>
              <div class="detail-item">
                <div class="detail-value">`;

        // Añadir roles
        if (permiso.roles && permiso.roles.length > 0) {
          html += '<ul class="mb-0 ps-3">';
          permiso.roles.forEach(function (rol) {
            html += `<li><strong>${rol.nombre}</strong>`;
            if (rol.descripcion) {
              html += `<br><small class="text-muted">${rol.descripcion}</small>`;
            }
            html += "</li>";
          });
          html += "</ul>";
        } else {
          html += '<span class="text-muted">Sin roles asignados</span>';
        }

        html += `
                </div>
              </div>
            </div>
            
            <div class="detail-actions">
              <button type="button" class="btn btn-sm btn-primary" id="btn-editar-lateral-permiso">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
              <button type="button" class="btn btn-sm btn-danger" id="btn-eliminar-lateral-permiso">
                <i class="bi bi-trash me-1"></i> Eliminar
              </button>
              <button type="button" class="btn btn-sm btn-info" id="btn-ver-completo-lateral-permiso">
                <i class="bi bi-eye me-1"></i> Ver Completo
              </button>
            </div>
          </div>
        `;

        // Actualizar contenido
        $panelDetallePermiso.html(html);

        // Añadir eventos a los botones
        $("#btn-editar-lateral-permiso").on("click", function () {
          editarPermiso(permisoSeleccionado);
        });

        $("#btn-eliminar-lateral-permiso").on("click", function () {
          confirmarEliminarPermiso(permisoSeleccionado);
        });

        $("#btn-ver-completo-lateral-permiso").on("click", function () {
          verPermiso(permisoSeleccionado);
        });

        // Quitar clase de carga y añadir clase de cargado
        setTimeout(function () {
          $panelDetallePermiso.removeClass("loading").addClass("loaded");
        }, 300);
      } else {
        limpiarPanelDetallePermiso();
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del permiso"
        );
      }
    },
    error: function (xhr, status, error) {
      limpiarPanelDetallePermiso();
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del permiso"
      );
      console.error(error);
    },
  });
}

/**
 * Limpia el panel de detalles de permisos
 */
function limpiarPanelDetallePermiso() {
  permisoSeleccionado = null;

  // Restaurar contenido original
  $panelDetallePermiso.html(`
    <div class="detail-header">
      <h2 class="detail-title">Detalles del Permiso</h2>
      <p class="detail-subtitle">Seleccione un permiso para ver información</p>
    </div>
    <div class="detail-content">
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-key"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un permiso para ver sus detalles
        </div>
      </div>
    </div>
  `);
}

/**
 * Resetea el formulario de permisos
 */
function resetearFormularioPermiso() {
  // Limpiar formulario
  $formPermiso[0].reset();
  $formPermiso.validate().resetForm();

  // Limpiar ID
  $("#permiso-id").val("");

  // Desmarcar todos los roles
  $('input[name="roles[]"]').prop("checked", false);

  // Quitar clases de error
  $(".is-invalid").removeClass("is-invalid");
}

/**
 * Aplica los filtros seleccionados para permisos
 */
function aplicarFiltrosPermisos() {
  // Guardar filtros en localStorage
  guardarFiltrosPermisos();

  // Recargar tabla con filtros
  permisosTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros aplicados");
}

/**
 * Limpia los filtros de permisos
 */
function limpiarFiltrosPermisos() {
  // Limpiar selectores
  $("#filtro-modulo").val("");

  // Limpiar localStorage
  localStorage.removeItem("permisos_filtros");

  // Recargar tabla
  permisosTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros eliminados");
}

/**
 * Guarda los filtros de permisos en localStorage
 */
function guardarFiltrosPermisos() {
  const filtros = {
    modulo_id: $("#filtro-modulo").val(),
  };

  localStorage.setItem("permisos_filtros", JSON.stringify(filtros));
}

/**
 * Carga los filtros guardados en localStorage
 */
function cargarFiltrosGuardados() {
  // Cargar filtros de roles
  const filtrosRolesGuardados = localStorage.getItem("roles_filtros");
  if (filtrosRolesGuardados) {
    const filtrosRoles = JSON.parse(filtrosRolesGuardados);
    $("#filtro-estado-rol").val(filtrosRoles.estado);
  }

  // Cargar filtros de permisos
  const filtrosPermisosGuardados = localStorage.getItem("permisos_filtros");
  if (filtrosPermisosGuardados) {
    const filtrosPermisos = JSON.parse(filtrosPermisosGuardados);
    $("#filtro-modulo").val(filtrosPermisos.modulo_id);
  }
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

/**
 * Verifica si el usuario tiene un permiso específico
 * @param {string} permiso - Nombre del permiso
 * @return {boolean}
 */
function tienePermiso(permiso) {
  // Esta función debería verificar si el usuario tiene el permiso especificado
  // En este caso, simplemente verificamos si existen ciertos elementos en el DOM
  // como una forma de determinar si el usuario tiene permisos

  if (permiso === "administracion.roles_permisos.crear") {
    return $("#btn-nuevo-rol").length > 0 || $("#btn-nuevo-permiso").length > 0;
  } else if (permiso === "administracion.roles_permisos.editar") {
    return (
      $("#btn-editar-desde-detalle-rol").length > 0 ||
      $("#btn-editar-desde-detalle-permiso").length > 0
    );
  }

  return false;
}

/**
 * Función para manejar errores de AJAX
 * @param {object} xhr - Objeto XMLHttpRequest
 * @param {string} status - Estado de la solicitud
 * @param {string} error - Mensaje de error
 */
function manejarErrorAjax(xhr, status, error) {
  console.error("Error en la solicitud AJAX:", status);
  console.error("Excepción:", error);
  console.error("Respuesta del servidor:", xhr.responseText);

  let mensaje = "Ha ocurrido un error al procesar la solicitud";

  try {
    const respuesta = JSON.parse(xhr.responseText);
    if (respuesta && respuesta.message) {
      mensaje = respuesta.message;
    }
  } catch (e) {
    // Si no se puede parsear la respuesta, usar el mensaje genérico
  }

  mostrarToast("error", "Error", mensaje);
}

/**
 * Función para inicializar tooltips de Bootstrap
 */
function inicializarTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

// Inicializar tooltips cuando el DOM esté listo
$(document).ready(function () {
  inicializarTooltips();
});

// Reinicializar tooltips después de cada redibujado de DataTables
$(document).on("draw.dt", function () {
  inicializarTooltips();
});
