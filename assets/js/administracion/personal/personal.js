/**
 * Módulo de Gestión de Personal
 * SIGESMANCOR
 */

// Variables globales
let personalTable;
let personalSeleccionado = null;
let modoEdicion = false;
let usuarioActual = null;
let imageUploader;

// Declaración de variables globales
const $ = jQuery;
const bootstrap = window.bootstrap; // Asegúrate de que Bootstrap esté disponible globalmente

// Elementos DOM - AHORA DESPUÉS DE DECLARAR $
const $tablaPersonal = $("#personal-table");
const $modalPersonal = $("#modal-personal");
const $modalDetallePersonal = $("#modal-detalle-personal");
const $modalConfirmarEliminar = $("#modal-confirmar-eliminar");
const $formPersonal = $("#form-personal");
const $panelDetalle = $("#personal-detalle");

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
  if ($("#btn-nuevo-personal").length > 0) {
    usuarioActual.permisos.push("administracion.personal.crear");
  }

  if ($("#btn-editar-desde-detalle").length > 0) {
    usuarioActual.permisos.push("administracion.personal.editar");
  }

  // Por defecto, permitir eliminar
  usuarioActual.permisos.push("administracion.personal.eliminar");
}

/**
 * Inicializa la tabla de personal con DataTables
 */
function inicializarDataTable() {
  personalTable = $tablaPersonal.DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/administracion/personal/listar.php"),
      type: "POST",
      data: function (d) {
        // Añadir filtros
        d.filtros = {
          area: $("#filtro-area").val(),
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
        data: "imagen",
        render: function (data, type, row) {
          let imgSrc = data
            ? data
            : getUrl("assets/img/administracion/personal/default.png");
          return `<img src="${imgSrc}" alt="Foto" class="personal-imagen-tabla" data-personal-id="${row.id}">`;
        },
        orderable: false,
      },
      { data: "nombre" },
      { data: "dni" },
      { data: "area" },
      {
        data: "fecha_ingreso",
        render: function (data, type, row) {
          if (type === "display" && data) {
            // Formatear fecha para mostrar
            const fecha = new Date(data);
            return fecha.toLocaleDateString("es-ES");
          }
          return data;
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
          html += `<button type="button" class="btn-accion btn-ver-personal" data-id="${row.id}" title="Ver detalles">
                                <i class="bi bi-eye"></i>
                            </button>`;

          // Botón Editar (si tiene permiso)
          html += `<button type="button" class="btn-accion btn-editar-personal" data-id="${row.id}" title="Editar personal">
                                <i class="bi bi-pencil"></i>
                            </button>`;

          // Botón Eliminar (si tiene permiso)
          html += `<button type="button" class="btn-accion btn-eliminar-personal" data-id="${row.id}" title="Eliminar personal">
                                <i class="bi bi-trash"></i>
                            </button>`;

          return html;
        },
        orderable: false,
      },
    ],
    order: [[1, "asc"]], // Ordenar por nombre
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
      $(".btn-ver-personal")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          verPersonal(id);
        });

      $(".btn-editar-personal")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          editarPersonal(id);
        });

      $(".btn-eliminar-personal")
        .off("click")
        .on("click", function () {
          let id = $(this).data("id");
          confirmarEliminarPersonal(id);
        });

      $(".personal-imagen-tabla")
        .off("click")
        .on("click", function () {
          if (window.imageViewer) {
            const imgSrc = $(this).attr("src");
            const personalNombre = $(this).attr("alt") || "Personal";
            window.imageViewer.show(imgSrc, personalNombre);
            return false; // Detener la propagación del evento
          }
        });

      // Selección de fila
      $tablaPersonal
        .find("tbody tr")
        .off("click")
        .on("click", function () {
          if (!$(this).hasClass("selected")) {
            personalTable.$("tr.selected").removeClass("selected");
            $(this).addClass("selected");

            // Obtener el ID del personal de la fila seleccionada
            let id = personalTable.row(this).data().id;
            cargarDetallePersonal(id);
          }
        });
    },
  });
}

/**
 * Inicializa todos los eventos del módulo
 */
