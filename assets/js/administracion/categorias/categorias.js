/**
 * Gestión de categorías
 * Funcionalidades para listar, crear, editar, ver detalles y eliminar categorías
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
if (!window.showLoading) window.showLoading = () => {};
if (!window.hideLoading) window.hideLoading = () => {};

document.addEventListener("DOMContentLoaded", () => {
  // Variables globales
  let categoriasTable;
  let categoriaActual = null;
  let filtrosActivos = {};
  let categoriaSeleccionada = null;

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
    categoriasTable = $("#categorias-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/administracion/categorias/listar.php"),
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
          data: "id",
          className: "align-middle text-center",
        },
        {
          data: "nombre",
          className: "align-middle",
        },
        {
          data: "descripcion",
          className: "align-middle",
          render: (data) => {
            return data && data.length > 50
              ? data.substring(0, 50) + "..."
              : data || "-";
          },
        },
        {
          data: "total_equipos",
          className: "align-middle text-center",
          render: (data) => {
            return `<span class="badge bg-primary">${data || 0}</span>`;
          },
        },
        {
          data: "creado_en",
          className: "align-middle text-center",
          render: (data) => {
            return data ? new Date(data).toLocaleDateString("es-ES") : "-";
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-categoria" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (tienePermiso("administracion.categorias.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-categoria" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }

            if (tienePermiso("administracion.categorias.eliminar")) {
              acciones += `<button type="button" class="btn-accion btn-eliminar-categoria" data-id="${data.id}" title="Eliminar"><i class="bi bi-trash"></i></button>`;
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
            columns: [0, 1, 2, 3, 4],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-primary",
          exportOptions: {
            columns: [0, 1, 2, 3, 4],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 8,
              color: "#333333",
            };

            const colores = {
              azulPastel: "#D4E6F1",
              azulOscuro: "#1A5276",
              azulPrimario: "#0055A4",
            };

            doc.content.unshift({
              text: "REPORTE DE CATEGORÍAS DE EQUIPOS",
              style: "header",
              alignment: "center",
              margin: [0, 0, 0, 20],
            });

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [
                "10%",
                "30%",
                "40%",
                "10%",
                "10%",
              ];

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
                  row.forEach((cell, j) => {
                    cell.fontSize = 8;
                    cell.margin = [2, 2, 2, 2];
                    if (j === 1 || j === 2) {
                      cell.alignment = "left";
                    } else {
                      cell.alignment = "center";
                    }
                  });

                  if (i % 2 === 0) {
                    row.forEach((cell) => {
                      cell.fillColor = "#f9f9f9";
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

            doc.styles = {
              header: {
                fontSize: 14,
                bold: true,
                color: colores.azulPrimario,
              },
            };

            doc.info = {
              title: "Reporte de Categorías",
              author: "CORDIAL SAC",
              subject: "Listado de Categorías de Equipos",
            };
          },
          filename:
            "Reporte_Categorias_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4],
          },
        },
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "Todos"],
      ],
      pageLength: 25,
      initComplete: () => {
        $("#categorias-table tbody").on("click", "tr", function () {
          const data = categoriasTable.row(this).data();
          if (data) {
            $("#categorias-table tbody tr").removeClass("selected");
            $(this).addClass("selected");
            $("#categoria-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesCategoria(data.id);
              $("#categoria-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
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

  // Función para cargar detalles de la categoría en el panel lateral
  function cargarDetallesCategoria(id) {
    $("#categoria-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#categoria-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/administracion/categorias/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const categoria = response.data;
          categoriaSeleccionada = categoria;

          $("#categoria-detalle .detail-header").html(`
            <div>
              <h2 class="detail-title">${categoria.nombre}</h2>
              <p class="detail-subtitle">ID: ${categoria.id}</p>
            </div>
          `);

          let equiposHtml = "";
          if (response.equipos && response.equipos.length > 0) {
            equiposHtml = `
              <div class="equipos-resumen-container mb-4">
                <h3 class="equipos-titulo">Equipos en esta Categoría</h3>
                <div class="text-center mb-3">
                  <div class="equipo-contador total">${response.equipos.length}</div>
                  <div class="equipo-etiqueta">Total de Equipos</div>
                </div>
              </div>
              <div class="equipos-lista-container">
                <h4 class="equipos-titulo">Lista de Equipos</h4>
                <div class="equipos-lista">
            `;

            response.equipos.forEach((equipo) => {
              const estadoClase =
                {
                  activo: "success",
                  mantenimiento: "warning",
                  averiado: "danger",
                  vendido: "secondary",
                  descanso: "info",
                }[equipo.estado.toLowerCase()] || "secondary";

              equiposHtml += `
                <div class="equipo-item mb-3 p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <strong>${equipo.codigo}</strong> - ${equipo.nombre}
                    </div>
                    <span class="badge bg-${estadoClase}">${
                equipo.estado
              }</span>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <small class="text-muted">Tipo: ${
                        equipo.tipo_equipo
                      }</small>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted">Ubicación: ${
                        equipo.ubicacion || "No especificada"
                      }</small>
                    </div>
                  </div>
                </div>
              `;
            });

            equiposHtml += `
                </div>
              </div>
            `;
          } else {
            equiposHtml = `
              <div class="equipos-resumen-container mb-4">
                <h3 class="equipos-titulo">Equipos en esta Categoría</h3>
                <div class="text-center py-3">
                  <div class="equipo-contador total">0</div>
                  <div class="equipo-etiqueta">No hay equipos en esta categoría</div>
                </div>
              </div>
            `;
          }

          $("#categoria-detalle .detail-content").html(equiposHtml);
        } else {
          $("#categoria-detalle .detail-content").html(`
            <div class="detail-empty">
              <div class="detail-empty-icon">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
              <div class="detail-empty-text">
                Error al cargar los detalles de la categoría
              </div>
            </div>
          `);
        }
      },
      error: (xhr, status, error) => {
        $("#categoria-detalle .detail-content").html(`
          <div class="detail-empty">
            <div class="detail-empty-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="detail-empty-text">
              Error de conexión al servidor
            </div>
          </div>
        `);
        console.error("Error al obtener detalles de la categoría:", error);
      },
    });
  }

  // Inicializar validación del formulario
  function initFormValidation() {
    $("#form-categoria").validate({
      rules: {
        nombre: {
          required: true,
          minlength: 2,
          maxlength: 50,
        },
        descripcion: {
          maxlength: 500,
        },
      },
      messages: {
        nombre: {
          required: "El nombre es obligatorio",
          minlength: "El nombre debe tener al menos 2 caracteres",
          maxlength: "El nombre no puede tener más de 50 caracteres",
        },
        descripcion: {
          maxlength: "La descripción no puede tener más de 500 caracteres",
        },
      },
      errorElement: "div",
      errorPlacement: (error, element) => {
        error.addClass("invalid-feedback");
        element.closest(".form-group").append(error);
      },
      highlight: (element) => {
        $(element).addClass("is-invalid");
      },
      unhighlight: (element) => {
        $(element).removeClass("is-invalid");
      },
      submitHandler: (form) => {
        guardarCategoria();
        return false;
      },
    });
  }

  // Abrir modal para crear nueva categoría
  function abrirModalCrear() {
    if (window.showInfoToast) {
      window.showInfoToast("Preparando formulario para nueva categoría...");
    }

    $("#form-categoria")[0].reset();
    $("#categoria-id").val("");
    $("#modal-categoria-titulo").text("Nueva Categoría");

    const modalCategoria = new bootstrap.Modal(
      document.getElementById("modal-categoria")
    );
    modalCategoria.show();

    $("#form-categoria").validate().resetForm();
    $("#form-categoria .is-invalid").removeClass("is-invalid");
    categoriaActual = null;
  }

  // Abrir modal para editar categoría
  function abrirModalEditar(id) {
    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast(
        "Cargando información de la categoría para editar..."
      );
    }

    $.ajax({
      url: getUrl("api/administracion/categorias/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const categoria = response.data;
          categoriaActual = categoria;

          $("#categoria-id").val(categoria.id);
          $("#categoria-nombre").val(categoria.nombre);
          $("#categoria-descripcion").val(categoria.descripcion);

          $("#modal-categoria-titulo").text("Editar Categoría");

          const modalCategoria = new bootstrap.Modal(
            document.getElementById("modal-categoria")
          );
          modalCategoria.show();

          $("#form-categoria").validate().resetForm();
          $("#form-categoria .is-invalid").removeClass("is-invalid");
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos de la categoría"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener categoría:", error);
      },
    });
  }

  // Guardar categoría (crear o actualizar)
  function guardarCategoria() {
    if (!$("#form-categoria").valid()) {
      return;
    }

    showLoadingOverlay();

    const formData = new FormData(document.getElementById("form-categoria"));

    $.ajax({
      url: getUrl("api/administracion/categorias/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          const modalCategoria = bootstrap.Modal.getInstance(
            document.getElementById("modal-categoria")
          );
          modalCategoria.hide();

          if (window.showSuccessToast) {
            window.showSuccessToast(response.message);
          }

          categoriasTable.ajax.reload(null, false);

          if (
            categoriaSeleccionada &&
            categoriaSeleccionada.id == response.id
          ) {
            $("#categoria-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesCategoria(response.id);
              $("#categoria-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar la categoría"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();

        let errorMessage = "Error de conexión al servidor";
        try {
          const response = JSON.parse(xhr.responseText);
          if (response && response.message) {
            errorMessage = response.message;
          }
        } catch (e) {
          console.error("Error al parsear respuesta:", e);
          console.log("Respuesta del servidor:", xhr.responseText);
        }

        if (window.showErrorToast) {
          window.showErrorToast(errorMessage);
        }
        console.error("Error al guardar categoría:", error);
      },
    });
  }

  // Ver detalle de categoría
  function verDetalleCategoria(id) {
    showLoadingOverlay();

    if (window.showInfoToast) {
      window.showInfoToast("Cargando detalles de la categoría...");
    }

    $.ajax({
      url: getUrl("api/administracion/categorias/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const categoria = response.data;
          categoriaActual = categoria;

          $("#detalle-nombre").text(categoria.nombre);
          $("#detalle-id").text(categoria.id || "-");
          $("#detalle-descripcion").text(categoria.descripcion || "-");
          $("#detalle-fecha").text(
            categoria.creado_en
              ? new Date(categoria.creado_en).toLocaleDateString("es-ES")
              : "-"
          );
          $("#detalle-total-equipos").text(response.total_equipos || 0);

          // Cargar equipos asociados
          if (response.equipos && response.equipos.length > 0) {
            $("#equipos-table").removeClass("d-none");
            $("#sin-equipos").addClass("d-none");

            $("#equipos-body").empty();

            response.equipos.forEach((equipo) => {
              const estadoClases = {
                activo: "bg-success",
                mantenimiento: "bg-warning text-dark",
                averiado: "bg-danger",
                vendido: "bg-secondary",
                descanso: "bg-info",
              };

              const row = `
                <tr>
                  <td>${equipo.codigo || "-"}</td>
                  <td>${equipo.nombre || "-"}</td>
                  <td>${equipo.tipo_equipo || "-"}</td>
                  <td><span class="badge rounded-pill ${
                    estadoClases[equipo.estado] || "bg-secondary"
                  }">${equipo.estado}</span></td>
                  <td>${equipo.ubicacion || "-"}</td>
                </tr>
              `;
              $("#equipos-body").append(row);
            });
          } else {
            $("#equipos-table").addClass("d-none");
            $("#sin-equipos").removeClass("d-none");
          }

          const modalDetalle = new bootstrap.Modal(
            document.getElementById("modal-detalle-categoria")
          );
          modalDetalle.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message ||
                "Error al obtener los detalles de la categoría"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener detalles de la categoría:", error);
      },
    });
  }

  // Eliminar categoría
  function eliminarCategoria(id) {
    const modalConfirmar = new bootstrap.Modal(
      document.getElementById("modal-confirmar-eliminar")
    );
    modalConfirmar.show();

    $("#btn-confirmar-eliminar")
      .off("click")
      .on("click", () => {
        modalConfirmar.hide();
        showLoadingOverlay();

        if (window.showInfoToast) {
          window.showInfoToast("Eliminando categoría...");
        }

        $.ajax({
          url: getUrl("api/administracion/categorias/eliminar.php"),
          type: "POST",
          data: { id: id },
          dataType: "json",
          success: (response) => {
            hideLoadingOverlay();

            if (response.success) {
              if (window.showSuccessToast) {
                window.showSuccessToast(response.message);
              }

              categoriasTable.ajax.reload();

              if (categoriaSeleccionada && categoriaSeleccionada.id == id) {
                $("#categoria-detalle .detail-content").html(`
                  <div class="detail-empty">
                    <div class="detail-empty-icon">
                      <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                      Seleccione una categoría para ver sus detalles
                    </div>
                  </div>
                `);
                categoriaSeleccionada = null;
              }
            } else {
              if (window.showErrorToast) {
                window.showErrorToast(response.message);
              }

              if (response.equipos && response.equipos.length > 0) {
                let equiposHtml = '<ul class="mb-0">';
                response.equipos.forEach((equipo) => {
                  equiposHtml += `<li>${equipo}</li>`;
                });
                equiposHtml += "</ul>";

                if (window.Swal) {
                  window.Swal.fire({
                    title: "No se puede eliminar",
                    html: `Esta categoría tiene equipos asociados:<br>${equiposHtml}`,
                    icon: "warning",
                    confirmButtonText: "Entendido",
                  });
                }
              }
            }
          },
          error: (xhr, status, error) => {
            hideLoadingOverlay();
            if (window.showErrorToast) {
              window.showErrorToast("Error de conexión al servidor");
            }
            console.error("Error al eliminar categoría:", error);
          },
        });
      });
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const buscar = $("#filtro-buscar").val();

    filtrosActivos = {};
    if (buscar) filtrosActivos.buscar = buscar;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    categoriasTable.ajax.reload();

    $("#categoria-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione una categoría para ver sus detalles
        </div>
      </div>
    `);
    $("#categoria-detalle").removeClass("active");
    categoriaSeleccionada = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-buscar").val("");
    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    categoriasTable.ajax.reload();

    $("#categoria-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione una categoría para ver sus detalles
        </div>
      </div>
    `);
    $("#categoria-detalle").removeClass("active");
    categoriaSeleccionada = null;
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

  // Verificar si el usuario tiene un permiso específico
  function tienePermiso(permiso) {
    if (typeof window.tienePermiso === "function") {
      return window.tienePermiso(permiso);
    }

    if (permiso === "administracion.categorias.crear") {
      return $("#btn-nueva-categoria").length > 0;
    } else if (permiso === "administracion.categorias.editar") {
      return $("#btn-editar-desde-detalle").length > 0;
    } else if (permiso === "administracion.categorias.eliminar") {
      return true;
    }

    return false;
  }

  // Inicializar componentes
  initDataTable();
  initFormValidation();

  // Event Listeners
  $("#btn-nueva-categoria").on("click", () => {
    abrirModalCrear();
  });

  $("#btn-guardar-categoria").on("click", () => {
    $("#form-categoria").submit();
  });

  $(document).on("click", ".btn-ver-categoria", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    verDetalleCategoria(id);
  });

  $(document).on("click", ".btn-editar-categoria", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    abrirModalEditar(id);
  });

  $(document).on("click", ".btn-eliminar-categoria", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    eliminarCategoria(id);
  });

  $("#btn-editar-desde-detalle").on("click", () => {
    const modalDetalle = bootstrap.Modal.getInstance(
      document.getElementById("modal-detalle-categoria")
    );
    modalDetalle.hide();

    if (categoriaActual && categoriaActual.id) {
      setTimeout(() => {
        abrirModalEditar(categoriaActual.id);
      }, 500);
    }
  });

  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros();
  });

  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros();
  });

  // Inicializar tooltips
  const tooltips = document.querySelectorAll("[title]");
  tooltips.forEach((tooltip) => {
    new bootstrap.Tooltip(tooltip);
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
