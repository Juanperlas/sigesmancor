/**
 * Módulo de Componentes - JavaScript
 * SIGESMANCOR
 */

// Variables globales
let componentesTable;
let componenteSeleccionado = null;
let componenteActual = null;
let filtrosAplicados = {};
let imageUploader;

// Constantes
const UNIDADES_OROMETRO = {
  horas: "hrs",
  kilometros: "km",
};

// Declaración de variables globales
const $ = jQuery;
const bootstrap = window.bootstrap; // Asegúrate de que Bootstrap esté disponible globalmente
// Verificar si las variables ya están definidas en el objeto window
// Si no están definidas, no intentamos redeclararlas
// Esto evita tanto el error de "no disponible" como el de "ya declarado"
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
if (!window.ImageUpload)
  window.ImageUpload = () => {
    console.log("Componente de carga de imágenes no disponible");
  };
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

// Inicialización cuando el DOM está listo
$(document).ready(function () {
  // Inicializar DataTable
  inicializarTabla();

  // Inicializar el cargador de imágenes
  initImageUploader();

  // Inicializar validación del formulario
  inicializarValidacionFormulario();

  // Eventos para botones y controles
  $("#btn-nuevo-componente").on("click", mostrarModalNuevoComponente);
  $("#btn-guardar-componente").on("click", guardarComponente);
  $("#btn-confirmar-eliminar").on("click", eliminarComponenteConfirmado);
  $("#btn-aplicar-filtros").on("click", aplicarFiltros);
  $("#btn-limpiar-filtros").on("click", limpiarFiltros);
  $("#btn-ver-imagen").on("click", ampliarImagenDetalle);
  $("#btn-editar-desde-detalle").on("click", editarDesdeDetalle);

  // Eventos para campos de formulario
  $("#componente-tipo-orometro").on("change", actualizarUnidadesOrometro);
  $("#componente-orometro, #componente-mantenimiento").on(
    "input",
    calcularProximoOrometro
  );

  // Inicializar tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();
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
 * Inicializa la tabla de componentes con DataTables
 */
function inicializarTabla() {
  componentesTable = $("#componentes-table").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/equipos/componentes/listar.php"),
      type: "POST",
      data: (d) => {
        // Si se selecciona "Todos", usar un valor grande pero finito en lugar de -1
        if (d.length == -1) {
          d.length = 10000; // Un número grande pero manejable
        }

        // Agregar filtros activos
        return {
          ...d,
          ...filtrosAplicados,
        };
      },
      error: (xhr, error, thrown) => {
        console.error(
          "Error en la solicitud AJAX de DataTable:",
          error,
          thrown
        );
        if (window.showErrorToast) {
          window.showErrorToast(
            "Error al cargar los datos de la tabla: " + thrown
          );
        }
      },
    },
    columns: [
      {
        data: "imagen",
        orderable: false,
        className: "text-center",
        render: function (data, type, row) {
          const imgUrl = data
            ? data
            : getUrl("assets/img/equipos/componentes/default.png");
          return `<img src="${imgUrl}" alt="Imagen" class="componente-imagen-tabla" data-image-viewer="true" data-id="${row.id}">`;
        },
      },
      {
        data: "codigo",
        className: "align-middle",
      },
      {
        data: "nombre",
        className: "align-middle",
      },
      {
        data: "equipo_nombre",
        className: "align-middle",
      },
      {
        data: "estado",
        className: "align-middle text-center",
        render: function (data, type) {
          // Para ordenamiento, devolver el valor sin formato
          if (type === "sort" || type === "type") {
            return data;
          }
          return `<span class="estado-badge estado-${data.toLowerCase()}">${capitalizarPrimeraLetra(
            data
          )}</span>`;
        },
      },
      {
        data: "anterior_orometro",
        className: "align-middle text-center",
        render: function (data, type, row) {
          // Para ordenamiento, devolver el valor numérico
          if (type === "sort" || type === "type") {
            return Number.parseFloat(data || 0);
          }
          const unidad = getUnidadOrometro(row.tipo_orometro);
          return `<span class="orometro-valor">${formatearNumero(
            data || 0
          )} ${unidad}</span>`;
        },
      },
      {
        data: "orometro_actual",
        className: "align-middle text-center",
        render: function (data, type, row) {
          // Para ordenamiento, devolver el valor numérico
          if (type === "sort" || type === "type") {
            return Number.parseFloat(data);
          }

          // Calcular el progreso para la barra
          const anterior = Number.parseFloat(row.anterior_orometro || 0);
          const actual = Number.parseFloat(data);
          const proximo = Number.parseFloat(row.proximo_orometro || 0);
          const notificacion = Number.parseFloat(row.notificacion || 0);
          const unidad = getUnidadOrometro(row.tipo_orometro);

          let porcentaje = 0;
          let colorClass = "bg-success";
          let textColorClass = "text-success";

          if (proximo > anterior) {
            // Calcular el porcentaje de avance
            const total = proximo - anterior;
            const avance = actual - anterior;
            porcentaje = Math.min(Math.max((avance / total) * 100, 0), 100);

            // Determinar el color según la proximidad al próximo mantenimiento
            const restante = proximo - actual;

            if (notificacion > 0) {
              if (restante <= notificacion / 2) {
                colorClass = "bg-danger";
                textColorClass = "text-danger";
              } else if (restante <= notificacion) {
                colorClass = "bg-warning";
                textColorClass = "text-warning";
              }
            }
          }

          // Construir la barra de progreso
          const progressBar = `
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar ${colorClass}" role="progressbar" 
                                style="width: ${porcentaje}%" 
                                aria-valuenow="${porcentaje}" 
                                aria-valuemin="0" 
                                aria-valuemax="100"></div>
                        </div>
                    `;

          return `
                        <div>
                            <span class="orometro-valor ${textColorClass} fw-bold">${formatearNumero(
            data
          )} ${unidad}</span>
                            ${progressBar}
                        </div>
                    `;
        },
      },
      {
        data: "proximo_orometro",
        className: "align-middle text-center",
        render: function (data, type, row) {
          // Para ordenamiento, devolver el valor numérico
          if (type === "sort" || type === "type") {
            return Number.parseFloat(data || 0);
          }
          const unidad = getUnidadOrometro(row.tipo_orometro);
          return data
            ? `<span class="orometro-valor text-danger fw-bold">${formatearNumero(
                data
              )} ${unidad}</span>`
            : "-";
        },
      },
      {
        data: null,
        orderable: false,
        className: "text-center align-middle",
        render: function (data) {
          let acciones = '<div class="btn-group btn-group-sm">';
          acciones += `<button type="button" class="btn-accion btn-ver-componente" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

          if (tienePermiso("componentes.editar")) {
            acciones += `<button type="button" class="btn-accion btn-editar-componente" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
          }

          if (tienePermiso("componentes.eliminar")) {
            acciones += `<button type="button" class="btn-accion btn-eliminar-componente" data-id="${data.id}" title="Eliminar"><i class="bi bi-trash"></i></button>`;
          }

          acciones += "</div>";
          return acciones;
        },
      },
    ],
    dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
    buttons: [
      {
        extend: "copy",
        text: '<i class="bi bi-clipboard"></i> Copiar',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7],
        },
      },
      {
        extend: "excel",
        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7],
        },
      },
      {
        extend: "pdf",
        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
        className: "btn btn-sm btn-primary",
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7], // Excluye la columna de imagen y acciones
        },
        customize: function (doc) {
          // Configuración básica del documento
          doc.pageOrientation = "landscape";
          doc.defaultStyle = {
            fontSize: 8,
            color: "#333333",
          };

          // Definir colores pastel con sus versiones oscuras para texto
          const colores = {
            // Colores pastel para fondos
            azulPastel: "#D4E6F1",
            verdePastel: "#D5F5E3",
            naranjaPastel: "#FAE5D3",
            rojoPastel: "#F5B7B1",
            grisPastel: "#EAECEE",
            celestePastel: "#EBF5FB",

            // Colores oscuros para texto
            azulOscuro: "#1A5276",
            verdeOscuro: "#186A3B",
            naranjaOscuro: "#BA4A00",
            rojoOscuro: "#922B21",
            grisOscuro: "#424949",
            celesteOscuro: "#2874A6",

            // Color primario azul
            azulPrimario: "#0055A4",
          };

          // Logo en base64
          const logoBase64 =
            "mi_imagen_base64";

          // Encabezado con logos a ambos lados
          doc.content.unshift(
            {
              columns: [
                {
                  // Logo izquierdo
                  image: logoBase64,
                  width: 50,
                  margin: [5, 5, 0, 5],
                },
                {
                  // Título central
                  text: "FORMATO REGISTRO DE COMPONENTES",
                  style: "header",
                  alignment: "center",
                  margin: [0, 15, 0, 0],
                },
                {
                  // Logo derecho
                  image: logoBase64,
                  width: 50,
                  alignment: "right",
                  margin: [0, 5, 5, 5],
                },
              ],
              columnGap: 10,
            },
            {
              columns: [
                {
                  text: "Empresa: CORDIAL SAC",
                  style: "empresa",
                  margin: [5, 0, 0, 5],
                },
                {
                  text: `Fecha: ${new Date().toLocaleDateString("es-ES")}`,
                  style: "empresa",
                  alignment: "right",
                  margin: [0, 0, 5, 5],
                },
              ],
            }
          );

          // Encontrar la tabla dinámicamente
          let tableIndex = doc.content.findIndex((item) => item.table);
          if (tableIndex !== -1) {
            // Configurar anchos de columnas proporcionales
            doc.content[tableIndex].table.widths = [
              40, // Código
              "*", // Nombre
              120, // Equipo
              80, // Estado
              60, // Anterior
              60, // Actual
              60, // Próximo
            ];

            // Estilizar la tabla
            doc.content[tableIndex].table.body.forEach((row, i) => {
              if (i === 0) {
                // Encabezado
                row.forEach((cell) => {
                  cell.fillColor = colores.azulPastel;
                  cell.color = colores.azulOscuro;
                  cell.fontSize = 9;
                  cell.bold = true;
                  cell.alignment = "center";
                  cell.margin = [2, 3, 2, 3];
                });
              } else {
                // Filas de datos
                const estado = row[3].text.toString().toLowerCase(); // Columna 'estado' (índice 3 para componentes)

                // Determinar color según estado (usando colores pastel)
                let colorFondo = colores.azulPastel;
                let colorTexto = colores.azulOscuro;

                if (estado.includes("activo")) {
                  colorFondo = colores.verdePastel;
                  colorTexto = colores.verdeOscuro;
                } else if (estado.includes("mantenimiento")) {
                  colorFondo = colores.naranjaPastel;
                  colorTexto = colores.naranjaOscuro;
                } else if (estado.includes("averiado")) {
                  colorFondo = colores.rojoPastel;
                  colorTexto = colores.rojoOscuro;
                } else if (estado.includes("descanso")) {
                  colorFondo = colores.celestePastel;
                  colorTexto = colores.celesteOscuro;
                } else if (estado.includes("vendido")) {
                  colorFondo = colores.grisPastel;
                  colorTexto = colores.grisOscuro;
                }

                // Aplicar estilos a cada celda
                row.forEach((cell, j) => {
                  cell.fontSize = 8;
                  cell.margin = [2, 2, 2, 2];

                  // Alineación según tipo de dato
                  if (j === 1) {
                    // Nombre
                    cell.alignment = "left";
                  } else if (j === 2) {
                    // Equipo
                    cell.alignment = "left";
                  } else if (j === 3) {
                    // Estado
                    cell.fillColor = colorFondo;
                    cell.color = colorTexto;
                    cell.alignment = "center";
                    cell.bold = true;
                  } else if (j === 5) {
                    // Orómetro Actual
                    cell.bold = true; // Ponemos en negrita el orómetro actual
                    cell.alignment = "center";
                  } else if (j >= 4) {
                    // Datos numéricos
                    cell.alignment = "center";
                  } else {
                    cell.alignment = "center";
                  }
                });

                // Añadir líneas zebra para mejor legibilidad
                if (i % 2 === 0) {
                  row.forEach((cell, j) => {
                    if (j !== 3 && !cell.fillColor) {
                      // No sobrescribir el color de estado
                      cell.fillColor = "#f9f9f9";
                    }
                  });
                }
              }
            });
          }

          // Añadir pie de página simplificado
          doc.footer = function (currentPage, pageCount) {
            return {
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 8,
              margin: [0, 0, 0, 0],
            };
          };

          // Añadir texto de firmas
          doc.content.push({
            columns: [
              {
                text: "JEFE DE MANTENIMIENTO",
                alignment: "center",
                fontSize: 8,
                margin: [0, 60, 0, 0],
              },
            ],
          });

          // Definir estilos
          doc.styles = {
            header: {
              fontSize: 14,
              bold: true,
              color: colores.azulPrimario,
            },
            empresa: {
              fontSize: 9,
              color: colores.azulOscuro,
              bold: true,
            },
          };

          // Metadatos del PDF
          doc.info = {
            title: "Reporte de Componentes",
            author: "CORDIAL SAC",
            subject: "Listado de Componentes",
          };
        },
        filename:
          "Reporte_Componentes_" + new Date().toISOString().split("T")[0],
        orientation: "landscape",
      },
      {
        extend: "print",
        text: '<i class="bi bi-printer"></i> Imprimir',
        className: "btn btn-sm",
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7],
        },
      },
    ],
    language: {
      url: getUrl("assets/plugins/datatables/js/es-ES.json"),
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      zeroRecords: "No se encontraron registros",
      emptyTable: "No hay datos disponibles en la tabla",
    },
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    pageLength: 25,
    initComplete: function () {
      // Eventos para botones de acción
      $("#componentes-table").on("click", ".btn-ver-componente", function () {
        const id = $(this).data("id");
        verDetallesComponente(id);
      });

      $("#componentes-table").on(
        "click",
        ".btn-editar-componente",
        function () {
          const id = $(this).data("id");
          editarComponente(id);
        }
      );

      $("#componentes-table").on(
        "click",
        ".btn-eliminar-componente",
        function () {
          const id = $(this).data("id");
          confirmarEliminarComponente(id);
        }
      );

      // Evento para imágenes en la tabla
      $("#componentes-table").on(
        "click",
        ".componente-imagen-tabla",
        function () {
          // En lugar de llamar a verDetallesComponente, usar el visor de imágenes
          if (window.imageViewer) {
            const imgSrc = $(this).attr("src");
            const componenteNombre = $(this).attr("alt") || "Componente";
            window.imageViewer.show(imgSrc, componenteNombre);
            return false; // Detener la propagación del evento
          }
        }
      );

      // Evento para seleccionar fila
      $("#componentes-table tbody").on("click", "tr", function () {
        const data = componentesTable.row(this).data();
        if (data) {
          // Remover selección anterior
          $("#componentes-table tbody tr").removeClass("selected");
          // Agregar selección a la fila actual
          $(this).addClass("selected");

          // Añadir animación al panel de detalles
          $("#componente-detalle").removeClass("loaded").addClass("loading");

          // Cargar detalles en el panel lateral con un pequeño retraso para la animación
          setTimeout(() => {
            cargarDetallesComponente(data.id);
            // Quitar clase de carga y añadir clase de cargado para la animación
            $("#componente-detalle").removeClass("loading").addClass("loaded");
          }, 300);
        }
      });
    },
    // Manejar el error de "Todos" registros
    drawCallback: () => {
      if (window.hideLoading) {
        window.hideLoading();
      }
    },
    preDrawCallback: () => {
      if (window.showLoading) {
        window.showLoading();
      }
    },
  });
}

