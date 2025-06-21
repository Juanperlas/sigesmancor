/**
 * Gestión de mantenimiento correctivo
 * Funcionalidades para listar, crear, completar y ver detalles de mantenimientos correctivos
 */

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
if (!window.showInfoToast)
  window.showInfoToast = (msg) => {
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

// Declarar las variables globales al principio del archivo
let modalCrear;
let modalCompletar;
let mantenimientosTable;
let mantenimientoSeleccionado;
let cargarDetallesMantenimiento;
let mantenimientoActual;
let imageUploaderProblema;
let imageUploaderMantenimiento;

document.addEventListener("DOMContentLoaded", () => {
  // Variables globales
  //let mantenimientosTable;
  //let mantenimientoActual = null;
  //let mantenimientoSeleccionado = null;
  let filtrosActivos = {
    estado: "pendiente", // Por defecto mostrar pendientes
  };
  //let imageUploaderProblema = null;
  //let imageUploaderMantenimiento = null;
  //let modalCrear = null;
  //let modalCompletar = null;
  let equiposDisponibles = [];
  let componentesDisponibles = [];

  // Función para obtener la URL base
  function getBaseUrl() {
    return window.location.pathname.split("/modulos/")[0] + "/";
  }

  // Función para construir URL completa
  function getUrl(path) {
    return getBaseUrl() + path;
  }

  // Función para obtener la unidad según el tipo de orómetro
  function getUnidadOrometro(tipoOrometro) {
    return tipoOrometro && tipoOrometro.toLowerCase() === "kilometros"
      ? "km"
      : "hrs";
  }

  // Inicializar DataTable
  function initDataTable() {
    mantenimientosTable = $("#mantenimientos-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/mantenimiento/correctivo/listar.php"),
        type: "POST",
        data: (d) => {
          // Si se selecciona "Todos", usar un valor grande pero finito en lugar de -1
          if (d.length == -1) {
            d.length = 10000; // Un número grande pero manejable
          }

          // Agregar filtros activos
          return {
            ...d,
            ...filtrosActivos,
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
          // Columna 0: Imagen
          data: "imagen", // Seguimos usando imagen pero modificamos el render
          orderable: false,
          className: "text-center",
          render: (data, type, row) => {
            // Siempre usamos la imagen del equipo/componente, no la del mantenimiento
            // Si el estado es completado, usamos imagen_equipo si existe, de lo contrario usamos imagen
            if (
              row.estado.toLowerCase() === "completado" &&
              row.imagen_equipo
            ) {
              return `<img src="${row.imagen_equipo}" class="mantenimiento-imagen-tabla" alt="Mantenimiento" data-image-viewer="true">`;
            }
            // Para otros estados, usamos imagen normalmente
            return `<img src="${data}" class="mantenimiento-imagen-tabla" alt="Mantenimiento" data-image-viewer="true">`;
          },
        },
        {
          data: "fecha_formateada",
          className: "align-middle",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return row.fecha_problema;
            }
            return data;
          },
        },
        {
          // Columna 2: Tipo
          data: "tipo_item",
          className: "align-middle text-center",
          render: (data, type) => {
            if (type === "sort" || type === "type") {
              return data;
            }
            return capitalizarPrimeraLetra(data);
          },
        },
        {
          data: "codigo_item",
          className: "align-middle",
          type: "string", // Explicitly set sorting type to string
        },
        {
          // Columna 4: Orómetro Actual
          data: "orometro_actual",
          className: "align-middle text-center",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return Number.parseFloat(row.orometro_actual_valor || 0);
            }
            return `<span class="orometro-valor">${data || "0.00"}</span>`;
          },
        },
        {
          // Columna 5: Estado
          data: "estado",
          className: "align-middle text-center",
          render: (data, type) => {
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
          // Columna 6: Acciones
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-mantenimiento" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (
              data.estado.toLowerCase() === "pendiente" &&
              tienePermiso("mantenimientos.correctivo.completar")
            ) {
              acciones += `<button type="button" class="btn-accion btn-completar-mantenimiento" data-id="${data.id}" data-tipo="${data.tipo_item}" title="Completar"><i class="bi bi-check-circle"></i></button>`;
            }

            acciones += "</div>";
            return acciones;
          },
        },
      ],
      order: [[1, "asc"]],
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
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [1, 2, 3, 4, 5],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-primary",
          exportOptions: {
            columns: [1, 2, 3, 4, 5],
          },
          customize: (doc) => {
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
            const logoBase64 = "TU_IMAGEN_BASE64";

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
                    text: "REPORTE DE MANTENIMIENTOS CORRECTIVOS",
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
            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              // Configurar anchos de columnas proporcionales
              doc.content[tableIndex].table.widths = [
                40, // Fecha
                40, // Tipo
                40, // Código
                50, // Actual
                50, // Estado
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
                  const estado = row[4].text.toString().toLowerCase(); // Columna 'estado'

                  // Determinar color según estado (usando colores pastel)
                  let colorFondo = colores.azulPastel;
                  let colorTexto = colores.azulOscuro;

                  if (estado.includes("pendiente")) {
                    colorFondo = colores.naranjaPastel;
                    colorTexto = colores.naranjaOscuro;
                  } else if (estado.includes("completado")) {
                    colorFondo = colores.verdePastel;
                    colorTexto = colores.verdeOscuro;
                  } else if (estado.includes("cancelado")) {
                    colorFondo = colores.rojoPastel;
                    colorTexto = colores.rojoOscuro;
                  }

                  // Aplicar estilos a cada celda
                  row.forEach((cell, j) => {
                    cell.fontSize = 8;
                    cell.margin = [2, 2, 2, 2];

                    // Alineación según tipo de dato
                    if (j === 2) {
                      // Código
                      cell.alignment = "left";
                    } else if (j === 4) {
                      // Estado
                      cell.fillColor = colorFondo;
                      cell.color = colorTexto;
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 3) {
                      // Orómetro Actual
                      cell.bold = true; // Ponemos en negrita el orómetro actual
                      cell.alignment = "center";
                    } else {
                      cell.alignment = "center";
                    }
                  });

                  // Añadir líneas zebra para mejor legibilidad
                  if (i % 2 === 0) {
                    row.forEach((cell, j) => {
                      if (j !== 4 && !cell.fillColor) {
                        // No sobrescribir el color de estado
                        cell.fillColor = "#f9f9f9";
                      }
                    });
                  }
                }
              });
            }

            // Añadir pie de página simplificado
            doc.footer = (currentPage, pageCount) => ({
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 8,
              margin: [0, 0, 0, 0],
            });

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
              title: "Reporte de Mantenimientos Correctivos",
              author: "CORDIAL SAC",
              subject: "Listado de Mantenimientos Correctivos",
            };
          },
          filename:
            "Reporte_Mantenimientos_Correctivos_" +
            new Date().toISOString().split("T")[0],
          orientation: "landscape",
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
      initComplete: () => {
        // Evento para seleccionar fila
        $("#mantenimientos-table tbody").on("click", "tr", function () {
          const data = mantenimientosTable.row(this).data();
          if (data) {
            // Remover selección anterior
            $("#mantenimientos-table tbody tr").removeClass("selected");
            // Agregar selección a la fila actual
            $(this).addClass("selected");

            // Añadir animación al panel de detalles
            $("#mantenimiento-detalle")
              .removeClass("loaded")
              .addClass("loading");

            // Cargar detalles en el panel lateral con un pequeño retraso para la animación
            setTimeout(() => {
              cargarDetallesMantenimiento(data.id);
              // Quitar clase de carga y añadir clase de cargado para la animación
              $("#mantenimiento-detalle")
                .removeClass("loading")
                .addClass("loaded");
            }, 300);
          }
        });

        // Inicializar eventos para imágenes en la tabla
        $("#mantenimientos-table").on(
          "click",
          ".mantenimiento-imagen-tabla",
          function (e) {
            e.stopPropagation();
            if (window.imageViewer) {
              window.imageViewer.show(
                $(this).attr("src"),
                "Imagen del mantenimiento"
              );
            }
          }
        );

        // Cargar equipos y componentes disponibles
        cargarEquiposDisponibles();
        cargarComponentesDisponibles();
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

  // Función para cargar equipos disponibles
  function cargarEquiposDisponibles() {
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/listar-disponibles.php"),
      type: "GET",
      data: { tipo: "equipo" },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          equiposDisponibles = response.data;
        } else {
          console.error(
            "Error al cargar equipos disponibles:",
            response.message
          );
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar equipos disponibles:", error);
      },
    });
  }

  // Función para cargar componentes disponibles
  function cargarComponentesDisponibles() {
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/listar-disponibles.php"),
      type: "GET",
      data: { tipo: "componente" },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          componentesDisponibles = response.data;
        } else {
          console.error(
            "Error al cargar componentes disponibles:",
            response.message
          );
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar componentes disponibles:", error);
      },
    });
  }

  // Función para cargar detalles del mantenimiento en el panel lateral
  function cargarDetallesMantenimiento(id) {
    // Mostrar indicador de carga
    $("#mantenimiento-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#mantenimiento-detalle").addClass("active");

    // Obtener datos del mantenimiento
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          mantenimientoSeleccionado = data;

          // Extraer datos del mantenimiento
          const mantenimiento = data.mantenimiento || {};
          const tipo = data.tipo || "";
          const equipo = data.equipo || {};
          const componente = data.componente || {};
          const unidad = data.unidad_orometro || "hrs";

          // Valores seguros para evitar errores
          const estado = mantenimiento.estado || "pendiente";
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const anteriorOrometro = Number.parseFloat(
            data.anterior_orometro || 0
          );

          // Actualizar título del panel y añadir imagen en el encabezado
          // La API ya envía la imagen correcta según el estado
          const imagenUrl = data.imagen;

          const nombre = equipo.nombre || componente.nombre || "";
          $("#mantenimiento-detalle .detail-header").html(`
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="detail-title">Mantenimiento ${capitalizarPrimeraLetra(
                tipo
              )}</h2>
              <p class="detail-subtitle">Fecha: ${formatearFecha(
                mantenimiento.fecha_problema || ""
              )}</p>
            </div>
            <div class="detail-header-image">
              <img src="${imagenUrl}" alt="${nombre}" class="detail-header-img" data-image-viewer="true">
            </div>
          </div>
        `);

          // Información de orómetro
          const infoOrometro = `
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-speedometer2 me-2"></i>Información de Orómetro
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Tipo de Orómetro</label>
                      <div class="form-control form-control-sm bg-light">${capitalizarPrimeraLetra(
                        unidad === "km" ? "kilómetros" : "horas"
                      )}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Orómetro Actual</label>
                      <div class="form-control form-control-sm bg-light">${formatearNumero(
                        orometroActual
                      )} ${unidad}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          // Información del problema
          const infoProblema = `
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-exclamation-triangle me-2"></i>Información del Problema
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Descripción del Problema</label>
                      <div class="form-control form-control-sm bg-light" style="min-height: 60px;">${
                        mantenimiento.descripcion_problema || "-"
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          // Información de completado (si está completado)
          let infoCompletado = "";
          if (estado.toLowerCase() === "completado") {
            infoCompletado = `
              <div class="card-form mb-3">
                <div class="card-form-header">
                  <i class="bi bi-check-circle me-2"></i>Información de Completado
                </div>
                <div class="card-form-body">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <div class="form-group mb-2">
                        <label class="form-label form-label-sm">Fecha Realizado</label>
                        <div class="form-control form-control-sm bg-light">${formatearFecha(
                          mantenimiento.fecha_realizado || ""
                        )}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group mb-2">
                        <label class="form-label form-label-sm">Orómetro al Completar</label>
                        <div class="form-control form-control-sm bg-light">${formatearNumero(
                          orometroActual
                        )} ${unidad}</div>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group mb-2">
                        <label class="form-label form-label-sm">Observaciones</label>
                        <div class="form-control form-control-sm bg-light" style="min-height: 60px;">${
                          mantenimiento.observaciones || "-"
                        }</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `;
          }

          // Botón de completar (si está pendiente)
          let botonCompletar = "";
          if (
            estado.toLowerCase() === "pendiente" &&
            tienePermiso("mantenimientos.correctivo.completar")
          ) {
            botonCompletar = `
              <div class="d-grid gap-2 mt-3">
                <button type="button" id="btn-completar-panel" class="btn btn-success" data-id="${mantenimiento.id}" data-tipo="${tipo}">
                  <i class="bi bi-check-circle me-2"></i>Completar Mantenimiento
                </button>
              </div>
            `;
          }

          // Actualizar contenido del panel
          $("#mantenimiento-detalle .detail-content").html(`
            ${infoOrometro}
            ${infoProblema}
            ${infoCompletado}
            ${botonCompletar}
          `);

          // Inicializar el visor de imágenes para la imagen del encabezado
          $(".detail-header-img").on("click", function () {
            if (window.imageViewer) {
              window.imageViewer.show(
                $(this).attr("src"),
                "Imagen del mantenimiento"
              );
            }
          });

          // Inicializar botón de completar desde el panel
          $("#btn-completar-panel").on("click", function () {
            const id = $(this).data("id");
            const tipo = $(this).data("tipo");
            abrirModalCompletar(id, tipo);
          });
        } else {
          // Mostrar mensaje de error
          $("#mantenimiento-detalle .detail-content").html(`
          <div class="detail-empty">
            <div class="detail-empty-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="detail-empty-text">
              Error al cargar los detalles del mantenimiento
            </div>
          </div>
        `);
        }
      },
      error: (xhr, status, error) => {
        // Mostrar mensaje de error
        $("#mantenimiento-detalle .detail-content").html(`
        <div class="detail-empty">
          <div class="detail-empty-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="detail-empty-text">
            Error de conexión al servidor
          </div>
        </div>
      `);
        console.error("Error al obtener detalles del mantenimiento:", error);
      },
    });
  }

  // Función para capitalizar la primera letra
  function capitalizarPrimeraLetra(string) {
    if (!string) return "";
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  }

  // Función para formatear números
  function formatearNumero(numero) {
    if (numero === null || numero === undefined) return "0.00";
    return Number.parseFloat(numero)
      .toFixed(2)
      .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  // Función para formatear fechas
  function formatearFecha(fecha) {
    if (!fecha) return "-";
    const date = new Date(fecha);
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  }

  // Función para abrir modal de crear mantenimiento
  function abrirModalCrear() {
    // Limpiar formulario
    $("#form-crear")[0].reset();
    $("#crear-tipo-item").val("");
    $("#crear-item-id").val("").prop("disabled", true);
    $("#crear-item-id")
      .empty()
      .append('<option value="">Primero seleccione un tipo</option>');

    // Establecer fecha actual
    const hoy = new Date();
    const fechaFormateada = hoy.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
    $("#crear-fecha-problema").val(fechaFormateada);

    // Inicializar componente de carga de imágenes
    try {
      if (
        imageUploaderProblema &&
        typeof imageUploaderProblema.destroy === "function"
      ) {
        imageUploaderProblema.destroy();
      }

      // Inicializar el componente de carga de imágenes
      imageUploaderProblema = new ImageUpload("container-problema-imagen", {
        maxSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "imagen",
        defaultImage: getUrl("assets/img/mantenimiento/correctivo/default.png"),
        existingImage: "",
        uploadPath: "assets/img/mantenimiento/correctivo/",
        position: "center",
        onError: (error) => {
          if (window.showErrorToast) {
            window.showErrorToast(error);
          }
        },
        onSuccess: () => {
          if (window.showSuccessToast) {
            window.showSuccessToast("Imagen cargada correctamente");
          }
        },
      });
    } catch (e) {
      console.error(
        "Error al inicializar el componente de carga de imágenes:",
        e
      );
      if (window.showErrorToast) {
        window.showErrorToast("Error al configurar la carga de imágenes");
      }
    }

    // Mostrar modal
    modalCrear = new bootstrap.Modal(document.getElementById("modal-crear"));
    modalCrear.show();

    // Inicializar datepicker después de mostrar el modal
    modalCrear._element.addEventListener("shown.bs.modal", () => {
      initDatepicker();
    });
  }

  // Función para crear mantenimiento correctivo
  function crearMantenimientoCorrectivo() {
    // Validar formulario
    if (!validarFormularioCrear()) {
      return;
    }

    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Creando mantenimiento correctivo...");
    }

    // Preparar datos del formulario
    const formData = new FormData();
    formData.append("tipo_item", $("#crear-tipo-item").val());
    formData.append("item_id", $("#crear-item-id").val());
    formData.append("fecha_problema", $("#crear-fecha-problema").val());
    formData.append("orometro_actual", $("#crear-orometro-actual").val());
    formData.append(
      "descripcion_problema",
      $("#crear-descripcion-problema").val()
    );
    formData.append("observaciones", $("#crear-observaciones").val());

    // Añadir imagen si existe
    const imagenInput = document.getElementById("input-problema-imagen");
    if (imagenInput && imagenInput.files && imagenInput.files.length > 0) {
      formData.append("imagen", imagenInput.files[0]);
    }

    // Enviar solicitud
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success) {
          // Cerrar modal
          if (modalCrear) {
            modalCrear.hide();
          }

          // Mostrar mensaje de éxito
          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Mantenimiento creado correctamente"
            );
          }

          // Recargar tabla
          mantenimientosTable.ajax.reload();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al crear el mantenimiento"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al crear mantenimiento:", error);
        console.error("Respuesta del servidor:", xhr.responseText);
      },
    });
  }

  // Función para validar el formulario de crear
  function validarFormularioCrear() {
    // Validar tipo de ítem
    const tipoItem = $("#crear-tipo-item").val();
    if (!tipoItem) {
    }
  }

  // Función para validar el formulario de crear
  function validarFormularioCrear() {
    // Validar tipo de ítem
    const tipoItem = $("#crear-tipo-item").val();
    if (!tipoItem) {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un tipo de ítem");
      }
      $("#crear-tipo-item").focus();
      return false;
    }

    // Validar ítem
    const itemId = $("#crear-item-id").val();
    if (!itemId) {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un ítem");
      }
      $("#crear-item-id").focus();
      return false;
    }

    // Validar fecha problema
    const fechaProblema = $("#crear-fecha-problema").val();
    if (!fechaProblema) {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar una fecha del problema");
      }
      $("#crear-fecha-problema").focus();
      return false;
    }

    // Validar orómetro actual
    const orometroActual = $("#crear-orometro-actual").val();
    if (
      !orometroActual ||
      isNaN(orometroActual) ||
      Number.parseFloat(orometroActual) <= 0
    ) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "Debe ingresar un valor válido para el orómetro actual"
        );
      }
      $("#crear-orometro-actual").focus();
      return false;
    }

    // Validar descripción del problema
    const descripcionProblema = $("#crear-descripcion-problema").val();
    if (!descripcionProblema) {
      if (window.showErrorToast) {
        window.showErrorToast("Debe ingresar una descripción del problema");
      }
      $("#crear-descripcion-problema").focus();
      return false;
    }

    return true;
  }

  // Función para abrir modal de completar mantenimiento
  function abrirModalCompletar(id, tipo) {
    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Cargando información del mantenimiento...");
    }

    // Obtener datos del mantenimiento
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;
          mantenimientoActual = data;

          // Extraer datos del mantenimiento
          const mantenimiento = data.mantenimiento || {};
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const unidad = data.unidad_orometro || "hrs";

          // Llenar formulario
          $("#completar-id").val(mantenimiento.id || id);
          $("#completar-tipo").val(data.tipo || tipo);
          $("#completar-orometro-actual").val(orometroActual.toFixed(2));
          $("#completar-unidad-orometro").text(unidad);

          // Establecer fecha actual
          const hoy = new Date();
          const fechaFormateada = hoy.toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          });
          $("#completar-fecha").val(fechaFormateada);

          // Limpiar el contenedor de imagen antes de inicializar
          $("#container-mantenimiento-imagen").empty();

          // Inicializar componente de carga de imágenes con la ruta correcta
          try {
            // Obtener imagen existente del mantenimiento (si la hay)
            const existingImage =
              mantenimiento.imagen && mantenimiento.imagen !== ""
                ? getUrl(mantenimiento.imagen)
                : "";

            // Asegurarse de que la ruta de carga sea correcta
            const uploadPath = "assets/img/mantenimiento/correctivo/";

            if (
              imageUploaderMantenimiento &&
              typeof imageUploaderMantenimiento.destroy === "function"
            ) {
              imageUploaderMantenimiento.destroy();
            }

            // Inicializar el componente de carga de imágenes
            imageUploaderMantenimiento = new ImageUpload(
              "container-mantenimiento-imagen",
              {
                maxSize: 2 * 1024 * 1024, // 2MB
                acceptedTypes: [
                  "image/jpeg",
                  "image/png",
                  "image/gif",
                  "image/webp",
                ],
                inputName: "imagen",
                defaultImage: getUrl(
                  "assets/img/mantenimiento/correctivo/default.png"
                ),
                existingImage: existingImage,
                uploadPath: uploadPath,
                position: "center",
                onError: (error) => {
                  if (window.showErrorToast) {
                    window.showErrorToast(error);
                  }
                },
                onSuccess: () => {
                  if (window.showSuccessToast) {
                    window.showSuccessToast("Imagen cargada correctamente");
                  }
                },
              }
            );
          } catch (e) {
            console.error(
              "Error al inicializar el componente de carga de imágenes:",
              e
            );
            if (window.showErrorToast) {
              window.showErrorToast("Error al configurar la carga de imágenes");
            }
          }

          // Mostrar modal y asignar a variable global
          modalCompletar = new bootstrap.Modal(
            document.getElementById("modal-completar")
          );
          modalCompletar.show();

          // Inicializar datepicker después de mostrar el modal
          modalCompletar._element.addEventListener("shown.bs.modal", () => {
            initDatepicker();
          });
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos del mantenimiento"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener mantenimiento:", error);
      },
    });
  }

  // Función para completar mantenimiento
  function completarMantenimiento() {
    // Validar formulario
    if (!validarFormularioCompletar()) {
      return;
    }

    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Completando mantenimiento...");
    }

    // Preparar datos del formulario
    const formData = new FormData();
    formData.append("id", $("#completar-id").val());
    formData.append("orometro_actual", $("#completar-orometro-actual").val());
    formData.append("observaciones", $("#completar-observaciones").val());

    // Añadir imagen si existe
    const imagenInput = document.getElementById("input-mantenimiento-imagen");
    if (imagenInput && imagenInput.files && imagenInput.files.length > 0) {
      formData.append("imagen", imagenInput.files[0]);
    }

    // Enviar solicitud
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/completar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success) {
          // Cerrar modal usando la variable global
          if (modalCompletar) {
            modalCompletar.hide();
          }

          // Mostrar mensaje de éxito
          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Mantenimiento completado correctamente"
            );
          }

          // Recargar tabla
          mantenimientosTable.ajax.reload();

          // Si hay un mantenimiento seleccionado, actualizar sus detalles
          if (
            mantenimientoSeleccionado &&
            mantenimientoSeleccionado.mantenimiento &&
            mantenimientoSeleccionado.mantenimiento.id ==
              $("#completar-id").val()
          ) {
            $("#mantenimiento-detalle")
              .removeClass("loaded")
              .addClass("loading");
            setTimeout(() => {
              cargarDetallesMantenimiento($("#completar-id").val());
              $("#mantenimiento-detalle")
                .removeClass("loading")
                .addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al completar el mantenimiento"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al completar mantenimiento:", error);
        console.error("Respuesta del servidor:", xhr.responseText);
      },
    });
  }

  // Función para validar el formulario de completar
  function validarFormularioCompletar() {
    // Validar orómetro actual
    const orometroActual = $("#completar-orometro-actual").val();
    if (
      !orometroActual ||
      isNaN(orometroActual) ||
      Number.parseFloat(orometroActual) <= 0
    ) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "Debe ingresar un valor válido para el orómetro actual"
        );
      }
      $("#completar-orometro-actual").focus();
      return false;
    }

    // Validar fecha realizado
    const fechaRealizado = $("#completar-fecha").val();
    if (!fechaRealizado) {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar una fecha de realización");
      }
      $("#completar-fecha").focus();
      return false;
    }

    return true;
  }

  // Función para ver detalles del mantenimiento en modal
  function verDetalleMantenimiento(id) {
    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Cargando detalles del mantenimiento...");
    }

    // Obtener datos del mantenimiento
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;
          mantenimientoActual = data;

          // Extraer datos del mantenimiento
          const mantenimiento = data.mantenimiento || {};
          const tipo = data.tipo || "";
          const equipo = data.equipo || {};
          const componente = data.componente || {};
          const unidad = data.unidad_orometro || "hrs";

          // Valores seguros para evitar errores
          const estado = mantenimiento.estado || "pendiente";
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const anteriorOrometro = Number.parseFloat(
            data.anterior_orometro || 0
          );
          const nombre = equipo.nombre || componente.nombre || "";
          const codigo = equipo.codigo || componente.codigo || "";

          // Actualizar datos en el modal
          $("#detalle-nombre").text(nombre);
          $("#detalle-codigo").text(codigo || "-");
          $("#detalle-tipo").text(capitalizarPrimeraLetra(tipo) || "-");
          $("#detalle-tipo-orometro").text(
            capitalizarPrimeraLetra(unidad === "km" ? "kilómetros" : "horas") ||
              "-"
          );
          $("#detalle-fecha-problema").text(
            formatearFecha(mantenimiento.fecha_problema || "-")
          );
          $("#detalle-fecha-realizado").text(
            mantenimiento.fecha_realizado
              ? formatearFecha(mantenimiento.fecha_realizado)
              : "-"
          );

          // Actualizar información de orómetros
          $("#detalle-orometro-anterior").text(
            formatearNumero(anteriorOrometro) + " " + unidad
          );
          $("#detalle-orometro").text(
            formatearNumero(orometroActual) + " " + unidad
          );

          // Actualizar descripción y observaciones
          $("#detalle-descripcion").text(
            mantenimiento.descripcion_problema || "-"
          );
          $("#detalle-observaciones").text(mantenimiento.observaciones || "-");

          // Actualizar imagen - La API ya envía la imagen correcta según el estado
          const imageSrc = data.imagen;
          $("#detalle-imagen").attr("src", imageSrc);

          // Actualizar estado
          const estadoClases = {
            pendiente: "bg-warning text-dark",
            completado: "bg-success",
            cancelado: "bg-danger",
          };

          $("#detalle-estado").attr(
            "class",
            "badge rounded-pill " +
              (estadoClases[estado.toLowerCase()] || "bg-secondary")
          );
          $("#detalle-estado").text(capitalizarPrimeraLetra(estado));

          // Mostrar/ocultar botón de completar según estado
          if (
            estado.toLowerCase() === "pendiente" &&
            tienePermiso("mantenimientos.correctivo.completar")
          ) {
            $("#btn-completar-desde-detalle").show();
          } else {
            $("#btn-completar-desde-detalle").hide();
          }

          // Cargar historial de mantenimientos
          cargarHistorialMantenimientos(
            tipo === "equipo" ? equipo.id : componente.id,
            tipo
          );

          // Mostrar modal
          const modalDetalle = new bootstrap.Modal(
            document.getElementById("modal-detalle-mantenimiento")
          );
          modalDetalle.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message ||
                "Error al obtener los detalles del mantenimiento"
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
        console.error("Error al obtener detalles del mantenimiento:", error);
      },
    });
  }

  // Función para cargar historial de mantenimientos
  function cargarHistorialMantenimientos(itemId, tipoItem) {
    // Mostrar indicador de carga en la tabla de historial
    $("#historial-body").html(
      '<tr><td colspan="5" class="text-center">Cargando historial...</td></tr>'
    );

    // Enviar solicitud - CAMBIAR URL para usar historial específico de correctivos
    $.ajax({
      url: getUrl("api/mantenimiento/correctivo/historial.php"),
      type: "GET",
      data: {
        item_id: itemId,
        tipo_item: tipoItem,
      },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data && response.data.length > 0) {
          // Construir filas de la tabla
          let html = "";
          response.data.forEach((item) => {
            html += `<tr>
            <td>${formatearFecha(item.fecha)}</td>
            <td>${capitalizarPrimeraLetra(item.tipo_mantenimiento)}</td>
            <td>${formatearNumero(item.orometro)} ${item.unidad || "hrs"}</td>
            <td>${item.descripcion || "-"}</td>
            <td>${item.observaciones || "-"}</td>
          </tr>`;
          });

          // Actualizar tabla
          $("#historial-body").html(html);
          $("#sin-historial").addClass("d-none");
        } else {
          // Mostrar mensaje de sin historial
          $("#historial-body").html("");
          $("#sin-historial").removeClass("d-none");
        }
      },
      error: (xhr, status, error) => {
        // Mostrar mensaje de error
        $("#historial-body").html(
          '<tr><td colspan="5" class="text-center text-danger">Error al cargar el historial</td></tr>'
        );
        console.error("Error al cargar historial de mantenimientos:", error);
      },
    });
  }

  // Cambio en el ítem seleccionado - AGREGAR carga automática de orómetro
  $("#crear-item-id").on("change", function () {
    const itemId = $(this).val();
    const tipo = $("#crear-tipo-item").val();

    if (itemId && tipo) {
      // Buscar el ítem seleccionado en los datos disponibles
      let opciones = [];
      if (tipo === "equipo") {
        opciones = equiposDisponibles;
      } else if (tipo === "componente") {
        opciones = componentesDisponibles;
      }

      // Encontrar el ítem seleccionado
      const itemSeleccionado = opciones.find((item) => item.id == itemId);

      if (itemSeleccionado && itemSeleccionado.orometro_actual) {
        // Cargar automáticamente el orómetro actual
        $("#crear-orometro-actual").val(
          Number.parseFloat(itemSeleccionado.orometro_actual).toFixed(2)
        );

        // Mostrar toast informativo
        if (window.showInfoToast) {
          window.showInfoToast(
            `Orómetro actual cargado: ${Number.parseFloat(
              itemSeleccionado.orometro_actual
            ).toFixed(2)} ${
              itemSeleccionado.tipo_orometro === "horas" ? "hrs" : "km"
            }`
          );
        }
      }
    } else {
      // Limpiar el campo si no hay selección
      $("#crear-orometro-actual").val("");
    }
  });

  // Inicializar datepicker
  function initDatepicker() {
    const datepickers = $(".datepicker"); // Usa jQuery para seleccionar los elementos
    console.log(
      "Intentando inicializar datepickers. Elementos encontrados:",
      datepickers.length
    );
    datepickers.each(function () {
      try {
        if (typeof $.fn.datepicker === "undefined") {
          console.error(
            "Bootstrap Datepicker no está definido. Verifica que bootstrap-datepicker.min.js se haya cargado."
          );
          return;
        }
        console.log("Inicializando datepicker en:", this.id || this.className);
        $(this).datepicker({
          format: "dd/mm/yyyy",
          language: "es",
          autoclose: true,
          todayBtn: true,
          todayHighlight: true,
          weekStart: 1,
        });
      } catch (e) {
        console.error(
          "Error al inicializar datepicker en",
          this.id || this.className,
          ":",
          e
        );
      }
    });
  }

  // Aplicar filtros
  function aplicarFiltros() {
    // Obtener valores de filtros
    const estado = $("#filtro-estado").val();
    const tipo = $("#filtro-tipo").val();
    const fechaDesde = $("#filtro-fecha-desde").val();
    const fechaHasta = $("#filtro-fecha-hasta").val();

    // Actualizar filtros activos
    filtrosActivos = {};
    if (estado) filtrosActivos.estado = estado;
    if (tipo) filtrosActivos.tipo = tipo;
    if (fechaDesde) {
      const partesFecha = fechaDesde.split("/");
      if (partesFecha.length === 3) {
        filtrosActivos.fecha_desde = `${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`;
      }
    }
    if (fechaHasta) {
      const partesFecha = fechaHasta.split("/");
      if (partesFecha.length === 3) {
        filtrosActivos.fecha_hasta = `${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`;
      }
    }

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    // Recargar tabla
    mantenimientosTable.ajax.reload();

    // Limpiar panel de detalles
    $("#mantenimiento-detalle .detail-content").html(`
    <div class="detail-empty">
      <div class="detail-empty-icon">
        <i class="bi bi-info-circle"></i>
      </div>
      <div class="detail-empty-text">
        Seleccione un mantenimiento para ver sus detalles
      </div>
    </div>
  `);
    $("#mantenimiento-detalle").removeClass("active");
    mantenimientoSeleccionado = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    // Restablecer valores de filtros
    $("#filtro-estado").val("pendiente");
    $("#filtro-tipo").val("");
    $("#filtro-fecha-desde").val("");
    $("#filtro-fecha-hasta").val("");

    // Limpiar filtros activos
    filtrosActivos = {
      estado: "pendiente", // Por defecto mostrar pendientes
    };

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    // Recargar tabla
    mantenimientosTable.ajax.reload();

    // Limpiar panel de detalles
    $("#mantenimiento-detalle .detail-content").html(`
    <div class="detail-empty">
      <div class="detail-empty-icon">
        <i class="bi bi-info-circle"></i>
      </div>
      <div class="detail-empty-text">
        Seleccione un mantenimiento para ver sus detalles
      </div>
    </div>
  `);
    $("#mantenimiento-detalle").removeClass("active");
    mantenimientoSeleccionado = null;
  }

  // Mostrar indicador de carga
  function showLoadingOverlay() {
    // Si existe un componente de carga, usarlo
    if (typeof window.showLoading === "function") {
      window.showLoading();
    }
  }

  // Ocultar indicador de carga
  function hideLoadingOverlay() {
    // Si existe un componente de carga, usarlo
    if (typeof window.hideLoading === "function") {
      window.hideLoading();
    }
  }

  // Verificar si el usuario tiene un permiso específico
  function tienePermiso(permiso) {
    // Si existe la función global, usarla
    if (typeof window.tienePermiso === "function") {
      return window.tienePermiso(permiso);
    }

    // Si no existe, verificar si el botón correspondiente está presente en el DOM
    if (permiso === "mantenimientos.correctivo.completar") {
      return $("#btn-completar-desde-detalle").length > 0;
    }

    return true; // Por defecto permitir
  }

  // Inicializar componentes
  initDataTable();
  initDatepicker();

  // Event Listeners
  // Botón para crear nuevo mantenimiento
  $("#btn-nuevo-mantenimiento").on("click", () => {
    abrirModalCrear();
  });

  // Cambio en el tipo de ítem
  $("#crear-tipo-item").on("change", function () {
    const tipo = $(this).val();
    const itemSelect = $("#crear-item-id");

    // Limpiar y deshabilitar el select de ítems
    itemSelect.empty().prop("disabled", true);
    itemSelect.append('<option value="">Cargando...</option>');

    if (tipo) {
      // Habilitar el select y cargar las opciones según el tipo
      itemSelect.prop("disabled", false);

      let opciones = [];
      if (tipo === "equipo") {
        opciones = equiposDisponibles;
      } else if (tipo === "componente") {
        opciones = componentesDisponibles;
      }

      // Llenar el select con las opciones
      itemSelect.empty();
      itemSelect.append('<option value="">Seleccione un ítem</option>');

      if (opciones.length > 0) {
        opciones.forEach((item) => {
          itemSelect.append(
            `<option value="${item.id}">${item.nombre} (${item.codigo})</option>`
          );
        });
      } else {
        itemSelect.append('<option value="">No hay ítems disponibles</option>');
      }
    } else {
      // Si no se selecciona un tipo, mostrar mensaje por defecto
      itemSelect.empty();
      itemSelect.append('<option value="">Primero seleccione un tipo</option>');
    }
  });

  // Botón para guardar nuevo mantenimiento
  $("#btn-guardar-crear").on("click", () => {
    crearMantenimientoCorrectivo();
  });

  // Botón para guardar completado
  $("#btn-guardar-completar").on("click", () => {
    completarMantenimiento();
  });

  // Botón para ver detalles del mantenimiento
  $(document).on("click", ".btn-ver-mantenimiento", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    verDetalleMantenimiento(id);
  });

  // Botón para completar mantenimiento
  $(document).on("click", ".btn-completar-mantenimiento", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    const tipo = $(this).data("tipo");
    abrirModalCompletar(id, tipo);
  });

  // Botón para completar desde el modal de detalles
  $("#btn-completar-desde-detalle").on("click", () => {
    // Cerrar modal de detalles
    const modalDetalle = bootstrap.Modal.getInstance(
      document.getElementById("modal-detalle-mantenimiento")
    );
    modalDetalle.hide();

    // Abrir modal de completar
    if (
      mantenimientoActual &&
      mantenimientoActual.mantenimiento &&
      mantenimientoActual.mantenimiento.id
    ) {
      setTimeout(() => {
        abrirModalCompletar(
          mantenimientoActual.mantenimiento.id,
          mantenimientoActual.tipo
        );
      }, 500);
    }
  });

  // Botón Generar Reporte
  $("#btn-generar-reporte").on("click", function () {
    if (
      mantenimientoActual &&
      mantenimientoActual.mantenimiento &&
      mantenimientoActual.mantenimiento.id
    ) {
      window.open(
        getUrl(
          "api/mantenimiento/correctivo/generar_reporte.php?id=" +
            mantenimientoActual.mantenimiento.id
        ),
        "_blank"
      );
    } else {
      if (window.showErrorToast) {
        window.showErrorToast("No se ha seleccionado un mantenimiento");
      }
    }
  });

  // Botón para ver imagen ampliada
  $("#btn-ver-imagen").on("click", () => {
    const imagen = $("#detalle-imagen").attr("src");
    try {
      if (imagen && window.imageViewer) {
        window.imageViewer.show(imagen, "Imagen del mantenimiento");
      }
    } catch (e) {
      console.error("Error al mostrar imagen:", e);
    }
  });

  // Botones de filtros
  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros();
  });

  // Botón para limpiar filtros
  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros();
  });

  // Inicializar tooltips
  const tooltips = document.querySelectorAll("[title]");
  tooltips.forEach((tooltip) => {
    try {
      new bootstrap.Tooltip(tooltip);
    } catch (e) {
      console.warn("Error al inicializar tooltip:", e);
    }
  });
});

// Añadir función showInfoToast si no existe
if (!window.showInfoToast) {
  window.showInfoToast = (msg) => {
    console.log(msg);
    // Si existe showSuccessToast, usarlo con un estilo diferente
    if (window.showSuccessToast) {
      try {
        // Intentar llamar a la función con un tipo diferente
        window.showSuccessToast(msg);
      } catch (e) {
        console.log("Info toast:", msg);
      }
    }
  };
}

// Declarar las variables globales al principio del archivo
//let modalCrear;
//let modalCompletar;
//let mantenimientosTable;
//let mantenimientoSeleccionado;
//let cargarDetallesMantenimiento;
//let mantenimientoActual;
//let imageUploaderProblema;
//let imageUploaderMantenimiento;
