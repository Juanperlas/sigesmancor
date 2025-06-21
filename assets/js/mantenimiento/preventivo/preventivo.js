/**
 * Gestión de mantenimiento preventivo
 * Funcionalidades para listar, crear, completar y ver detalles de mantenimientos preventivos
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
if (!window.ImageUpload)
  window.ImageUpload = () => {
    console.log("Componente de carga de imágenes no disponible");
  };
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

document.addEventListener("DOMContentLoaded", () => {
  // Variables globales
  let mantenimientosTable;
  let mantenimientoActual = null;
  let mantenimientoSeleccionado = null;
  let filtrosActivos = {
    estado: "pendiente",
  };
  let imageUploader = null;
  let modalCompletar = null;

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

  // AGREGAR AQUÍ: Función para cargar historial de mantenimientos preventivos
  function cargarHistorialMantenimientos(itemId, tipoItem) {
    // Mostrar indicador de carga en la tabla de historial
    $("#historial-body").html(
      '<tr><td colspan="5" class="text-center">Cargando historial...</td></tr>'
    );

    // Enviar solicitud
    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/historial.php"),
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

  // Inicializar DataTable
  function initDataTable() {
    mantenimientosTable = $("#mantenimientos-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/mantenimiento/preventivo/listar.php"),
        type: "POST",
        data: (d) => {
          if (d.length == -1) {
            d.length = 10000;
          }
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
          data: "imagen",
          orderable: false,
          className: "text-center",
          render: (data, type, row) => {
            if (
              row.estado.toLowerCase() === "completado" &&
              row.imagen_equipo
            ) {
              return `<img src="${row.imagen_equipo}" class="mantenimiento-imagen-tabla" alt="Mantenimiento" data-image-viewer="true">`;
            }
            return `<img src="${data}" class="mantenimiento-imagen-tabla" alt="Mantenimiento" data-image-viewer="true">`;
          },
        },
        {
          data: "fecha_formateada",
          className: "align-middle",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return row.fecha_programada;
            }
            return data;
          },
        },
        {
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
          type: "string",
        },
        {
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
          data: "proximo_orometro",
          className: "align-middle text-center",
          render: (data, type, row) => {
            if (type === "sort" || type === "type") {
              return Number.parseFloat(row.proximo_orometro_valor || 0);
            }
            return data
              ? `<span class="orometro-valor text-danger fw-bold">${data}</span>`
              : "-";
          },
        },
        {
          data: "estado",
          className: "align-middle text-center",
          render: (data, type) => {
            if (type === "sort" || type === "type") {
              return data;
            }
            return `<span class="estado-badge estado-${data.toLowerCase()}">${capitalizarPrimeraLetra(
              data
            )}</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-mantenimiento" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (
              data.estado.toLowerCase() === "pendiente" &&
              tienePermiso("mantenimientos.preventivo.completar")
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
            columns: [1, 2, 3, 4, 5, 6],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-primary",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 8,
              color: "#333333",
            };

            const colores = {
              azulPastel: "#D4E6F1",
              verdePastel: "#D5F5E3",
              naranjaPastel: "#FAE5D3",
              rojoPastel: "#F5B7B1",
              grisPastel: "#EAECEE",
              celestePastel: "#EBF5FB",
              azulOscuro: "#1A5276",
              verdeOscuro: "#186A3B",
              naranjaOscuro: "#BA4A00",
              rojoOscuro: "#922B21",
              grisOscuro: "#424949",
              celesteOscuro: "#2874A6",
              azulPrimario: "#0055A4",
            };

            const logoBase64 = "TU_IMAGEN";

            doc.content.unshift(
              {
                columns: [
                  {
                    image: logoBase64,
                    width: 50,
                    margin: [5, 5, 0, 5],
                  },
                  {
                    text: "REPORTE DE MANTENIMIENTOS PREVENTIVOS",
                    style: "header",
                    alignment: "center",
                    margin: [0, 15, 0, 0],
                  },
                  {
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

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [40, 40, 40, 50, 50, 50];

              doc.content[tableIndex].table.body.forEach((row, i) => {
                if (i === 0) {
                  row.forEach((cell) => {
                    cell.fillColor = colores.azulPastel;
                    cell.color = colores.azulOscuro;
                    cell.fontSize = 9;
                    cell.bold = true;
                    cell.alignment = "center";
                    cell.margin = [2, 3, 2, 3];
                  });
                } else {
                  const estado = row[5].text.toString().toLowerCase();

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

                  row.forEach((cell, j) => {
                    cell.fontSize = 8;
                    cell.margin = [2, 2, 2, 2];

                    if (j === 2) {
                      cell.alignment = "left";
                    } else if (j === 5) {
                      cell.fillColor = colorFondo;
                      cell.color = colorTexto;
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 3) {
                      cell.bold = true;
                      cell.alignment = "center";
                    } else {
                      cell.alignment = "center";
                    }
                  });

                  if (i % 2 === 0) {
                    row.forEach((cell, j) => {
                      if (j !== 5 && !cell.fillColor) {
                        cell.fillColor = "#f9f9f9";
                      }
                    });
                  }
                }
              });
            }

            doc.footer = (currentPage, pageCount) => ({
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 8,
              margin: [0, 0, 0, 0],
            });

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

            doc.info = {
              title: "Reporte de Mantenimientos Preventivos",
              author: "CORDIAL SAC",
              subject: "Listado de Mantenimientos Preventivos",
            };
          },
          filename:
            "Reporte_Mantenimientos_Preventivos_" +
            new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6],
          },
        },
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "Todos"],
      ],
      pageLength: 25,
      initComplete: () => {
        $("#mantenimientos-table tbody").on("click", "tr", function () {
          const data = mantenimientosTable.row(this).data();
          if (data) {
            $("#mantenimientos-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#mantenimiento-detalle")
              .removeClass("loaded")
              .addClass("loading");

            setTimeout(() => {
              cargarDetallesMantenimiento(data.id);
              $("#mantenimiento-detalle")
                .removeClass("loading")
                .addClass("loaded");
            }, 300);
          }
        });

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

        verificarMantenimientos();

        $("#btn-actualizar-fechas").on("click", () => {
          showLoadingOverlay();

          if (window.showInfoToast) {
            window.showInfoToast("Actualizando fechas de mantenimientos...");
          }

          $.ajax({
            url: getUrl("api/mantenimiento/preventivo/actualizar-fechas.php"),
            type: "GET",
            dataType: "json",
            success: (response) => {
              hideLoadingOverlay();

              if (response.success) {
                if (window.showSuccessToast) {
                  window.showSuccessToast(
                    response.message || "Fechas actualizadas correctamente"
                  );
                }
                mantenimientosTable.ajax.reload();
              } else {
                if (window.showErrorToast) {
                  window.showErrorToast(
                    response.message || "Error al actualizar fechas"
                  );
                }
              }
            },
            error: (xhr, status, error) => {
              hideLoadingOverlay();
              if (window.showErrorToast) {
                window.showErrorToast("Error de conexión al servidor");
              }
              console.error("Error al actualizar fechas:", error);
            },
          });
        });
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
  function cargarDetallesMantenimiento(id) {
    $("#mantenimiento-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#mantenimiento-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          mantenimientoSeleccionado = data;

          const mantenimiento = data.mantenimiento || {};
          const tipo = data.tipo || "";
          const equipo = data.equipo || {};
          const componente = data.componente || {};
          const unidad = data.unidad_orometro || "hrs";

          const estado = mantenimiento.estado || "pendiente";
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const proximoOrometro = Number.parseFloat(data.proximo_orometro || 0);
          const anteriorOrometro = Number.parseFloat(
            data.anterior_orometro || 0
          );

          const imagenUrl = data.imagen;
          const nombre = equipo.nombre || componente.nombre || "";

          $("#mantenimiento-detalle .detail-header").html(`
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="detail-title">Mantenimiento ${capitalizarPrimeraLetra(
                tipo
              )}</h2>
              <p class="detail-subtitle">Fecha: ${formatearFecha(
                mantenimiento.fecha_programada || ""
              )}</p>
            </div>
            <div class="detail-header-image">
              <img src="${imagenUrl}" alt="${nombre}" class="detail-header-img" data-image-viewer="true">
            </div>
          </div>
        `);

          let tiempoRestante = "";
          let porcentajeAvance = 0;
          let colorClase = "success";

          if (proximoOrometro > 0 && orometroActual > 0) {
            const restante = proximoOrometro - orometroActual;
            const total = proximoOrometro - anteriorOrometro;
            const avance = orometroActual - anteriorOrometro;

            if (total > 0) {
              porcentajeAvance = Math.min(
                Math.max((avance / total) * 100, 0),
                100
              );
            }

            if (data.dias_restantes <= 3) {
              colorClase = "danger";
            } else if (data.dias_restantes <= 7) {
              colorClase = "warning";
            }

            if (restante > 0) {
              tiempoRestante = `
                <div class="tiempo-restante-container text-center mb-4">
                  <h3 class="tiempo-restante-titulo">Tiempo para mantenimiento</h3>
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
                      anteriorOrometro
                    )} ${unidad} <br><span class="text-muted">(Anterior)</span></small>
                    <small>${formatearNumero(
                      orometroActual
                    )} ${unidad} <br><span class="text-muted">(Actual)</span></small>
                    <small>${formatearNumero(
                      proximoOrometro
                    )} ${unidad} <br><span class="text-muted">(Programado)</span></small>
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
                      anteriorOrometro
                    )} ${unidad} <br><span class="text-muted">(Anterior)</span></small>
                    <small>${formatearNumero(
                      orometroActual
                    )} ${unidad} <br><span class="text-muted">(Actual)</span></small>
                    <small>${formatearNumero(
                      proximoOrometro
                    )} ${unidad} <br><span class="text-muted">(Programado)</span></small>
                  </div>
                </div>
              `;
            }
          }

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

          let botonCompletar = "";
          if (
            estado.toLowerCase() === "pendiente" &&
            tienePermiso("mantenimientos.preventivo.completar")
          ) {
            botonCompletar = `
              <div class="d-grid gap-2 mt-3">
                <button type="button" id="btn-completar-panel" class="btn btn-success" data-id="${mantenimiento.id}" data-tipo="${tipo}">
                  <i class="bi bi-check-circle me-2"></i>Completar Mantenimiento
                </button>
              </div>
            `;
          }

          $("#mantenimiento-detalle .detail-content").html(`
            ${tiempoRestante}
            ${infoCompletado}
            ${botonCompletar}
          `);

          $(".detail-header-img").on("click", function () {
            if (window.imageViewer) {
              window.imageViewer.show(
                $(this).attr("src"),
                "Imagen del mantenimiento"
              );
            }
          });

          $("#btn-completar-panel").on("click", function () {
            const id = $(this).data("id");
            const tipo = $(this).data("tipo");
            abrirModalCompletar(id, tipo);
          });
        } else {
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

  function capitalizarPrimeraLetra(string) {
    if (!string) return "";
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  }

  function formatearNumero(numero) {
    if (numero === null || numero === undefined) return "0.00";
    return Number.parseFloat(numero)
      .toFixed(2)
      .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function formatearFecha(fecha) {
    if (!fecha) return "-";
    const date = new Date(fecha);
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  }

  function verificarMantenimientos() {
    $("#verificacion-container").show();
    $("#verificacion-resultados").hide();
    $("#todo-actualizado").hide();

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/verificar.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        $("#verificacion-container").hide();

        if (response.success) {
          const totalFaltantes =
            response.equipos_faltantes + response.componentes_faltantes;
          if (totalFaltantes > 0) {
            $("#verificacion-mensaje").text(
              `Se encontraron ${totalFaltantes} equipos/componentes sin mantenimientos preventivos programados.`
            );
            $("#verificacion-resultados").show();
          } else {
            $("#todo-actualizado").show();
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al verificar mantenimientos"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        $("#verificacion-container").hide();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor: " + error);
        }
        console.error("Error al verificar mantenimientos:", error);
      },
    });
  }

  function crearMantenimientosFaltantes() {
    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast("Creando mantenimientos preventivos...");
    }

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/generar.php"),
      type: "POST",
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Mantenimientos creados correctamente"
            );
          }

          $("#verificacion-resultados").hide();
          $("#todo-actualizado").show();
          mantenimientosTable.ajax.reload();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al crear mantenimientos"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al crear mantenimientos:", error);
      },
    });
  }

  function abrirModalCompletar(id, tipo) {
    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast("Cargando información del mantenimiento...");
    }

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;
          mantenimientoActual = data;

          const mantenimiento = data.mantenimiento || {};
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const unidad = data.unidad_orometro || "hrs";

          $("#completar-id").val(mantenimiento.id || id);
          $("#completar-tipo").val(data.tipo || tipo);
          $("#completar-orometro-actual").val(orometroActual.toFixed(2));
          $("#completar-unidad-orometro").text(unidad);

          const hoy = new Date();
          const fechaFormateada = hoy.toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          });
          $("#completar-fecha").val(fechaFormateada);

          $("#container-mantenimiento-imagen").empty();

          try {
            const existingImage =
              mantenimiento.imagen && mantenimiento.imagen !== ""
                ? getUrl(mantenimiento.imagen)
                : "";

            const uploadPath = "assets/img/mantenimiento/preventivo/";

            if (imageUploader && typeof imageUploader.destroy === "function") {
              imageUploader.destroy();
            }

            imageUploader = new ImageUpload("container-mantenimiento-imagen", {
              maxSize: 2 * 1024 * 1024,
              acceptedTypes: [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
              ],
              inputName: "imagen",
              defaultImage: getUrl(
                "assets/img/mantenimiento/preventivo/default.png"
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
            });

            console.log("ImageUpload inicializado correctamente");
            const inputElement = document.getElementById(
              "input-container-mantenimiento-imagen"
            );
            console.log("Input de imagen:", inputElement);
          } catch (e) {
            console.error(
              "Error al inicializar el componente de carga de imágenes:",
              e
            );
            if (window.showErrorToast) {
              window.showErrorToast("Error al configurar la carga de imágenes");
            }
          }

          modalCompletar = new bootstrap.Modal(
            document.getElementById("modal-completar")
          );
          modalCompletar.show();

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

  function completarMantenimiento() {
    if (!validarFormularioCompletar()) {
      return;
    }

    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast("Completando mantenimiento...");
    }

    const formData = new FormData();
    formData.append("id", $("#completar-id").val());
    formData.append("orometro_actual", $("#completar-orometro-actual").val());
    formData.append("observaciones", $("#completar-observaciones").val());

    const imagenInput = document.getElementById(
      "input-container-mantenimiento-imagen"
    );

    console.log("Buscando input de imagen:", imagenInput);

    if (imagenInput && imagenInput.files && imagenInput.files.length > 0) {
      const file = imagenInput.files[0];
      formData.append("imagen", file);
      console.log(
        "Imagen seleccionada:",
        file.name,
        "Tamaño:",
        file.size,
        "Tipo:",
        file.type
      );
    } else {
      console.log("No se seleccionó ninguna imagen");
      console.log("Estado del input:", imagenInput ? "Existe" : "No existe");
      if (imagenInput) {
        console.log(
          "Files:",
          imagenInput.files
            ? `${imagenInput.files.length} archivos`
            : "No hay files"
        );
      }

      const allFileInputs = document.querySelectorAll('input[type="file"]');
      console.log("Todos los inputs de tipo file:", allFileInputs.length);
      allFileInputs.forEach((input, index) => {
        console.log(
          `Input ${index}:`,
          input.id,
          input.name,
          input.files ? input.files.length : 0
        );
      });
    }

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/completar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (modalCompletar) {
            modalCompletar.hide();
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Mantenimiento completado correctamente"
            );
          }

          mantenimientosTable.ajax.reload();

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

  function validarFormularioCompletar() {
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

  // MODIFICAR ESTA FUNCIÓN: Agregar llamada al historial
  function verDetalleMantenimiento(id) {
    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast("Cargando detalles del mantenimiento...");
    }

    $.ajax({
      url: getUrl("api/mantenimiento/preventivo/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;
          mantenimientoActual = data;

          const mantenimiento = data.mantenimiento || {};
          const tipo = data.tipo || "";
          const equipo = data.equipo || {};
          const componente = data.componente || {};
          const unidad = data.unidad_orometro || "hrs";

          const estado = mantenimiento.estado || "pendiente";
          const orometroActual = Number.parseFloat(data.orometro_actual || 0);
          const proximoOrometro = Number.parseFloat(data.proximo_orometro || 0);
          const anteriorOrometro = Number.parseFloat(
            data.anterior_orometro || 0
          );
          const nombre = equipo.nombre || componente.nombre || "";
          const codigo = equipo.codigo || componente.codigo || "";

          $("#detalle-nombre").text(nombre);
          $("#detalle-codigo").text(codigo || "-");
          $("#detalle-tipo").text(capitalizarPrimeraLetra(tipo) || "-");
          $("#detalle-tipo-orometro").text(
            capitalizarPrimeraLetra(unidad === "km" ? "kilómetros" : "horas") ||
              "-"
          );
          $("#detalle-fecha-programada").text(
            formatearFecha(mantenimiento.fecha_programada || "-")
          );
          $("#detalle-fecha-realizado").text(
            mantenimiento.fecha_realizado
              ? formatearFecha(mantenimiento.fecha_realizado)
              : "-"
          );

          $("#detalle-orometro-anterior").text(
            formatearNumero(anteriorOrometro) + " " + unidad
          );
          $("#detalle-orometro").text(
            formatearNumero(orometroActual) + " " + unidad
          );
          $("#detalle-proximo-orometro").text(
            formatearNumero(proximoOrometro) + " " + unidad
          );
          $("#detalle-limite").text(
            equipo.limite || componente.limite
              ? formatearNumero(equipo.limite || componente.limite) +
                  " " +
                  unidad
              : "-"
          );
          $("#detalle-mantenimiento").text(
            equipo.mantenimiento || componente.mantenimiento
              ? formatearNumero(
                  equipo.mantenimiento || componente.mantenimiento
                ) +
                  " " +
                  unidad
              : "-"
          );

          $("#detalle-descripcion").text(
            mantenimiento.descripcion_razon || "-"
          );
          $("#detalle-observaciones").text(mantenimiento.observaciones || "-");

          const imageSrc = data.imagen;
          $("#detalle-imagen").attr("src", imageSrc);

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

          let porcentaje = 0;
          let colorClase = "success";

          if (proximoOrometro > anteriorOrometro) {
            const total = proximoOrometro - anteriorOrometro;
            const avance = orometroActual - anteriorOrometro;

            if (total > 0) {
              porcentaje = Math.min(Math.max((avance / total) * 100, 0), 100);
            }

            if (data.dias_restantes <= 3) {
              colorClase = "danger";
            } else if (data.dias_restantes <= 7) {
              colorClase = "warning";
            }
          }

          $("#detalle-progreso").attr("class", `progress-bar bg-${colorClase}`);
          $("#detalle-progreso").css("width", `${porcentaje}%`);
          $("#detalle-progreso").attr("aria-valuenow", porcentaje);

          $("#detalle-progreso-anterior").text(
            `${formatearNumero(anteriorOrometro)} ${unidad}`
          );
          $("#detalle-progreso-actual").text(
            `${formatearNumero(orometroActual)} ${unidad}`
          );
          $("#detalle-progreso-proximo").text(
            `${formatearNumero(proximoOrometro)} ${unidad}`
          );

          if (
            estado.toLowerCase() === "pendiente" &&
            tienePermiso("mantenimientos.preventivo.completar")
          ) {
            $("#btn-completar-desde-detalle").show();
          } else {
            $("#btn-completar-desde-detalle").hide();
          }

          // AQUÍ ES DONDE LLAMAS AL HISTORIAL DE PREVENTIVOS
          cargarHistorialMantenimientos(
            tipo === "equipo" ? equipo.id : componente.id,
            tipo
          );

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
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener detalles del mantenimiento:", error);
      },
    });
  }

  function initImageUploader(existingImage = "") {
    const container = document.getElementById("container-mantenimiento-imagen");
    if (!container) return;

    try {
      if (document.getElementById("input-mantenimiento-imagen")) {
        document.getElementById("input-mantenimiento-imagen").value = "";
      }

      imageUploader = new ImageUpload("container-mantenimiento-imagen", {
        maxSize: 2 * 1024 * 1024,
        acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        inputName: "imagen",
        defaultImage: getUrl("assets/img/mantenimiento/preventivo/default.png"),
        existingImage: existingImage,
        uploadPath: "assets/img/mantenimiento/preventivo/",
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

      console.log("ImageUpload inicializado correctamente");
      console.log(
        "Input de archivo:",
        document.getElementById("input-mantenimiento-imagen")
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
  }

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

  function aplicarFiltros() {
    const estado = $("#filtro-estado").val();
    const tipo = $("#filtro-tipo").val();
    const fechaDesde = $("#filtro-fecha-desde").val();
    const fechaHasta = $("#filtro-fecha-hasta").val();

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

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    mantenimientosTable.ajax.reload();

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

  function limpiarFiltros() {
    $("#filtro-estado").val("pendiente");
    $("#filtro-tipo").val("");
    $("#filtro-fecha-desde").val("");
    $("#filtro-fecha-hasta").val("");

    filtrosActivos = {
      estado: "pendiente",
    };

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    mantenimientosTable.ajax.reload();

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

  function showLoadingOverlay() {
    if (typeof window.showLoading === "function") {
      window.showLoading();
    }
  }

  function hideLoadingOverlay() {
    if (typeof window.hideLoading === "function") {
      window.hideLoading();
    }
  }

  function tienePermiso(permiso) {
    if (typeof window.tienePermiso === "function") {
      return window.tienePermiso(permiso);
    }

    if (permiso === "mantenimientos.preventivo.completar") {
      return $("#btn-completar-desde-detalle").length > 0;
    }

    return true;
  }

  // Inicializar componentes
  initDataTable();
  initDatepicker();

  // Event Listeners
  $("#btn-verificar-mantenimientos").on("click", () => {
    verificarMantenimientos();
  });

  $("#btn-crear-faltantes").on("click", () => {
    crearMantenimientosFaltantes();
  });

  $("#btn-ignorar-faltantes").on("click", () => {
    $("#verificacion-resultados").hide();
  });

  $("#btn-guardar-completar").on("click", () => {
    completarMantenimiento();
  });

  $(document).on("click", ".btn-ver-mantenimiento", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    verDetalleMantenimiento(id);
  });

  $(document).on("click", ".btn-completar-mantenimiento", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    const tipo = $(this).data("tipo");
    abrirModalCompletar(id, tipo);
  });

  $("#btn-completar-desde-detalle").on("click", () => {
    const modalDetalle = bootstrap.Modal.getInstance(
      document.getElementById("modal-detalle-mantenimiento")
    );
    modalDetalle.hide();

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

  $("#btn-generar-reporte").on("click", () => {
    if (
      mantenimientoActual &&
      mantenimientoActual.mantenimiento &&
      mantenimientoActual.mantenimiento.id
    ) {
      window.open(
        getUrl(
          "api/mantenimiento/preventivo/generar_reporte.php?id=" +
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

  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros();
  });

  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros();
  });

  const tooltips = document.querySelectorAll("[title]");
  tooltips.forEach((tooltip) => {
    try {
      new bootstrap.Tooltip(tooltip);
    } catch (e) {
      console.warn("Error al inicializar tooltip:", e);
    }
  });
});

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

let modalCompletar;
let mantenimientosTable;
let mantenimientoSeleccionado;
let cargarDetallesMantenimiento;
let mantenimientoActual;
let imageUploader;