/**
 * Inicializa la validación del formulario de componentes
 */
function inicializarValidacionFormulario() {
  $("#form-componente").validate({
    rules: {
      codigo: {
        required: true,
        minlength: 2,
        maxlength: 20,
      },
      nombre: {
        required: true,
        minlength: 3,
        maxlength: 100,
      },
      equipo_id: {
        required: true,
      },
      estado: {
        required: true,
      },
      tipo_orometro: {
        required: true,
      },
      orometro_actual: {
        required: true,
        number: true,
        min: 0,
      },
      mantenimiento: {
        number: true,
        min: 0,
      },
      notificacion: {
        number: true,
        min: 0,
      },
      limite: {
        number: true,
        min: 0,
      },
    },
    messages: {
      codigo: {
        required: "El código es obligatorio",
        minlength: "El código debe tener al menos 2 caracteres",
        maxlength: "El código no puede tener más de 20 caracteres",
      },
      nombre: {
        required: "El nombre es obligatorio",
        minlength: "El nombre debe tener al menos 3 caracteres",
        maxlength: "El nombre no puede tener más de 100 caracteres",
      },
      equipo_id: {
        required: "Debe seleccionar un equipo",
      },
      estado: {
        required: "Debe seleccionar un estado",
      },
      tipo_orometro: {
        required: "Debe seleccionar un tipo de orómetro",
      },
      orometro_actual: {
        required: "El orómetro actual es obligatorio",
        number: "Debe ingresar un número válido",
        min: "El valor debe ser mayor o igual a 0",
      },
      mantenimiento: {
        number: "Debe ingresar un número válido",
        min: "El valor debe ser mayor o igual a 0",
      },
      notificacion: {
        number: "Debe ingresar un número válido",
        min: "El valor debe ser mayor o igual a 0",
      },
      limite: {
        number: "Debe ingresar un número válido",
        min: "El valor debe ser mayor o igual a 0",
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
  });
}

/**
 * Inicializa el componente de carga de imágenes
 */
function initImageUploader() {
  // Verificar si el contenedor existe
  if (document.getElementById("container-componente-imagen")) {
    // Inicializar el componente
    try {
      imageUploader = new ImageUpload("container-componente-imagen", {
        maxSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "imagen",
        defaultImage: getUrl("assets/img/equipos/componentes/default.png"),
        existingImage: "",
        uploadPath: "assets/img/equipos/componentes/",
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
 * Muestra el modal para crear un nuevo componente
 */
function mostrarModalNuevoComponente() {
  // Mostrar toast de información
  if (window.showInfoToast) {
    window.showInfoToast("Preparando formulario para nuevo componente...");
  }

  // Limpiar formulario
  $("#form-componente")[0].reset();
  $("#componente-id").val("");

  // Inicializar componente de carga de imágenes con valores por defecto
  if (imageUploader) {
    try {
      // Reiniciar el componente con valores por defecto
      imageUploader = new ImageUpload("container-componente-imagen", {
        maxSize: 2 * 1024 * 1024,
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "imagen",
        defaultImage: getUrl("assets/img/equipos/componentes/default.png"),
        existingImage: "",
        uploadPath: "assets/img/equipos/componentes/",
        position: "center", // Posicionar la cámara en el centro
      });
    } catch (e) {
      console.warn("Error al reiniciar el componente de carga de imágenes:", e);
    }
  } else {
    // Inicializar si no existe
    initImageUploader();
  }

  // Actualizar título del modal
  $("#modal-componente-titulo").text("Nuevo Componente");

  // Mostrar modal
  const modal = new bootstrap.Modal(
    document.getElementById("modal-componente")
  );
  modal.show();

  // Establecer valores por defecto
  $("#componente-estado").val("activo");
  $("#componente-tipo-orometro").val("horas").trigger("change");
  $("#componente-orometro").val("0");
  $("#componente-anterior-orometro").val("0");

  // Calcular próximo orómetro
  calcularProximoOrometro();
}

/**
 * Guarda un componente (nuevo o existente)
 */
function guardarComponente() {
  // Validar formulario
  if (!$("#form-componente").valid()) {
    mostrarToast(
      "warning",
      "Atención",
      "Por favor, complete todos los campos obligatorios"
    );
    return;
  }

  // Actualizar valor del próximo orómetro en el campo oculto
  $("#hidden-proximo-orometro").val($("#componente-proximo-orometro").val());

  // Preparar datos del formulario
  const formData = new FormData($("#form-componente")[0]);

  // Mostrar indicador de carga
  $("#btn-guardar-componente")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...'
    );

  // Enviar solicitud AJAX
  $.ajax({
    url: getUrl("api/equipos/componentes/guardar.php"),
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-componente")
        ).hide();

        // Actualizar tabla
        componentesTable.ajax.reload();

        // Mostrar mensaje de éxito
        mostrarToast("success", "Éxito", response.message);
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar el componente"
        );
      }
    },
    error: function (xhr, status, error) {
      mostrarToast(
        "error",
        "Error",
        "Error en la comunicación con el servidor"
      );
      console.error(xhr.responseText);
    },
    complete: function () {
      // Restaurar botón
      $("#btn-guardar-componente").prop("disabled", false).html("Guardar");
    },
  });
}

/**
 * Edita un componente existente
 * @param {number} id - ID del componente a editar
 */
function editarComponente(id) {
  // Verificar que el ID sea válido
  if (!id || isNaN(parseInt(id))) {
    mostrarToast("error", "Error", "ID de componente no válido");
    console.error("ID de componente inválido:", id);
    return;
  }

  // Mostrar indicador de carga
  mostrarToast(
    "info",
    "Cargando",
    "Obteniendo información del componente...",
    1000
  );

  // Obtener datos del componente
  $.ajax({
    url: getUrl("api/equipos/componentes/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    beforeSend: function (xhr) {
      // Verificar los datos que se están enviando
      // console.log("Datos enviados:", { id: id });
    },
    success: function (response) {
      // console.log("Respuesta recibida:", response);

      if (response.success) {
        const componente = response.data;
        componenteActual = componente;

        // Limpiar formulario
        $("#form-componente")[0].reset();

        // Llenar formulario con datos del componente
        $("#componente-id").val(componente.id);
        $("#componente-codigo").val(componente.codigo);
        $("#componente-nombre").val(componente.nombre);
        $("#componente-equipo").val(componente.equipo_id);
        $("#componente-estado").val(componente.estado);
        $("#componente-marca").val(componente.marca);
        $("#componente-modelo").val(componente.modelo);
        $("#componente-serie").val(componente.numero_serie);
        $("#componente-tipo-orometro")
          .val(componente.tipo_orometro)
          .trigger("change");
        $("#componente-mantenimiento").val(componente.mantenimiento);
        $("#componente-notificacion").val(componente.notificacion);
        $("#componente-limite").val(componente.limite);
        $("#componente-anterior-orometro").val(componente.anterior_orometro);
        $("#componente-orometro").val(componente.orometro_actual);
        $("#componente-observaciones").val(componente.observaciones);

        // Inicializar componente de carga de imágenes con la imagen existente
        if (imageUploader) {
          try {
            // Reiniciar el componente con la imagen existente
            imageUploader = new ImageUpload("container-componente-imagen", {
              maxSize: 2 * 1024 * 1024,
              acceptedTypes: [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
              ],
              inputName: "imagen",
              defaultImage: getUrl(
                "assets/img/equipos/componentes/default.png"
              ),
              existingImage: componente.imagen || "",
              uploadPath: "assets/img/equipos/componentes/",
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

        // Calcular próximo orómetro
        calcularProximoOrometro();

        // Actualizar título del modal
        $("#modal-componente-titulo").text("Editar Componente");

        // Mostrar modal
        const modal = new bootstrap.Modal(
          document.getElementById("modal-componente")
        );
        modal.show();
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al obtener el componente"
        );
      }
    },
    error: function (xhr, status, error) {
      console.error("Error en la solicitud AJAX:", status, error);
      console.error("Respuesta del servidor:", xhr.responseText);

      let mensaje = "Error en la comunicación con el servidor";
      try {
        const respuesta = JSON.parse(xhr.responseText);
        if (respuesta && respuesta.message) {
          mensaje = respuesta.message;
        }
      } catch (e) {
        console.error("Error al parsear la respuesta:", e);
      }

      mostrarToast("error", "Error", mensaje);
    },
  });
}

/**
 * Muestra el modal de confirmación para eliminar un componente
 * @param {number} id - ID del componente a eliminar
 */
function confirmarEliminarComponente(id) {
  componenteActual = { id: id };
  const modal = new bootstrap.Modal(
    document.getElementById("modal-confirmar-eliminar")
  );
  modal.show();
}

/**
 * Elimina un componente después de la confirmación
 */
function eliminarComponenteConfirmado() {
  if (!componenteActual || !componenteActual.id) {
    mostrarToast("error", "Error", "No se ha seleccionado ningún componente");
    return;
  }

  // Mostrar indicador de carga
  $("#btn-confirmar-eliminar")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...'
    );

  // Enviar solicitud AJAX
  $.ajax({
    url: getUrl("api/equipos/componentes/eliminar.php"),
    type: "POST",
    data: { id: componenteActual.id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        // Cerrar modal
        bootstrap.Modal.getInstance(
          document.getElementById("modal-confirmar-eliminar")
        ).hide();

        // Actualizar tabla
        componentesTable.ajax.reload();

        // Mostrar mensaje de éxito
        mostrarToast("success", "Éxito", response.message);

        // Si el componente eliminado es el seleccionado, ocultar detalles
        if (
          componenteSeleccionado &&
          componenteSeleccionado.id == componenteActual.id
        ) {
          componenteSeleccionado = null;
          ocultarDetalleComponente();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al eliminar el componente"
        );

        // Si hay mantenimientos asociados, mostrar mensaje específico
        if (response.mantenimientos) {
          mostrarToast(
            "warning",
            "Atención",
            `El componente tiene ${response.mantenimientos} mantenimientos asociados`
          );
        }
      }
    },
    error: function (xhr, status, error) {
      mostrarToast(
        "error",
        "Error",
        "Error en la comunicación con el servidor"
      );
      console.error(xhr.responseText);
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
 * Carga los detalles de un componente en el panel lateral
 * @param {number} id - ID del componente
 */
function cargarDetallesComponente(id) {
  // Mostrar indicador de carga
  $("#componente-detalle .detail-content").html(
    '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
  );
  $("#componente-detalle").addClass("active");

  // Obtener datos del componente
  $.ajax({
    url: getUrl("api/equipos/componentes/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: (response) => {
      if (response.success && response.data) {
        const componente = response.data;
        componenteSeleccionado = componente;
        const unidad = getUnidadOrometro(componente.tipo_orometro);

        // Actualizar título del panel y añadir imagen en el encabezado
        const imagenUrl = componente.imagen
          ? componente.imagen
          : getUrl("assets/img/equipos/componentes/default.png");

        // Actualizar el encabezado con la imagen
        $("#componente-detalle .detail-header").html(`
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="detail-title">${componente.nombre}</h2>
              <p class="detail-subtitle">Código: ${componente.codigo}</p>
            </div>
            <div class="detail-header-image">
              <img src="${imagenUrl}" alt="${componente.nombre}" class="detail-header-img" data-image-viewer="true">
            </div>
          </div>
        `);

        // Calcular tiempo restante para mantenimiento
        let tiempoRestante = "";
        let tiempoRestanteValor = 0;
        let porcentajeAvance = 0;
        let colorClase = "success";

        if (componente.proximo_orometro && componente.orometro_actual) {
          const restante =
            Number.parseFloat(componente.proximo_orometro) -
            Number.parseFloat(componente.orometro_actual);
          tiempoRestanteValor = restante;

          // Calcular porcentaje de avance
          const anterior = Number.parseFloat(componente.anterior_orometro || 0);
          const total =
            Number.parseFloat(componente.proximo_orometro) - anterior;
          const avance =
            Number.parseFloat(componente.orometro_actual) - anterior;
          porcentajeAvance = Math.min(Math.max((avance / total) * 100, 0), 100);

          // Determinar color según proximidad
          if (componente.notificacion) {
            const notificacion = Number.parseFloat(componente.notificacion);
            if (restante <= notificacion / 2) {
              colorClase = "danger";
            } else if (restante <= notificacion) {
              colorClase = "warning";
            }
          }

          if (restante > 0) {
            tiempoRestante = `
              <div class="tiempo-restante-container text-center mb-4">
                <h3 class="tiempo-restante-titulo">Tiempo para próximo mantenimiento</h3>
                <div class="tiempo-restante-valor text-${colorClase}">${formatearNumero(
              restante
            )} ${unidad}</div>
                <div class="progress mt-2" style="height: 8px;">
                  <div class="progress-bar bg-${colorClase}" role="progressbar" 
                    style="width: ${porcentajeAvance}%" 
                    aria-valuenow="${porcentajeAvance}" 
                    aria-valuemin="0" 
                    aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <small>${formatearNumero(
                    anterior
                  )} ${unidad} <br><span class="text-muted">(Lleva ${formatearNumero(
              Number.parseFloat(componente.orometro_actual) - anterior
            )} ${unidad})</span></small>
                  <small>${formatearNumero(
                    componente.proximo_orometro
                  )} ${unidad} <br><span class="text-muted">(Faltan ${formatearNumero(
              restante
            )} ${unidad})</span></small>
                </div>
              </div>
            `;
          } else {
            tiempoRestante = `
              <div class="tiempo-restante-container text-center mb-4">
                <h3 class="tiempo-restante-titulo">¡Mantenimiento requerido!</h3>
                <div class="tiempo-restante-valor text-danger">Excedido por ${formatearNumero(
                  Math.abs(restante)
                )} ${unidad}</div>
                <div class="progress mt-2" style="height: 8px;">
                  <div class="progress-bar bg-danger" role="progressbar" 
                    style="width: 100%" 
                    aria-valuenow="100" 
                    aria-valuemin="0" 
                    aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                  <small>${formatearNumero(
                    anterior
                  )} ${unidad} <br><span class="text-muted">(Inicio)</span></small>
                  <small>${formatearNumero(
                    Number.parseFloat(componente.orometro_actual)
                  )} ${unidad} <br><span class="text-muted">(Lleva ${formatearNumero(
              Number.parseFloat(componente.orometro_actual) - anterior
            )} ${unidad})</span></small>
                  <small>${formatearNumero(
                    componente.proximo_orometro
                  )} ${unidad} <br><span class="text-muted">(Excedido)</span></small>
                </div>
              </div>
            `;
          }
        }

        // Actualizar contenido del panel - SIMPLIFICADO
        $("#componente-detalle .detail-content").html(`
          ${tiempoRestante}
          
          <!-- Información del equipo al que pertenece - destacado -->
          <div class="equipo-pertenencia-container text-center mb-4">
            <h3 class="equipo-titulo">Pertenece al Equipo</h3>
            <div class="equipo-nombre bg-primary text-white p-3 rounded">
              <i class="bi bi-tools me-2"></i>${
                componente.equipo_nombre || "No asignado"
              }
            </div>
          </div>
          
          <!-- Observaciones -->
          <div class="detail-section">
            <div class="detail-section-title">
              <i class="bi bi-chat-left-text"></i> Observaciones
            </div>
            <p class="detail-value">${
              componente.observaciones || "Sin observaciones"
            }</p>
          </div>
          
          <div class="detail-actions">
            <button type="button" class="btn btn-sm btn-primary btn-ver-detalles" data-id="${
              componente.id
            }">
              <i class="bi bi-search"></i> Ver Detalles Completos
            </button>
            ${
              tienePermiso("componentes.editar")
                ? `
                <button type="button" class="btn btn-sm btn-secondary btn-editar" data-id="${componente.id}">
                  <i class="bi bi-pencil"></i> Editar
                </button>
              `
                : ""
            }
          </div>
        `);

        // Inicializar el visor de imágenes para la imagen del encabezado
        $(".detail-header-img").on("click", function () {
          if (window.imageViewer) {
            window.imageViewer.show(
              $(this).attr("src"),
              "Imagen del componente"
            );
          }
        });

        // Eventos para botones en el panel
        $("#componente-detalle .btn-ver-detalles").on("click", function () {
          verDetallesComponente($(this).data("id"));
        });

        $("#componente-detalle .btn-editar").on("click", function () {
          editarComponente($(this).data("id"));
        });
      } else {
        // Mostrar mensaje de error
        $("#componente-detalle .detail-content").html(`
          <div class="detail-empty">
            <div class="detail-empty-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="detail-empty-text">
              Error al cargar los detalles del componente
            </div>
          </div>
        `);
      }
    },
    error: (xhr, status, error) => {
      // Mostrar mensaje de error
      $("#componente-detalle .detail-content").html(`
        <div class="detail-empty">
          <div class="detail-empty-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="detail-empty-text">
            Error de conexión al servidor
          </div>
        </div>
      `);
      console.error("Error al obtener detalles del componente:", error);
    },
  });
}

/**
 * Oculta el panel de detalles del componente
 */
function ocultarDetalleComponente() {
  // Restaurar contenido por defecto
  $("#componente-detalle").html(`
        <div class="detail-header">
            <h2 class="detail-title">Detalles del Componente</h2>
            <p class="detail-subtitle">Seleccione un componente para ver información</p>
        </div>
        <div class="detail-content">
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un componente para ver sus detalles
                </div>
            </div>
        </div>
    `);
}

/**
 * Muestra los detalles completos de un componente en un modal
 * @param {number} id - ID del componente
 */
function verDetallesComponente(id) {
  // Mostrar indicador de carga
  showLoadingOverlay();

  // Mostrar toast de información
  if (window.showInfoToast) {
    window.showInfoToast("Cargando detalles del componente...");
  }

  // Obtener datos del componente
  $.ajax({
    url: getUrl("api/equipos/componentes/obtener.php"),
    type: "GET",
    data: { id: id },
    dataType: "json",
    success: (response) => {
      // Ocultar indicador de carga
      hideLoadingOverlay();

      if (response.success && response.data) {
        const componente = response.data;
        componenteActual = componente;
        const unidad = getUnidadOrometro(componente.tipo_orometro);

        // Actualizar datos en el modal
        $("#detalle-nombre").text(componente.nombre);
        $("#detalle-codigo").text(componente.codigo || "-");
        $("#detalle-equipo").text(componente.equipo_nombre || "-");
        $("#detalle-tipo-orometro").text(
          capitalizarPrimeraLetra(componente.tipo_orometro) || "-"
        );
        $("#detalle-marca").text(componente.marca || "-");
        $("#detalle-modelo").text(componente.modelo || "-");
        $("#detalle-serie").text(componente.numero_serie || "-");

        // Actualizar información de orómetros
        $("#detalle-orometro-anterior").text(
          formatearNumero(componente.anterior_orometro || 0) + " " + unidad
        );
        $("#detalle-orometro").text(
          componente.orometro_actual
            ? formatearNumero(componente.orometro_actual) + " " + unidad
            : "0.00 " + unidad
        );
        $("#detalle-proximo-orometro").text(
          componente.proximo_orometro
            ? formatearNumero(componente.proximo_orometro) + " " + unidad
            : "-"
        );

        // Calcular y mostrar tiempo restante
        if (componente.proximo_orometro && componente.orometro_actual) {
          const restante =
            Number.parseFloat(componente.proximo_orometro) -
            Number.parseFloat(componente.orometro_actual);

          // Determinar color según proximidad
          let colorClase = "success";
          if (componente.notificacion) {
            const notificacion = Number.parseFloat(componente.notificacion);
            if (restante <= notificacion / 2) {
              colorClase = "danger";
            } else if (restante <= notificacion) {
              colorClase = "warning";
            }
          }

          if (restante > 0) {
            $("#detalle-tiempo-restante").html(`
                            <div class="alert alert-${colorClase} mb-3">
                                <strong>Tiempo para mantenimiento:</strong> ${formatearNumero(
                                  restante
                                )} ${unidad}
                            </div>
                        `);
          } else {
            $("#detalle-tiempo-restante").html(`
                            <div class="alert alert-danger mb-3">
                                <strong>¡Mantenimiento requerido!</strong> Excedido por ${formatearNumero(
                                  Math.abs(restante)
                                )} ${unidad}
                            </div>
                        `);
          }
        } else {
          $("#detalle-tiempo-restante").html("");
        }

        $("#detalle-limite").text(
          componente.limite
            ? formatearNumero(componente.limite) + " " + unidad
            : "-"
        );
        $("#detalle-notificacion").text(
          componente.notificacion
            ? formatearNumero(componente.notificacion) + " " + unidad
            : "-"
        );
        $("#detalle-mantenimiento").text(
          componente.mantenimiento
            ? formatearNumero(componente.mantenimiento) + " " + unidad
            : "-"
        );
        $("#detalle-observaciones").text(componente.observaciones || "-");

        // Actualizar imagen
        if (componente.imagen) {
          $("#detalle-imagen").attr("src", componente.imagen);
        } else {
          $("#detalle-imagen").attr(
            "src",
            getUrl("assets/img/equipos/componentes/default.png")
          );
        }

        // Actualizar estado
        const estadoClases = {
          activo: "bg-success",
          mantenimiento: "bg-warning text-dark",
          averiado: "bg-danger",
          vendido: "bg-secondary",
          descanso: "bg-info",
        };

        const estadoTexto = {
          activo: "Activo",
          mantenimiento: "Mantenimiento",
          averiado: "Averiado",
          vendido: "Vendido",
          descanso: "Descanso",
        };

        $("#detalle-estado").attr(
          "class",
          "badge rounded-pill " +
            (estadoClases[componente.estado] || "bg-secondary")
        );
        $("#detalle-estado").text(
          estadoTexto[componente.estado] || componente.estado
        );

        // Mostrar modal
        const modalDetalle = new bootstrap.Modal(
          document.getElementById("modal-detalle-componente")
        );
        modalDetalle.show();
      } else {
        if (window.showErrorToast) {
          window.showErrorToast(
            response.message || "Error al obtener los detalles del componente"
          );
        }
      }
    },
    error: (xhr, status, error) => {
      // Ocultar indicador de carga
      hideLoadingOverlay();
      if (window.showErrorToast) {
        window.showErrorToast("Error de conexión al servidor");
      }
      console.error("Error al obtener detalles del componente:", error);
    },
  });
}

/**
 * Edita un componente desde el modal de detalles
 */
function editarDesdeDetalle() {
  // Cerrar modal de detalles
  const modalDetalle = bootstrap.Modal.getInstance(
    document.getElementById("modal-detalle-componente")
  );
  modalDetalle.hide();

  // Abrir modal de edición
  if (componenteActual && componenteActual.id) {
    setTimeout(() => {
      editarComponente(componenteActual.id);
    }, 500);
  }
}

/**
 * Amplía la imagen del componente en el modal de detalles
 */
function ampliarImagenDetalle() {
  const imagen = $("#detalle-imagen").attr("src");
  try {
    if (imagen && window.imageViewer) {
      window.imageViewer.show(imagen, "Imagen del componente");
    }
  } catch (e) {
    console.error("Error al mostrar la imagen:", e);
  }
}

/**
 * Actualiza las unidades de orómetro en el formulario
 */
function actualizarUnidadesOrometro() {
  const tipoOrometro = $("#componente-tipo-orometro").val();
  const unidad = getUnidadOrometro(tipoOrometro);

  // Actualizar todas las unidades en el formulario
  $(".unidad-orometro").text(unidad);
}

/**
 * Calcula el próximo orómetro basado en el actual y el mantenimiento
 */
function calcularProximoOrometro() {
  const anteriorOrometro =
    parseFloat($("#componente-anterior-orometro").val()) || 0;
  const mantenimiento = parseFloat($("#componente-mantenimiento").val()) || 0;
  const proximoOrometro = anteriorOrometro + mantenimiento;

  // Actualizar el campo visible de próximo orómetro
  $("#componente-proximo-orometro").val(proximoOrometro.toFixed(2));

  // Actualizar también el campo oculto
  $("#hidden-proximo-orometro").val(proximoOrometro.toFixed(2));
}

/**
 * Aplica los filtros seleccionados a la tabla
 */
function aplicarFiltros() {
  // Obtener valores de filtros
  const equipo = $("#filtro-equipo").val();
  const estado = $("#filtro-estado").val();
  const tipoOrometro = $("#filtro-tipo-orometro").val();

  // Actualizar filtros activos
  filtrosAplicados = {};
  if (equipo) filtrosAplicados.equipo_id = equipo;
  if (estado) filtrosAplicados.estado = estado;
  if (tipoOrometro) filtrosAplicados.tipo_orometro = tipoOrometro;

  // Mostrar toast de información
  if (window.showInfoToast) {
    window.showInfoToast("Aplicando filtros...");
  }

  // Recargar tabla
  componentesTable.ajax.reload();

  // Limpiar panel de detalles
  ocultarDetalleComponente();
  componenteSeleccionado = null;
}

/**
 * Limpia los filtros aplicados
 */
function limpiarFiltros() {
  // Restablecer valores de filtros
  $("#filtro-equipo").val("");
  $("#filtro-estado").val("");
  $("#filtro-tipo-orometro").val("");

  // Limpiar filtros activos
  filtrosAplicados = {};

  // Mostrar toast de información
  if (window.showInfoToast) {
    window.showInfoToast("Limpiando filtros...");
  }

  // Recargar tabla
  componentesTable.ajax.reload();

  // Limpiar panel de detalles
  ocultarDetalleComponente();
  componenteSeleccionado = null;
}

/**
 * Obtiene la unidad según el tipo de orómetro
 * @param {string} tipoOrometro - Tipo de orómetro (horas o kilometros)
 * @returns {string} - Unidad (hrs o km)
 */
function getUnidadOrometro(tipoOrometro) {
  return tipoOrometro && tipoOrometro.toLowerCase() === "kilometros"
    ? "km"
    : "hrs";
}

/**
 * Capitaliza la primera letra de un texto
 * @param {string} texto - Texto a capitalizar
 * @returns {string} - Texto con la primera letra en mayúscula
 */
function capitalizarPrimeraLetra(string) {
  if (!string) return string;
  return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

/**
 * Formatea un número para mostrar
 * @param {number} numero - Número a formatear
 * @returns {string} - Número formateado
 */
function formatearNumero(numero) {
  if (numero === null || numero === undefined) return "0";
  return Number.parseFloat(numero)
    .toFixed(2)
    .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Obtiene la clase del estado
 * @param {string} estado - Estado del componente
 * @returns {string} - Clase CSS
 */
function obtenerClaseEstado(estado) {
  const clases = {
    activo: "bg-success",
    mantenimiento: "bg-warning",
    averiado: "bg-danger",
    vendido: "bg-secondary",
    descanso: "bg-info",
  };
  return clases[estado.toLowerCase()] || "bg-secondary";
}

/**
 * Muestra un mensaje toast
 * @param {string} tipo - Tipo de mensaje (success, error, warning, info)
 * @param {string} titulo - Título del mensaje
 * @param {string} mensaje - Contenido del mensaje
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

/**
 * Muestra un indicador de carga
 */
function showLoadingOverlay() {
  // Si existe un componente de carga, usarlo
  if (typeof window.showLoading === "function") {
    window.showLoading();
  }
}

/**
 * Oculta el indicador de carga
 */
function hideLoadingOverlay() {
  // Si existe un componente de carga, usarlo
  if (typeof window.hideLoading === "function") {
    window.hideLoading();
  }
}

/**
 * Verifica si el usuario tiene un permiso específico
 * @param {string} permiso - Permiso a verificar
 * @returns {boolean} - true si tiene el permiso, false en caso contrario
 */
function tienePermiso(permiso) {
  // Verificación basada en elementos del DOM
  if (permiso === "componentes.crear") {
    return $("#btn-nuevo-componente").length > 0;
  } else if (permiso === "componentes.editar") {
    return $("#btn-editar-desde-detalle").length > 0;
  } else if (permiso === "componentes.eliminar") {
    return true; // Por defecto permitir eliminar
  }

  return false;
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
