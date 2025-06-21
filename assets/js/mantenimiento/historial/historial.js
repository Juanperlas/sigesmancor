/**
 * Gestión de historial de mantenimientos
 * Funcionalidades para listar y ver detalles de mantenimientos completados
 */

// Declaración de variables globales
const $ = jQuery;
const bootstrap = window.bootstrap;

// Verificar si las variables ya están definidas en el objeto window
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
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

document.addEventListener("DOMContentLoaded", () => {
  // Variables globales
  let historialTable;
  let mantenimientoSeleccionado = null;
  let filtrosActivos = {};

  // Función para obtener la URL base
  function getBaseUrl() {
    return window.location.pathname.split("/modulos/")[0] + "/";
  }

  // Función para construir URL completa
  function getUrl(path) {
    return getBaseUrl() + path;
  }

  // Inicializar DataTable
  function initDataTable() {
    historialTable = $("#historial-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/mantenimiento/historial/listar.php"),
        type: "POST",
        data: (d) => {
          // Si se selecciona "Todos", usar un valor grande pero finito en lugar de -1
          if (d.length == -1) {
            d.length = 10000;
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
          // Columna 0: ID del historial
          data: "id",
          className: "align-middle text-center",
          width: "60px",
          render: (data, type) => {
            if (type === "sort" || type === "type") {
              return Number.parseInt(data);
            }
            return `<span class="badge text-black">#${data}</span>`;
          },
        },
        {
          // Columna 1: Imagen
          data: "imagen",
          orderable: false,
          className: "text-center",
          width: "50px",
          render: (data, type, row) => {
            return `<img src="${data}" class="mantenimiento-imagen-tabla" alt="Mantenimiento" data-image-viewer="true">`;
          },
        },
        {
          // Columna 2: Tipo de mantenimiento
          data: "tipo_mantenimiento",
          className: "align-middle text-center",
          render: (data, type) => {
            if (type === "sort" || type === "type") {
              return data;
            }
            const badgeClass =
              data === "correctivo" ? "tipo-correctivo" : "tipo-preventivo";
            return `<span class="tipo-badge ${badgeClass}">${capitalizarPrimeraLetra(
              data
            )}</span>`;
          },
        },
        {
          // Columna 3: Código
          data: "codigo_item",
          className: "align-middle",
          type: "string",
        },
        {
          // Columna 4: Nombre del equipo/componente
          data: "nombre_item",
          className: "align-middle",
        },
        {
          // Columna 5: Fecha realizado
          data: "fecha_realizado",
          className: "align-middle text-center",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return data;
            }
            return formatearFechaHora(data);
          },
        },
        {
          // Columna 6: Orómetro
          data: "orometro_realizado_valor",
          className: "align-middle text-center",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return Number.parseFloat(data || 0);
            }
            return `${formatearNumero(data || 0)} ${
              row.unidad_orometro || "hrs"
            }`;
          },
        },
        {
          // Columna 7: Acciones
          data: null,
          orderable: false,
          className: "text-center align-middle",
          width: "80px",
          render: (data) => {
            return `<div class="btn-group btn-group-sm">
              <button type="button" class="btn-accion btn-ver-mantenimiento" data-id="${data.id}" data-tipo="${data.tipo_mantenimiento}" title="Ver detalles">
                <i class="bi bi-eye"></i>
              </button>
            </div>`;
          },
        },
      ],
      order: [[0, "desc"]], // Ordenar por ID del historial descendente (más reciente primero)
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
            columns: [0, 2, 3, 4, 5, 6],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 2, 3, 4, 5, 6],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-primary",
          exportOptions: {
            columns: [0, 2, 3, 4, 5, 6],
          },
          customize: (doc) => {
            // Configuración básica del documento
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 8,
              color: "#333333",
            };

            // Definir colores
            const colores = {
              azulPastel: "#D4E6F1",
              verdePastel: "#D5F5E3",
              naranjaPastel: "#FAE5D3",
              azulOscuro: "#1A5276",
              verdeOscuro: "#186A3B",
              naranjaOscuro: "#BA4A00",
              azulPrimario: "#0055A4",
            };

            // Encabezado
            doc.content.unshift(
              {
                text: "HISTORIAL DE MANTENIMIENTOS COMPLETADOS",
                style: "header",
                alignment: "center",
                margin: [0, 15, 0, 15],
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
                30, // ID
                50, // Tipo
                50, // Código
                80, // Nombre
                60, // Fecha y Hora
                50, // Orómetro
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
                  const tipo = row[1].text.toString().toLowerCase();

                  // Determinar color según tipo
                  let colorFondo = colores.azulPastel;
                  let colorTexto = colores.azulOscuro;

                  if (tipo.includes("correctivo")) {
                    colorFondo = colores.naranjaPastel;
                    colorTexto = colores.naranjaOscuro;
                  } else if (tipo.includes("preventivo")) {
                    colorFondo = colores.verdePastel;
                    colorTexto = colores.verdeOscuro;
                  }

                  // Aplicar estilos a cada celda
                  row.forEach((cell, j) => {
                    cell.fontSize = 8;
                    cell.margin = [2, 2, 2, 2];

                    if (j === 0) {
                      // ID
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 1) {
                      // Tipo
                      cell.fillColor = colorFondo;
                      cell.color = colorTexto;
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 2) {
                      // Código
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 3) {
                      // Nombre
                      cell.alignment = "left";
                    } else {
                      cell.alignment = "center";
                    }
                  });

                  // Añadir líneas zebra para mejor legibilidad
                  if (i % 2 === 0) {
                    row.forEach((cell, j) => {
                      if (j !== 1 && !cell.fillColor) {
                        cell.fillColor = "#f9f9f9";
                      }
                    });
                  }
                }
              });
            }

            // Añadir pie de página
            doc.footer = (currentPage, pageCount) => ({
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 8,
              margin: [0, 0, 0, 0],
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
              title: "Historial de Mantenimientos Completados",
              author: "CORDIAL SAC",
              subject: "Listado de Mantenimientos Completados",
            };
          },
          filename:
            "Historial_Mantenimientos_" +
            new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 2, 3, 4, 5, 6],
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
        $("#historial-table tbody").on("click", "tr", function () {
          const data = historialTable.row(this).data();
          if (data) {
            // Remover selección anterior
            $("#historial-table tbody tr").removeClass("selected");
            // Agregar selección a la fila actual
            $(this).addClass("selected");

            // Añadir animación al panel de detalles
            $("#historial-detalle").removeClass("loaded").addClass("loading");

            // Cargar detalles en el panel lateral con un pequeño retraso para la animación
            setTimeout(() => {
              cargarDetallesMantenimiento(data.id); // Solo necesitamos el ID del historial
              // Quitar clase de carga y añadir clase de cargado para la animación
              $("#historial-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        });

        // Inicializar eventos para imágenes en la tabla
        $("#historial-table").on(
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
      },
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

  // Función para cargar detalles del mantenimiento en el panel lateral
  function cargarDetallesMantenimiento(historial_id) {
    // Mostrar indicador de carga
    $("#historial-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#historial-detalle").addClass("active");

    // Obtener datos del historial
    $.ajax({
      url: getUrl("api/mantenimiento/historial/obtener.php"),
      type: "GET",
      data: { id: historial_id }, // Solo enviamos el ID del historial
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          mantenimientoSeleccionado = data;

          // Actualizar título del panel y añadir imagen en el encabezado
          const imagenUrl = data.imagen;
          const nombre = data.nombre_item || "";

          $("#historial-detalle .detail-header").html(`
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="detail-title">Mantenimiento ${capitalizarPrimeraLetra(
                  data.tipo_mantenimiento
                )}</h2>
                <p class="detail-subtitle">Completado: ${formatearFechaHora(
                  data.fecha_realizado || ""
                )}</p>
              </div>
              <div class="detail-header-image">
                <img src="${imagenUrl}" alt="${nombre}" class="detail-header-img" data-image-viewer="true">
              </div>
            </div>
          `);

          // Botón de generar reporte
          const botonReporte = `
            <div class="d-grid gap-2 mt-3">
              <button type="button" id="btn-generar-reporte-lateral" class="btn btn-danger" data-id="${data.mantenimiento_id}" data-tipo="${data.tipo_mantenimiento}">
                <i class="bi bi-file-pdf me-2"></i>Generar Informe
              </button>
            </div>
          `;

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
                        data.unidad_orometro === "km" ? "kilómetros" : "horas"
                      )}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Orómetro al Completar</label>
                      <div class="form-control form-control-sm bg-light">${formatearNumero(
                        data.orometro_realizado || 0
                      )} ${data.unidad_orometro || "hrs"}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          // Descripción y observaciones
          const infoDescripcion = `
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-chat-left-text me-2"></i>Descripción y Observaciones
              </div>
              <div class="card-form-body">
                <div class="form-group mb-2">
                  <label class="form-label form-label-sm">Descripción</label>
                  <div class="form-control form-control-sm bg-light" style="min-height: 60px;">${
                    data.descripcion || "No hay descripción disponible"
                  }</div>
                </div>
                <div class="form-group mb-2">
                  <label class="form-label form-label-sm">Observaciones</label>
                  <div class="form-control form-control-sm bg-light" style="min-height: 60px;">${
                    data.observaciones || "No hay observaciones disponibles"
                  }</div>
                </div>
              </div>
            </div>
          `;

          // Actualizar contenido del panel
          $("#historial-detalle .detail-content").html(`
            ${infoDescripcion}
            ${botonReporte}
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
          // Inicializar botón de generar reporte desde el panel
          $("#btn-generar-reporte-lateral").on("click", function () {
            const id = $(this).data("id");
            const tipo = $(this).data("tipo");
            if (id && tipo) {
              window.open(
                getUrl(
                  "api/mantenimiento/historial/generar_reporte.php?id=" +
                    id +
                    "&tipo=" +
                    tipo
                ),
                "_blank"
              );
            } else {
              if (window.showErrorToast) {
                window.showErrorToast("No se ha seleccionado un mantenimiento");
              }
            }
          });
        } else {
          // Mostrar mensaje de error
          $("#historial-detalle .detail-content").html(`
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
        $("#historial-detalle .detail-content").html(`
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

  // Función para ver detalles del mantenimiento en modal
  function verDetalleMantenimiento(historial_id) {
    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Cargando detalles del mantenimiento...");
    }

    // Obtener datos del historial
    $.ajax({
      url: getUrl("api/mantenimiento/historial/obtener.php"),
      type: "GET",
      data: { id: historial_id },
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;

          // Actualizar datos en el modal
          $("#detalle-nombre").text(data.nombre_item || "");
          $("#detalle-codigo").text(data.codigo_item || "Sin código");
          $("#detalle-tipo-item").text(
            capitalizarPrimeraLetra(data.tipo_item || "")
          );
          $("#detalle-tipo-orometro").text(
            capitalizarPrimeraLetra(
              data.unidad_orometro === "km" ? "kilómetros" : "horas"
            )
          );
          $("#detalle-fecha-problema").text(
            formatearFechaHora(data.fecha_problema || "")
          );
          $("#detalle-fecha-realizado").text(
            formatearFechaHora(data.fecha_realizado || "")
          );

          // Actualizar información de orómetro
          $("#detalle-orometro").text(
            formatearNumero(data.orometro_realizado || 0) +
              " " +
              (data.unidad_orometro || "hrs")
          );

          // Actualizar descripción y observaciones
          $("#detalle-descripcion").text(
            data.descripcion || "No hay descripción disponible"
          );
          $("#detalle-observaciones").text(
            data.observaciones || "No hay observaciones disponibles"
          );

          // Actualizar imagen
          const imageSrc = data.imagen;
          $("#detalle-imagen").attr("src", imageSrc);

          // Actualizar tipo de mantenimiento
          const tipoClases = {
            correctivo: "bg-warning text-dark",
            preventivo: "bg-success",
          };

          $("#detalle-tipo-mantenimiento").attr(
            "class",
            "badge rounded-pill " +
              (tipoClases[data.tipo_mantenimiento.toLowerCase()] ||
                "bg-secondary")
          );
          $("#detalle-tipo-mantenimiento").text(
            capitalizarPrimeraLetra(data.tipo_mantenimiento)
          );

          // Configurar botón de generar reporte (usar mantenimiento_id)
          $("#btn-generar-reporte")
            .data("id", data.mantenimiento_id)
            .data("tipo", data.tipo_mantenimiento);

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

  // Función para formatear fechas con hora
  function formatearFechaHora(fecha) {
    if (!fecha) return "-";
    const date = new Date(fecha);
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Función para formatear solo fechas
  function formatearFecha(fecha) {
    if (!fecha) return "-";
    const date = new Date(fecha);
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  }

  // Inicializar datepicker
  function initDatepicker() {
    const datepickers = $(".datepicker");
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
    const tipoMantenimiento = $("#filtro-tipo-mantenimiento").val();
    const tipoItem = $("#filtro-tipo-item").val();
    const fechaDesde = $("#filtro-fecha-desde").val();
    const fechaHasta = $("#filtro-fecha-hasta").val();

    // Actualizar filtros activos
    filtrosActivos = {};
    if (tipoMantenimiento)
      filtrosActivos.tipo_mantenimiento = tipoMantenimiento;
    if (tipoItem) filtrosActivos.tipo_item = tipoItem;
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
    historialTable.ajax.reload();

    // Limpiar panel de detalles
    $("#historial-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un mantenimiento para ver sus detalles
        </div>
      </div>
    `);
    $("#historial-detalle").removeClass("active");
    mantenimientoSeleccionado = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    // Restablecer valores de filtros
    $("#filtro-tipo-mantenimiento").val("");
    $("#filtro-tipo-item").val("");
    $("#filtro-fecha-desde").val("");
    $("#filtro-fecha-hasta").val("");

    // Limpiar filtros activos
    filtrosActivos = {};

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    // Recargar tabla
    historialTable.ajax.reload();

    // Limpiar panel de detalles
    $("#historial-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un mantenimiento para ver sus detalles
        </div>
      </div>
    `);
    $("#historial-detalle").removeClass("active");
    mantenimientoSeleccionado = null;
  }

  // Mostrar indicador de carga
  function showLoadingOverlay() {
    if (typeof window.showLoading === "function") {
      window.showLoading();
    }
  }

  // Ocultar indicador de carga
  function hideLoadingOverlay() {
    if (typeof window.hideLoading === "function") {
      window.hideLoading();
    }
  }

  // Inicializar componentes
  initDataTable();
  initDatepicker();

  // Event Listeners
  // Botón para ver detalles del mantenimiento
  $(document).on("click", ".btn-ver-mantenimiento", function (e) {
    e.stopPropagation();
    const id = $(this).data("id"); // ID del historial
    verDetalleMantenimiento(id);
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

  // Botón Generar Reporte desde modal
  $("#btn-generar-reporte").on("click", function () {
    const id = $(this).data("id"); // mantenimiento_id
    const tipo = $(this).data("tipo");
    if (id && tipo) {
      window.open(
        getUrl(
          "api/mantenimiento/historial/generar_reporte.php?id=" +
            id +
            "&tipo=" +
            tipo
        ),
        "_blank"
      );
    } else {
      if (window.showErrorToast) {
        window.showErrorToast("No se ha seleccionado un mantenimiento");
      }
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
    if (window.showSuccessToast) {
      try {
        window.showSuccessToast(msg, "info");
      } catch (e) {
        console.log("Info toast:", msg);
      }
    }
  };
}