function inicializarEventos() {
  // Botón Nuevo Personal
  $("#btn-nuevo-personal").on("click", function () {
    nuevoPersonal();
  });

  // Botón Guardar Personal
  $("#btn-guardar-personal").on("click", function () {
    guardarPersonal();
  });

  // Botón Confirmar Eliminar
  $("#btn-confirmar-eliminar").on("click", function () {
    eliminarPersonal();
  });

  // Botón Editar desde Detalle
  $("#btn-editar-desde-detalle").on("click", function () {
    if (personalSeleccionado) {
      editarPersonal(personalSeleccionado);
    }
  });

  // Botón Ver Imagen
  $("#btn-ver-imagen").on("click", function () {
    if (personalSeleccionado) {
      let imgSrc = $("#detalle-imagen").attr("src");
      let nombre = $("#detalle-nombre-completo").text();

      // Usar el componente image-viewer
      if (window.imageViewer) {
        window.imageViewer.show(imgSrc, nombre);
      }
    }
  });

  // Botón Generar Reporte
  $("#btn-generar-reporte").on("click", function () {
    if (personalSeleccionado) {
      window.open(
        getUrl(
          "api/administracion/personal/generar_reporte.php?id=" +
            personalSeleccionado
        ),
        "_blank"
      );
    } else {
      mostrarToast("error", "Error", "No se ha seleccionado una persona");
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
  if (document.getElementById("container-personal-imagen")) {
    // Inicializar el componente
    try {
      imageUploader = new ImageUpload("container-personal-imagen", {
        maxSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "imagen",
        defaultImage: getUrl("assets/img/administracion/personal/default.png"),
        existingImage: "",
        uploadPath: "assets/img/personal/",
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
  $formPersonal.validate({
    rules: {
      nombre: {
        required: true,
        minlength: 3,
        maxlength: 100,
      },
      dni: {
        maxlength: 20,
      },
      telefono: {
        maxlength: 20,
      },
      area: {
        maxlength: 50,
      },
      direccion: {
        maxlength: 255,
      },
      fecha_ingreso: {
        required: true,
        date: true,
      },
      fecha_baja: {
        date: true,
      },
    },
    messages: {
      nombre: {
        required: "El nombre es obligatorio",
        minlength: "El nombre debe tener al menos 3 caracteres",
        maxlength: "El nombre no puede tener más de 100 caracteres",
      },
      fecha_ingreso: {
        required: "La fecha de ingreso es obligatoria",
        date: "Ingrese una fecha válida",
      },
      fecha_baja: {
        date: "Ingrese una fecha válida",
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
      guardarPersonal();
    },
  });
}

/**
 * Prepara el formulario para un nuevo personal
 */
function nuevoPersonal() {
  modoEdicion = false;
  resetearFormulario();

  // Establecer fecha de ingreso por defecto (hoy)
  const hoy = new Date().toISOString().split("T")[0];
  $("#personal-fecha-ingreso").val(hoy);

  // Cambiar título del modal
  $("#modal-personal-titulo").text("Nuevo Personal");

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById("modal-personal"));
  modal.show();
}

/**
 * Prepara el formulario para editar un personal existente
 * @param {number} id - ID del personal a editar
 */
function editarPersonal(id) {
  modoEdicion = true;
  resetearFormulario();

  // Cambiar título del modal
  $("#modal-personal-titulo").text("Editar Personal");

  // Cargar datos del personal
  $.ajax({
    url: getUrl("api/administracion/personal/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const persona = response.data;

        // Llenar el formulario con los datos del personal
        $("#personal-id").val(persona.id);
        $("#personal-nombre").val(persona.nombre);
        $("#personal-dni").val(persona.dni);
        $("#personal-telefono").val(persona.telefono);
        $("#personal-area").val(persona.area);
        $("#personal-direccion").val(persona.direccion);
        $("#personal-fecha-ingreso").val(persona.fecha_ingreso);
        $("#personal-fecha-baja").val(persona.fecha_baja);
        $("#personal-estado").val(persona.esta_activo);

        // Inicializar componente de carga de imágenes con la imagen existente
        if (imageUploader) {
          try {
            // Reiniciar el componente con la imagen existente
            imageUploader = new ImageUpload("container-personal-imagen", {
              maxSize: 2 * 1024 * 1024,
              acceptedTypes: [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
              ],
              inputName: "imagen",
              defaultImage: getUrl(
                "assets/img/administracion/personal/default.png"
              ),
              existingImage: persona.imagen || "",
              uploadPath: "assets/img/personal/",
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
          document.getElementById("modal-personal")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los datos del personal"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al cargar los datos del personal");
      console.error(error);
    },
  });
}

/**
 * Muestra el modal de detalles de un personal
 * @param {number} id - ID del personal a ver
 */
function verPersonal(id) {
  $.ajax({
    url: getUrl("api/administracion/personal/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const persona = response.data;

        // Llenar los detalles del personal
        $("#detalle-nombre-completo").text(persona.nombre);
        $("#detalle-dni").text(persona.dni || "-");
        $("#detalle-telefono").text(persona.telefono || "-");
        $("#detalle-area").text(persona.area || "-");
        $("#detalle-direccion").text(persona.direccion || "-");
        $("#detalle-fecha-ingreso").text(formatearFecha(persona.fecha_ingreso));
        $("#detalle-fecha-baja").text(
          persona.fecha_baja ? formatearFecha(persona.fecha_baja) : "-"
        );
        $("#detalle-creado-en").text(formatearFecha(persona.creado_en));
        $("#detalle-creado-por").text(persona.creado_por_nombre || "-");

        // Estado
        if (persona.esta_activo == 1) {
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

        // Imagen
        if (persona.imagen) {
          $("#detalle-imagen").attr("src", persona.imagen);
        } else {
          $("#detalle-imagen").attr(
            "src",
            getUrl("assets/img/administracion/personal/default.png")
          );
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-detalle-personal")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al cargar los detalles del personal"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del personal"
      );
      console.error(error);
    },
  });
}

/**
 * Guarda un personal (nuevo o existente)
 */
function guardarPersonal() {
  // Validar el formulario
  if (!$formPersonal.valid()) {
    return;
  }

  // Preparar datos del formulario
  let formData = new FormData($formPersonal[0]);

  // Añadir modo (nuevo o edición)
  formData.append("modo", modoEdicion ? "editar" : "nuevo");

  // Mostrar indicador de carga
  $("#btn-guardar-personal")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud
  $.ajax({
    url: getUrl("api/administracion/personal/guardar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-personal")
        ).hide();

        // Mostrar mensaje de éxito
        mostrarToast(
          "success",
          "Éxito",
          response.message || "Personal guardado correctamente"
        );

        // Recargar tabla
        personalTable.ajax.reload();

        // Si estamos editando el personal seleccionado, actualizar panel de detalles
        if (modoEdicion && personalSeleccionado == $("#personal-id").val()) {
          cargarDetallePersonal(personalSeleccionado);
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar el personal"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast("error", "Error", "Error al guardar el personal");
      console.error(error);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-guardar-personal").prop("disabled", false).html("Guardar");
    },
  });
}

/**
 * Muestra el modal de confirmación para eliminar un personal
 * @param {number} id - ID del personal a eliminar
 */
function confirmarEliminarPersonal(id) {
  personalSeleccionado = id;
  const modal = new bootstrap.Modal(
    document.getElementById("modal-confirmar-eliminar")
  );
  modal.show();
}

/**
 * Elimina un personal
 */
function eliminarPersonal() {
  if (!personalSeleccionado) {
    return;
  }

  // Mostrar indicador de carga
  $("#btn-confirmar-eliminar")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...'
    );

  $.ajax({
    url: getUrl("api/administracion/personal/eliminar.php"),
    type: "POST",
    data: { id: personalSeleccionado },
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
          response.message || "Personal eliminado correctamente"
        );

        // Recargar tabla
        personalTable.ajax.reload();

        // Si el personal eliminado es el seleccionado, limpiar panel de detalles
        if (personalSeleccionado == personalSeleccionado) {
          limpiarPanelDetalle();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al eliminar el personal"
        );
      }
    },
    error: function (xhr, status, error) {
      bootstrap.Modal.getInstance(
        document.getElementById("modal-confirmar-eliminar")
      ).hide();
      mostrarToast("error", "Error", "Error al eliminar el personal");
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
 * Carga los detalles de un personal en el panel lateral
 * @param {number} id - ID del personal
 */
function cargarDetallePersonal(id) {
  personalSeleccionado = id;

  // Añadir clase de carga
  $panelDetalle.removeClass("loaded").addClass("loading");

  $.ajax({
    url: getUrl("api/administracion/personal/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const persona = response.data;

        // Construir HTML para el panel de detalles
        let html = `
                    <div class="detail-header d-flex align-items-center">
                        <div class="detail-header-image me-3">
                            <img src="${
                              persona.imagen
                                ? persona.imagen
                                : getUrl(
                                    "assets/img/administracion/personal/default.png"
                                  )
                            }" 
                                alt="Foto" class="detail-header-img" id="detalle-lateral-img">
                        </div>
                        <div>
                            <h2 class="detail-title">${persona.nombre}</h2>
                            <p class="detail-subtitle">${
                              persona.area || "Sin área asignada"
                            }</p>
                        </div>
                    </div>
                    <div class="detail-content">
                        <!--<div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-person-circle"></i> Información Básica
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">DNI:</span>
                                <span class="detail-value">${
                                  persona.dni || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Teléfono:</span>
                                <span class="detail-value">${
                                  persona.telefono || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="badge ${
                                      persona.esta_activo == 1
                                        ? "bg-success"
                                        : "bg-danger"
                                    }">
                                        ${
                                          persona.esta_activo == 1
                                            ? "Activo"
                                            : "Inactivo"
                                        }
                                    </span>
                                </span>
                            </div>
                        </div>-->
                        
                        <div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-calendar-date"></i> Fechas
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha Ingreso:</span>
                                <span class="detail-value">${
                                  formatearFecha(persona.fecha_ingreso) || "-"
                                }</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fecha Baja:</span>
                                <span class="detail-value">${
                                  persona.fecha_baja
                                    ? formatearFecha(persona.fecha_baja)
                                    : "-"
                                }</span>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <div class="detail-section-title">
                                <i class="bi bi-geo-alt"></i> Ubicación
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Dirección:</span>
                                <span class="detail-value">${
                                  persona.direccion || "-"
                                }</span>
                            </div>
                        </div>
                        
                        <div class="detail-actions">`;

        // Botones de acción
        if (tienePermiso("administracion.personal.editar")) {
          html += `
                            <button type="button" class="btn btn-sm btn-primary" id="btn-editar-lateral">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </button>`;
        }

        if (tienePermiso("administracion.personal.eliminar")) {
          html += `
                            <button type="button" class="btn btn-sm btn-danger" id="btn-eliminar-lateral">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>`;
        }

        html += `
                            <button type="button" class="btn btn-sm btn-info" id="btn-ver-completo-lateral">
                                <i class="bi bi-eye me-1"></i> Ver Completo
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" id="btn-generar-reporte-lateral">
                                <i class="bi bi-file-pdf me-1"></i> Generar Informe
                            </button>
                        </div>
                    </div>
                `;

        // Actualizar contenido
        $panelDetalle.html(html);

        // Añadir eventos a los botones
        $("#btn-editar-lateral").on("click", function () {
          editarPersonal(personalSeleccionado);
        });

        $("#btn-eliminar-lateral").on("click", function () {
          confirmarEliminarPersonal(personalSeleccionado);
        });

        $("#btn-ver-completo-lateral").on("click", function () {
          verPersonal(personalSeleccionado);
        });

        $("#btn-generar-reporte-lateral").on("click", function () {
          if (personalSeleccionado) {
            window.open(
              getUrl(
                "api/administracion/personal/generar_reporte.php?id=" +
                  personalSeleccionado
              ),
              "_blank"
            );
          }
        });

        $("#detalle-lateral-img").on("click", function () {
          let imgSrc = $(this).attr("src");
          let nombre = persona.nombre;

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
          response.message || "Error al cargar los detalles del personal"
        );
      }
    },
    error: function (xhr, status, error) {
      limpiarPanelDetalle();
      mostrarToast(
        "error",
        "Error",
        "Error al cargar los detalles del personal"
      );
      console.error(error);
    },
  });
}

/**
 * Limpia el panel de detalles
 */
function limpiarPanelDetalle() {
  personalSeleccionado = null;

  // Restaurar contenido original
  $panelDetalle.html(`
        <div class="detail-header">
            <h2 class="detail-title">Detalles del Personal</h2>
            <p class="detail-subtitle">Seleccione un personal para ver información</p>
        </div>
        <div class="detail-content">
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un personal para ver sus detalles
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
  $formPersonal[0].reset();
  $formPersonal.validate().resetForm();

  // Limpiar ID
  $("#personal-id").val("");

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
  personalTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros aplicados");
}

/**
 * Limpia los filtros
 */
function limpiarFiltros() {
  // Limpiar selectores
  $("#filtro-area").val("");
  $("#filtro-estado").val("");

  // Limpiar localStorage
  localStorage.removeItem("personal_filtros");

  // Recargar tabla
  personalTable.ajax.reload();

  // Mostrar mensaje
  mostrarToast("info", "Información", "Filtros eliminados");
}
/**
 * Guarda los filtros en localStorage
 */
function guardarFiltros() {
  const filtros = {
    area: $("#filtro-area").val(),
    estado: $("#filtro-estado").val(),
  };

  localStorage.setItem("personal_filtros", JSON.stringify(filtros));
}

/**
 * Carga los filtros guardados en localStorage
 */
function cargarFiltrosGuardados() {
  const filtrosGuardados = localStorage.getItem("personal_filtros");

  if (filtrosGuardados) {
    const filtros = JSON.parse(filtrosGuardados);

    $("#filtro-area").val(filtros.area);
    $("#filtro-estado").val(filtros.estado);

    // Aplicar filtros
    personalTable.ajax.reload();
  }
}

/**
 * Verifica si el usuario tiene un permiso específico
 * @param {string} permiso - Nombre del permiso
 * @return {boolean}
 */
function tienePermiso(permiso) {
  // Verificación basada en elementos del DOM
  if (permiso === "administracion.personal.crear") {
    return $("#btn-nuevo-personal").length > 0;
  } else if (permiso === "administracion.personal.editar") {
    return $("#btn-editar-desde-detalle").length > 0;
  } else if (permiso === "administracion.personal.eliminar") {
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
