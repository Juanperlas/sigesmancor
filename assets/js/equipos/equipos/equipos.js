/**
 * Gestión de equipos
 * Funcionalidades para listar, crear, editar, ver detalles y eliminar equipos
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
  let equiposTable;
  let equipoActual = null;
  let filtrosActivos = {};
  let imageUploader = null;
  let equipoSeleccionado = null;

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
    equiposTable = $("#equipos-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/equipos/equipos/listar.php"),
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
          data: "imagen",
          orderable: false,
          className: "text-center",
          render: (data, type, row) => {
            const imgUrl = data
              ? data
              : getUrl("assets/img/equipos/equipos/default.png");
            return `<img src="${imgUrl}" class="equipo-imagen-tabla" alt="${
              row.nombre || "Equipo"
            }" data-image-viewer="true">`;
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
          data: "categoria_nombre", // Nueva columna de categoría
          className: "align-middle text-center",
          render: (data, type) => {
            // Para ordenamiento, devolver el valor sin formato
            if (type === "sort" || type === "type") {
              return data || "";
            }
            return `<span class="categoria-badge">${
              data || "Sin categoría"
            }</span>`;
          },
        },
        {
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
          data: "anterior_orometro",
          className: "align-middle text-center",
          render: (data, type, row) => {
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
          render: (data, type, row) => {
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
          render: (data, type, row) => {
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
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-equipo" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (tienePermiso("equipos.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-equipo" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }

            if (tienePermiso("equipos.eliminar")) {
              acciones += `<button type="button" class="btn-accion btn-eliminar-equipo" data-id="${data.id}" title="Eliminar"><i class="bi bi-trash"></i></button>`;
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
            columns: [1, 2, 3, 4, 5, 6, 7], // Incluir la nueva columna de categoría
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6, 7], // Incluir la nueva columna de categoría
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-primary",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6, 7], // Incluir la nueva columna de categoría
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
            const logoBase64 = "MI_IMAGEN_BASE64";

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
                    text: "FORMATO REGISTRO DE EQUIPOS Y HERRAMIENTAS",
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
              // Configurar anchos de columnas proporcionales (actualizado para incluir categoría)
              doc.content[tableIndex].table.widths = [
                40, // Código
                "*", // Nombre
                60, // Categoría
                70, // Estado - Más ancho para que quepa "Mantenimiento"
                50, // Tipo Orómetro
                50, // Anterior
                50, // Actual
                50, // Próximo
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
                  const estado = row[4].text.toString().toLowerCase(); // Columna 'estado' (ahora es la 4ta)

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
                      // Categoría
                      cell.alignment = "center";
                    } else if (j === 4) {
                      // Estado
                      cell.fillColor = colorFondo;
                      cell.color = colorTexto;
                      cell.alignment = "center";
                      cell.bold = true;
                    } else if (j === 6) {
                      // Orómetro Actual
                      cell.bold = true; // Ponemos en negrita el orómetro actual
                      cell.alignment = "center";
                    } else if (j >= 5) {
                      // Datos numéricos
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
                  text: "JEFE DE SEGURIDAD",
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
              title: "Reporte de Equipos",
              author: "CORDIAL SAC",
              subject: "Listado de Equipos",
            };
          },
          filename: "Reporte_Equipos_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [1, 2, 3, 4, 5, 6, 7], // Incluir la nueva columna de categoría
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
        $("#equipos-table tbody").on("click", "tr", function () {
          const data = equiposTable.row(this).data();
          if (data) {
            // Remover selección anterior
            $("#equipos-table tbody tr").removeClass("selected");
            // Agregar selección a la fila actual
            $(this).addClass("selected");

            // Añadir animación al panel de detalles
            $("#equipo-detalle").removeClass("loaded").addClass("loading");

            // Cargar detalles en el panel lateral con un pequeño retraso para la animación
            setTimeout(() => {
              cargarDetallesEquipo(data.id);
              // Quitar clase de carga y añadir clase de cargado para la animación
              $("#equipo-detalle").removeClass("loading").addClass("loaded");
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

  // Aplicar filtros (actualizado para incluir categoría)
  function aplicarFiltros() {
    // Obtener valores de filtros
    const estado = $("#filtro-estado").val();
    const tipoOrometro = $("#filtro-tipo-orometro").val();
    const categoria = $("#filtro-categoria").val(); // Nuevo filtro de categoría

    // Actualizar filtros activos
    filtrosActivos = {};
    if (estado) filtrosActivos.estado = estado;
    if (tipoOrometro) filtrosActivos.tipo_orometro = tipoOrometro;
    if (categoria) filtrosActivos.categoria = categoria; // Agregar filtro de categoría

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    // Recargar tabla
    equiposTable.ajax.reload();

    // Limpiar panel de detalles
    $("#equipo-detalle .detail-content").html(`
    <div class="detail-empty">
      <div class="detail-empty-icon">
        <i class="bi bi-info-circle"></i>
      </div>
      <div class="detail-empty-text">
        Seleccione un equipo para ver sus detalles
      </div>
    </div>
  `);
    $("#equipo-detalle").removeClass("active");
    equipoSeleccionado = null;
  }

  // Limpiar filtros (actualizado para incluir categoría)
  function limpiarFiltros() {
    // Restablecer valores de filtros
    $("#filtro-estado").val("");
    $("#filtro-tipo-orometro").val("");
    $("#filtro-categoria").val(""); // Limpiar filtro de categoría

    // Limpiar filtros activos
    filtrosActivos = {};

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    // Recargar tabla
    equiposTable.ajax.reload();

    // Limpiar panel de detalles
    $("#equipo-detalle .detail-content").html(`
    <div class="detail-empty">
      <div class="detail-empty-icon">
        <i class="bi bi-info-circle"></i>
      </div>
      <div class="detail-empty-text">
        Seleccione un equipo para ver sus detalles
      </div>
    </div>
  `);
    $("#equipo-detalle").removeClass("active");
    equipoSeleccionado = null;
  }

  // Resto de las funciones permanecen igual...
  // [Aquí van todas las demás funciones que ya tenías: cargarDetallesEquipo, capitalizarPrimeraLetra, formatearNumero, etc.]

  // Función para cargar detalles del equipo en el panel lateral
  function cargarDetallesEquipo(id) {
    // Mostrar indicador de carga
    $("#equipo-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#equipo-detalle").addClass("active");

    // Obtener datos del equipo
    $.ajax({
      url: getUrl("api/equipos/equipos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const equipo = response.data;
          equipoSeleccionado = equipo;
          const unidad = getUnidadOrometro(equipo.tipo_orometro);

          // Actualizar título del panel y añadir imagen en el encabezado
          const imagenUrl =
            equipo.imagen || getUrl("assets/img/equipos/equipos/default.png");

          // Actualizar el encabezado con la imagen
          $("#equipo-detalle .detail-header").html(`
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h2 class="detail-title">${equipo.nombre}</h2>
              <p class="detail-subtitle">Código: ${equipo.codigo}</p>
            </div>
            <div class="detail-header-image">
              <img src="${imagenUrl}" alt="${equipo.nombre}" class="detail-header-img" data-image-viewer="true">
            </div>
          </div>
        `);

          // Calcular tiempo restante para mantenimiento
          let tiempoRestante = "";
          let tiempoRestanteValor = 0;
          let porcentajeAvance = 0;
          let colorClase = "success";

          if (equipo.proximo_orometro && equipo.orometro_actual) {
            const restante =
              Number.parseFloat(equipo.proximo_orometro) -
              Number.parseFloat(equipo.orometro_actual);
            tiempoRestanteValor = restante;

            // Calcular porcentaje de avance
            const anterior = Number.parseFloat(equipo.anterior_orometro || 0);
            const total = Number.parseFloat(equipo.proximo_orometro) - anterior;
            const avance = Number.parseFloat(equipo.orometro_actual) - anterior;
            porcentajeAvance = Math.min(
              Math.max((avance / total) * 100, 0),
              100
            );

            // Determinar color según proximidad
            if (equipo.notificacion) {
              const notificacion = Number.parseFloat(equipo.notificacion);
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
                    <small class="clleva">${formatearNumero(
                      anterior
                    )} ${unidad} <br><span class="text-muted">(Lleva ${formatearNumero(
                Number.parseFloat(equipo.orometro_actual) - anterior
              )} ${unidad})</span></small>
                    <small class="cfalta">${formatearNumero(
                      equipo.proximo_orometro
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
                      Number.parseFloat(equipo.orometro_actual)
                    )} ${unidad} <br><span class="text-muted">(Lleva ${formatearNumero(
                Number.parseFloat(equipo.orometro_actual) - anterior
              )} ${unidad})</span></small>
                    <small>${formatearNumero(
                      equipo.proximo_orometro
                    )} ${unidad} <br><span class="text-muted">(Excedido)</span></small>
                  </div>
                </div>
              `;
            }
          }

          // Procesar componentes que ya vienen en la respuesta
          let componentesHtml = "";

          // 2. Sección de resumen de componentes
          let totalComponentes = 0;
          let componentesActivos = 0;
          let componentesMantenimiento = 0;
          let componentesAveriados = 0;
          let componentesOtros = 0;

          if (response.componentes && response.componentes.length > 0) {
            const componentes = response.componentes;
            totalComponentes = componentes.length;

            // Contar componentes por estado
            componentes.forEach((comp) => {
              if (comp.estado.toLowerCase() === "activo") componentesActivos++;
              else if (comp.estado.toLowerCase() === "mantenimiento")
                componentesMantenimiento++;
              else if (comp.estado.toLowerCase() === "averiado")
                componentesAveriados++;
              else componentesOtros++;
            });

            // Generar HTML de resumen de componentes
            componentesHtml += `
            <div class="componentes-resumen-container mb-4">
              <h3 class="componentes-titulo">Resumen de Componentes</h3>
              <div class="row text-center">
                <div class="col">
                  <div class="componente-contador total">${totalComponentes}</div>
                  <div class="componente-etiqueta">Total</div>
                </div>
                <div class="col">
                  <div class="componente-contador activos">${componentesActivos}</div>
                  <div class="componente-etiqueta">Activos</div>
                </div>
                <div class="col">
                  <div class="componente-contador mantenimiento">${componentesMantenimiento}</div>
                  <div class="componente-etiqueta">Mantenimiento</div>
                </div>
                <div class="col">
                  <div class="componente-contador averiados">${componentesAveriados}</div>
                  <div class="componente-etiqueta">Averiados</div>
                </div>
              </div>
            </div>
          `;

            // 3. Lista de componentes con tiempo restante
            componentesHtml += `
            <div class="componentes-lista-container">
              <h3 class="componentes-titulo">Estado de Mantenimiento de Componentes</h3>
              <div class="componentes-lista">
          `;

            // Generar lista de componentes
            componentes.forEach((comp) => {
              const compUnidad = getUnidadOrometro(comp.tipo_orometro);
              let compColorClase = "success";
              let compPorcentaje = 0;
              let compRestante = 0;

              // Calcular tiempo restante y porcentaje para cada componente
              if (comp.proximo_orometro && comp.orometro_actual) {
                const anterior = Number.parseFloat(comp.anterior_orometro || 0);
                const actual = Number.parseFloat(comp.orometro_actual);
                const proximo = Number.parseFloat(comp.proximo_orometro);
                compRestante = proximo - actual;

                // Calcular porcentaje
                if (proximo > anterior) {
                  const total = proximo - anterior;
                  const avance = actual - anterior;
                  compPorcentaje = Math.min(
                    Math.max((avance / total) * 100, 0),
                    100
                  );
                }

                // Determinar color
                if (comp.notificacion) {
                  const notificacion = Number.parseFloat(comp.notificacion);
                  if (compRestante <= notificacion / 2) {
                    compColorClase = "danger";
                  } else if (compRestante <= notificacion) {
                    compColorClase = "warning";
                  }
                }
              }

              // Generar HTML para cada componente
              componentesHtml += `
              <div class="componente-item mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div>
                    <strong>${comp.codigo}</strong> - ${comp.nombre}
                  </div>
                  <div class="text-${compColorClase}">
                    ${
                      compRestante > 0
                        ? formatearNumero(compRestante) + " " + compUnidad
                        : "Mantenimiento requerido"
                    }
                  </div>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-${compColorClase}" role="progressbar" 
                       style="width: ${compPorcentaje}%" 
                       aria-valuenow="${compPorcentaje}" 
                       aria-valuemin="0" 
                       aria-valuemax="100"></div>
                </div>
              </div>
            `;
            });

            componentesHtml += `
              </div>
            </div>
          `;
          } else {
            // No hay componentes
            componentesHtml += `
            <div class="componentes-resumen-container mb-4">
              <h3 class="componentes-titulo">Resumen de Componentes</h3>
              <div class="text-center py-3">
                <div class="componente-contador total">0</div>
                <div class="componente-etiqueta">No hay componentes asociados</div>
              </div>
            </div>
          `;
          }

          // Actualizar contenido del panel
          $("#equipo-detalle .detail-content").html(`
          ${tiempoRestante}
          ${componentesHtml}
        `);

          // Inicializar el visor de imágenes para la imagen del encabezado
          $(".detail-header-img").on("click", function () {
            if (window.imageViewer) {
              window.imageViewer.show($(this).attr("src"), "Imagen del equipo");
            }
          });
        } else {
          // Mostrar mensaje de error
          $("#equipo-detalle .detail-content").html(`
          <div class="detail-empty">
            <div class="detail-empty-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="detail-empty-text">
              Error al cargar los detalles del equipo
            </div>
          </div>
        `);
        }
      },
      error: (xhr, status, error) => {
        // Mostrar mensaje de error
        $("#equipo-detalle .detail-content").html(`
        <div class="detail-empty">
          <div class="detail-empty-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div class="detail-empty-text">
            Error de conexión al servidor
          </div>
        </div>
      `);
        console.error("Error al obtener detalles del equipo:", error);
      },
    });
  }

  // Función para capitalizar la primera letra
  function capitalizarPrimeraLetra(string) {
    if (!string) return string;
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
  }

  // Función para formatear números
  function formatearNumero(numero) {
    if (numero === null || numero === undefined) return "0";
    return Number.parseFloat(numero)
      .toFixed(2)
      .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  // Función para obtener la clase del estado
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

  // Función para calcular el próximo orómetro
  function calcularProximoOrometro() {
    const anteriorOrometro =
      Number.parseFloat($("#equipo-anterior-orometro").val()) || 0;
    const mantenimiento =
      Number.parseFloat($("#equipo-mantenimiento").val()) || 0;
    const proximoOrometro = anteriorOrometro + mantenimiento;

    // Actualizar el campo visible de próximo orómetro
    $("#equipo-proximo-orometro").val(proximoOrometro.toFixed(2));

    // Actualizar también el campo oculto
    $("#hidden-proximo-orometro").val(proximoOrometro.toFixed(2));
  }

  // Función para actualizar las etiquetas de unidad según el tipo de orómetro
  function actualizarEtiquetasUnidad() {
    const tipoOrometro = $("#equipo-tipo-orometro").val();
    const unidad = getUnidadOrometro(tipoOrometro);

    // Actualizar todas las etiquetas de unidad
    $(".unidad-orometro").text(unidad);
  }

  // Inicializar validación del formulario
  function initFormValidation() {
    $("#form-equipo").validate({
      rules: {
        codigo: {
          required: true,
          minlength: 2,
          maxlength: 50,
        },
        nombre: {
          required: true,
          minlength: 3,
          maxlength: 100,
        },
        categoria_id: {
          required: true,
        },
        tipo_equipo: {
          required: true,
        },
        estado: {
          required: true,
        },
        orometro_actual: {
          required: true,
          number: true,
          min: 0,
        },
        limite: {
          number: true,
          min: 0,
          max: 1000000,
        },
        notificacion: {
          number: true,
          min: 0,
          max: 1000,
        },
        mantenimiento: {
          number: true,
          min: 0,
        },
      },
      messages: {
        codigo: {
          required: "El código es obligatorio",
          minlength: "El código debe tener al menos 2 caracteres",
          maxlength: "El código no puede tener más de 50 caracteres",
        },
        nombre: {
          required: "El nombre es obligatorio",
          minlength: "El nombre debe tener al menos 3 caracteres",
          maxlength: "El nombre no puede tener más de 100 caracteres",
        },
        categoria_id: {
          required: "La categoría es obligatoria",
        },
        tipo_equipo: {
          required: "El tipo de equipo es obligatorio",
        },
        estado: {
          required: "El estado es obligatorio",
        },
        orometro_actual: {
          required: "El orómetro actual es obligatorio",
          number: "Debe ser un número válido",
          min: "No puede ser negativo",
        },
        limite: {
          number: "Debe ser un número válido",
          min: "No puede ser negativo",
          max: "No puede ser mayor a 1,000,000",
        },
        notificacion: {
          number: "Debe ser un número válido",
          min: "No puede ser negativo",
          max: "No puede ser mayor a 1,000",
        },
        mantenimiento: {
          number: "Debe ser un número válido",
          min: "No puede ser negativo",
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
        guardarEquipo();
        return false;
      },
    });
  }

  // Inicializar componente de carga de imágenes
  function initImageUploader() {
    // Verificar si el contenedor existe
    if (document.getElementById("container-equipo-imagen")) {
      // Inicializar el componente
      try {
        imageUploader = new ImageUpload("container-equipo-imagen", {
          maxSize: 2 * 1024 * 1024, // 2MB
          acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
          inputName: "imagen",
          defaultImage: getUrl("assets/img/equipos/equipos/default.png"),
          existingImage: "",
          uploadPath: "assets/img/equipos/equipos/",
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

  // Abrir modal para crear nuevo equipo
  function abrirModalCrear() {
    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Preparando formulario para nuevo equipo...");
    }

    // Limpiar formulario
    $("#form-equipo")[0].reset();
    $("#equipo-id").val("");

    // Actualizar título del modal
    $("#modal-equipo-titulo").text("Nuevo Equipo");

    // Inicializar componente de carga de imágenes con valores por defecto
    if (imageUploader) {
      try {
        // Reiniciar el componente con valores por defecto
        imageUploader = new ImageUpload("container-equipo-imagen", {
          maxSize: 2 * 1024 * 1024,
          acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
          inputName: "imagen",
          defaultImage: getUrl("assets/img/equipos/equipos/default.png"),
          existingImage: "",
          uploadPath: "assets/img/equipos/equipos/",
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
    const modalEquipo = new bootstrap.Modal(
      document.getElementById("modal-equipo")
    );
    modalEquipo.show();

    // Resetear validación
    $("#form-equipo").validate().resetForm();
    $("#form-equipo .is-invalid").removeClass("is-invalid");

    // Resetear equipo actual
    equipoActual = null;

    // Configurar el campo de próximo orómetro como solo lectura
    $("#equipo-proximo-orometro").prop("readonly", true);

    // Actualizar etiquetas de unidad
    actualizarEtiquetasUnidad();

    // Calcular próximo orómetro inicial
    calcularProximoOrometro();
  }

  // Abrir modal para editar equipo
  function abrirModalEditar(id) {
    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Cargando información del equipo para editar...");
    }

    // Obtener datos del equipo
    $.ajax({
      url: getUrl("api/equipos/equipos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success && response.data) {
          const equipo = response.data;
          equipoActual = equipo;

          // Llenar formulario
          $("#equipo-id").val(equipo.id);
          $("#equipo-codigo").val(equipo.codigo);
          $("#equipo-nombre").val(equipo.nombre);
          $("#equipo-categoria").val(equipo.categoria_id);
          $("#equipo-tipo").val(equipo.tipo_equipo);
          $("#equipo-marca").val(equipo.marca);
          $("#equipo-modelo").val(equipo.modelo);
          $("#equipo-serie").val(equipo.numero_serie);
          $("#equipo-capacidad").val(equipo.capacidad);
          $("#equipo-fase").val(equipo.fase);
          $("#equipo-linea").val(equipo.linea_electrica);
          $("#equipo-ubicacion").val(equipo.ubicacion);
          $("#equipo-estado").val(equipo.estado);
          $("#equipo-tipo-orometro").val(equipo.tipo_orometro);
          $("#equipo-anterior-orometro").val(equipo.anterior_orometro);
          $("#equipo-orometro").val(equipo.orometro_actual);
          $("#equipo-proximo-orometro").val(equipo.proximo_orometro);
          $("#equipo-limite").val(equipo.limite);
          $("#equipo-notificacion").val(equipo.notificacion);
          $("#equipo-mantenimiento").val(equipo.mantenimiento);
          $("#equipo-observaciones").val(equipo.observaciones);

          // Configurar el campo de próximo orómetro como solo lectura
          $("#equipo-proximo-orometro").prop("readonly", true);

          // Actualizar etiquetas de unidad
          actualizarEtiquetasUnidad();

          // Inicializar componente de carga de imágenes con la imagen existente
          if (imageUploader) {
            try {
              // Reiniciar el componente con la imagen existente
              imageUploader = new ImageUpload("container-equipo-imagen", {
                maxSize: 2 * 1024 * 1024,
                acceptedTypes: [
                  "image/jpeg",
                  "image/png",
                  "image/gif",
                  "image/webp",
                ],
                inputName: "imagen",
                defaultImage: getUrl("assets/img/equipos/equipos/default.png"),
                existingImage: equipo.imagen || "",
                uploadPath: "assets/img/equipos/equipos/",
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

          // Actualizar título del modal
          $("#modal-equipo-titulo").text("Editar Equipo");

          // Mostrar modal
          const modalEquipo = new bootstrap.Modal(
            document.getElementById("modal-equipo")
          );
          modalEquipo.show();

          // Resetear validación
          $("#form-equipo").validate().resetForm();
          $("#form-equipo .is-invalid").removeClass("is-invalid");
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos del equipo"
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
        console.error("Error al obtener equipo:", error);
      },
    });
  }

  // Guardar equipo (crear o actualizar)
  function guardarEquipo() {
    // Validar formulario
    if (!$("#form-equipo").valid()) {
      return;
    }

    // Mostrar indicador de carga
    showLoadingOverlay();

    // Preparar datos del formulario
    const formData = new FormData(document.getElementById("form-equipo"));

    // Calcular y establecer el próximo orómetro
    const anteriorOrometro =
      Number.parseFloat($("#equipo-anterior-orometro").val()) || 0;
    const mantenimiento =
      Number.parseFloat($("#equipo-mantenimiento").val()) || 0;
    const proximoOrometro = anteriorOrometro + mantenimiento;

    // Actualizar el campo de próximo orómetro en el formulario
    $("#equipo-proximo-orometro").val(proximoOrometro.toFixed(2));
    formData.set("proximo_orometro", proximoOrometro.toFixed(2));

    // Verificar si la imagen es default.png y está siendo reemplazada
    const imagenInput = document.getElementById("input-equipo-imagen");
    const existingImageElement = document.getElementById(
      "existing-equipo-imagen"
    );
    const existingImage = existingImageElement
      ? existingImageElement.value
      : "";

    if (
      imagenInput &&
      imagenInput.files.length > 0 &&
      existingImage.includes("default.png")
    ) {
      // Si es la imagen por defecto, no enviar el campo de imagen existente
      formData.delete("imagen_existing");
    }

    // Enviar solicitud
    $.ajax({
      url: getUrl("api/equipos/equipos/guardar.php"),
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
          const modalEquipo = bootstrap.Modal.getInstance(
            document.getElementById("modal-equipo")
          );
          modalEquipo.hide();

          // Mostrar mensaje de éxito
          if (window.showSuccessToast) {
            window.showSuccessToast(response.message);
          }

          // Recargar tabla
          equiposTable.ajax.reload(null, false);

          // Si hay un equipo seleccionado, actualizar sus detalles
          if (equipoSeleccionado && equipoSeleccionado.id == response.id) {
            // Añadir animación al panel de detalles
            $("#equipo-detalle").removeClass("loaded").addClass("loading");

            // Cargar detalles con un pequeño retraso para la animación
            setTimeout(() => {
              cargarDetallesEquipo(response.id);
              // Quitar clase de carga y añadir clase de cargado para la animación
              $("#equipo-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar el equipo"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        // Intentar parsear la respuesta JSON
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
        console.error("Error al guardar equipo:", error);
      },
    });
  }

  // Modificación para la función verDetalleEquipo en equipos.js
  function verDetalleEquipo(id) {
    // Mostrar indicador de carga
    showLoadingOverlay();

    // Mostrar toast de información
    if (window.showInfoToast) {
      window.showInfoToast("Cargando detalles del equipo...");
    }

    // Obtener datos del equipo
    $.ajax({
      url: getUrl("api/equipos/equipos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        // Ocultar indicador de carga
        hideLoadingOverlay();

        if (response.success && response.data) {
          const equipo = response.data;
          equipoActual = equipo;
          const unidad = getUnidadOrometro(equipo.tipo_orometro);

          // Actualizar datos en el modal
          $("#detalle-nombre").text(equipo.nombre);
          $("#detalle-codigo").text(equipo.codigo || "-");
          $("#detalle-categoria").text(equipo.categoria_nombre || "-");
          $("#detalle-tipo").text(
            capitalizarPrimeraLetra(equipo.tipo_equipo) || "-"
          );
          $("#detalle-tipo-orometro").text(
            capitalizarPrimeraLetra(equipo.tipo_orometro) || "-"
          );
          $("#detalle-marca").text(equipo.marca || "-");
          $("#detalle-modelo").text(equipo.modelo || "-");
          $("#detalle-serie").text(equipo.numero_serie || "-");
          $("#detalle-capacidad").text(equipo.capacidad || "-");
          $("#detalle-fase").text(equipo.fase || "-");
          $("#detalle-linea").text(equipo.linea_electrica || "-");
          $("#detalle-ubicacion").text(equipo.ubicacion || "-");

          // Actualizar información de orómetros
          $("#detalle-orometro-anterior").text(
            formatearNumero(equipo.anterior_orometro || 0) + " " + unidad
          );
          $("#detalle-orometro").text(
            equipo.orometro_actual
              ? formatearNumero(equipo.orometro_actual) + " " + unidad
              : "0.00 " + unidad
          );
          $("#detalle-proximo-orometro").text(
            equipo.proximo_orometro
              ? formatearNumero(equipo.proximo_orometro) + " " + unidad
              : "-"
          );

          // Calcular y mostrar tiempo restante
          if (equipo.proximo_orometro && equipo.orometro_actual) {
            const restante =
              Number.parseFloat(equipo.proximo_orometro) -
              Number.parseFloat(equipo.orometro_actual);

            // Determinar color según proximidad
            let colorClase = "success";
            if (equipo.notificacion) {
              const notificacion = Number.parseFloat(equipo.notificacion);
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
            equipo.limite ? formatearNumero(equipo.limite) + " " + unidad : "-"
          );
          $("#detalle-notificacion").text(
            equipo.notificacion
              ? formatearNumero(equipo.notificacion) + " " + unidad
              : "-"
          );
          $("#detalle-mantenimiento").text(
            equipo.mantenimiento
              ? formatearNumero(equipo.mantenimiento) + " " + unidad
              : "-"
          );
          $("#detalle-observaciones").text(equipo.observaciones || "-");

          // Actualizar imagen
          if (equipo.imagen) {
            $("#detalle-imagen").attr("src", equipo.imagen);
          } else {
            $("#detalle-imagen").attr(
              "src",
              getUrl("assets/img/equipos/equipos/default.png")
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
              (estadoClases[equipo.estado] || "bg-secondary")
          );
          $("#detalle-estado").text(
            estadoTexto[equipo.estado] || equipo.estado
          );

          // Cargar componentes desde la respuesta de obtener.php
          if (response.componentes && response.componentes.length > 0) {
            // Mostrar tabla y ocultar mensaje
            $("#componentes-table").removeClass("d-none");
            $("#sin-componentes").addClass("d-none");

            // Limpiar tabla
            $("#componentes-body").empty();

            // Agregar componentes a la tabla
            response.componentes.forEach((componente) => {
              const componenteUnidad = getUnidadOrometro(
                componente.tipo_orometro
              );
              const row = `
                          <tr>
                              <td>${componente.codigo || "-"}</td>
                              <td>${componente.nombre || "-"}</td>
                              <td>${
                                capitalizarPrimeraLetra(
                                  componente.tipo_orometro
                                ) || "-"
                              }</td>
                              <td><span class="badge rounded-pill ${obtenerClaseEstado(
                                componente.estado
                              )}">${capitalizarPrimeraLetra(
                componente.estado
              )}</span></td>
                              <td class="text-end">${
                                formatearNumero(componente.orometro_actual) ||
                                "0"
                              } ${componenteUnidad}</td>
                              <td class="text-end">${
                                componente.limite
                                  ? formatearNumero(componente.limite) +
                                    " " +
                                    componenteUnidad
                                  : "-"
                              }</td>
                          </tr>
                      `;
              $("#componentes-body").append(row);
            });
          } else {
            // Ocultar tabla y mostrar mensaje
            $("#componentes-table").addClass("d-none");
            $("#sin-componentes").removeClass("d-none");
          }

          // Mostrar modal
          const modalDetalle = new bootstrap.Modal(
            document.getElementById("modal-detalle-equipo")
          );
          modalDetalle.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los detalles del equipo"
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
        console.error("Error al obtener detalles del equipo:", error);
      },
    });
  }

  // Cargar componentes asociados a un equipo
  function cargarComponentesAsociados(equipoId) {
    $.ajax({
      url: getUrl("api/equipos/componentes/listar.php"),
      type: "POST",
      data: { equipo_id: equipoId },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const componentes = response.data;

          if (componentes.length > 0) {
            // Mostrar tabla y ocultar mensaje
            $("#componentes-table").removeClass("d-none");
            $("#sin-componentes").addClass("d-none");

            // Limpiar tabla
            $("#componentes-body").empty();

            // Agregar componentes a la tabla
            componentes.forEach((componente) => {
              const componenteUnidad = getUnidadOrometro(
                componente.tipo_orometro
              );
              const row = `
                <tr>
                  <td>${componente.codigo || "-"}</td>
                  <td>${componente.nombre || "-"}</td>
                  <td>${
                    capitalizarPrimeraLetra(componente.tipo_orometro) || "-"
                  }</td>
                  <td><span class="badge rounded-pill ${obtenerClaseEstado(
                    componente.estado
                  )}">${capitalizarPrimeraLetra(componente.estado)}</span></td>
                  <td class="text-end">${
                    formatearNumero(componente.orometro_actual) || "0"
                  } ${componenteUnidad}</td>
                  <td class="text-end">${
                    componente.limite
                      ? formatearNumero(componente.limite) +
                        " " +
                        componenteUnidad
                      : "-"
                  }</td>
                </tr>
              `;
              $("#componentes-body").append(row);
            });
          } else {
            // Ocultar tabla y mostrar mensaje
            $("#componentes-table").addClass("d-none");
            $("#sin-componentes").removeClass("d-none");
          }
        } else {
          // Ocultar tabla y mostrar mensaje
          $("#componentes-table").addClass("d-none");
          $("#sin-componentes").removeClass("d-none");
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar componentes:", error);
        console.log("Respuesta del servidor:", xhr.responseText);

        // Ocultar tabla y mostrar mensaje
        $("#componentes-table").addClass("d-none");
        $("#sin-componentes").removeClass("d-none");

        // Mostrar mensaje de error en consola para depuración
        if (xhr.responseText && xhr.responseText.includes("<br")) {
          console.error(
            "La respuesta del servidor contiene HTML en lugar de JSON. Posible error de PHP."
          );
        }
      },
    });
  }

  // Eliminar equipo
  function eliminarEquipo(id) {
    // Mostrar modal de confirmación
    const modalConfirmar = new bootstrap.Modal(
      document.getElementById("modal-confirmar-eliminar")
    );
    modalConfirmar.show();

    // Configurar botón de confirmación
    $("#btn-confirmar-eliminar")
      .off("click")
      .on("click", () => {
        // Ocultar modal de confirmación
        modalConfirmar.hide();

        // Mostrar indicador de carga
        showLoadingOverlay();

        // Mostrar toast de información
        if (window.showInfoToast) {
          window.showInfoToast("Eliminando equipo...");
        }

        // Enviar solicitud
        $.ajax({
          url: getUrl("api/equipos/equipos/eliminar.php"),
          type: "POST",
          data: { id: id },
          dataType: "json",
          success: (response) => {
            // Ocultar indicador de carga
            hideLoadingOverlay();

            if (response.success) {
              // Mostrar mensaje de éxito
              if (window.showSuccessToast) {
                window.showSuccessToast(response.message);
              }

              // Recargar tabla
              equiposTable.ajax.reload();

              // Si el equipo eliminado es el que está seleccionado, limpiar panel de detalles
              if (equipoSeleccionado && equipoSeleccionado.id == id) {
                $("#equipo-detalle .detail-content").html(`
                  <div class="detail-empty">
                    <div class="detail-empty-icon">
                      <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="detail-empty-text">
                      Seleccione un equipo para ver sus detalles
                    </div>
                  </div>
                `);
                equipoSeleccionado = null;
              }
            } else {
              // Mostrar mensaje de error
              if (window.showErrorToast) {
                window.showErrorToast(response.message);
              }

              // Si hay componentes asociados, mostrarlos
              if (response.componentes && response.componentes.length > 0) {
                let componentesHtml = '<ul class="mb-0">';
                response.componentes.forEach((componente) => {
                  componentesHtml += `<li>${componente}</li>`;
                });
                componentesHtml += "</ul>";

                if (window.Swal) {
                  window.Swal.fire({
                    title: "No se puede eliminar",
                    html: `Este equipo tiene componentes asociados:<br>${componentesHtml}`,
                    icon: "warning",
                    confirmButtonText: "Entendido",
                  });
                }
              }
            }
          },
          error: (xhr, status, error) => {
            // Ocultar indicador de carga
            hideLoadingOverlay();
            if (window.showErrorToast) {
              window.showErrorToast("Error de conexión al servidor");
            }
            console.error("Error al eliminar equipo:", error);
          },
        });
      });
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
    if (permiso === "equipos.crear") {
      return $("#btn-nuevo-equipo").length > 0;
    } else if (permiso === "equipos.editar") {
      return $("#btn-editar-desde-detalle").length > 0;
    } else if (permiso === "equipos.eliminar") {
      return true; // Por defecto permitir eliminar
    }

    return false;
  }

  // Inicializar componentes
  initDataTable();
  initFormValidation();
  initImageUploader();

  // Event Listeners
  // Botón para crear nuevo equipo
  $("#btn-nuevo-equipo").on("click", () => {
    abrirModalCrear();
  });

  // Botón para guardar equipo
  $("#btn-guardar-equipo").on("click", () => {
    $("#form-equipo").submit();
  });

  // Botón para ver detalles del equipo
  $(document).on("click", ".btn-ver-equipo", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    verDetalleEquipo(id);
  });

  // Botón para editar equipo
  $(document).on("click", ".btn-editar-equipo", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    abrirModalEditar(id);
  });

  // Botón para eliminar equipo
  $(document).on("click", ".btn-eliminar-equipo", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    eliminarEquipo(id);
  });

  // Botón para editar desde el modal de detalles
  $("#btn-editar-desde-detalle").on("click", () => {
    // Cerrar modal de detalles
    const modalDetalle = bootstrap.Modal.getInstance(
      document.getElementById("modal-detalle-equipo")
    );
    modalDetalle.hide();

    // Abrir modal de edición
    if (equipoActual && equipoActual.id) {
      setTimeout(() => {
        abrirModalEditar(equipoActual.id);
      }, 500);
    }
  });

  // Botón para ver imagen ampliada
  $("#btn-ver-imagen").on("click", () => {
    const imagen = $("#detalle-imagen").attr("src");
    try {
      if (imagen && window.imageViewer) {
        window.imageViewer.show(imagen, "Imagen del equipo");
      }
    } catch (e) {
      window.imageViewer.show(imagen, "Imagen del equipo");
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

  // Evento para cambio en tipo de orómetro
  $("#equipo-tipo-orometro").on("change", () => {
    actualizarEtiquetasUnidad();
  });

  // Eventos para calcular próximo orómetro automáticamente
  $("#equipo-anterior-orometro, #equipo-mantenimiento").on("input", () => {
    calcularProximoOrometro();
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
