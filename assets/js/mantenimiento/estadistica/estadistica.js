/**
 * JavaScript para el módulo de Estadísticas
 * SIGESMANCOR
 */

// Variables globales
let tablaHistorial
let graficaTrabajo
let elementosSeleccionados = []
let elementosDisponibles = []

// Constantes
const $ = window.jQuery
const ApexCharts = window.ApexCharts

// Verificar disponibilidad de funciones globales sin redeclararlas
if (!window.showErrorToast) {
  window.showErrorToast = (msg) => console.error(msg)
}
if (!window.showSuccessToast) {
  window.showSuccessToast = (msg) => console.log(msg)
}
if (!window.showInfoToast) {
  window.showInfoToast = (msg) => console.log(msg)
}

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  // Inicializar componentes
  inicializarTablaHistorial()
  inicializarEventos()
  cargarElementosDisponibles()

  // Establecer fechas por defecto
  establecerFechasPorDefecto()

  // Inicializar tooltips
  $('[data-bs-toggle="tooltip"]').tooltip()
})

// Función para obtener la URL base
function getBaseUrl() {
  return window.location.pathname.split("/modulos/")[0] + "/"
}

// Función para construir URL completa
function getUrl(path) {
  return getBaseUrl() + path
}

/**
 * Inicializa la tabla de historial con DataTables
 */
function inicializarTablaHistorial() {
  tablaHistorial = $("#tabla-historial").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: getUrl("api/mantenimiento/estadistica/listar-historial.php"),
      type: "POST",
      data: (d) => {
        // Si se selecciona "Todos", usar un valor grande pero finito
        if (d.length == -1) {
          d.length = 10000
        }

        // Agregar filtros personalizados
        d.fecha_desde = $("#fecha-desde-historial").val()
        d.fecha_hasta = $("#fecha-hasta-historial").val()
        d.tipo = $("#tipo-historial").val()
        d.fuente = $("#fuente-historial").val()
        return d
      },
      error: (xhr, error, thrown) => {
        console.error("Error en la solicitud AJAX de DataTable:", error, thrown)
        mostrarError("Error al cargar los datos de la tabla: " + thrown)
      },
    },
    columns: [
      {
        data: "fecha",
        className: "text-center align-middle",
        width: "100px",
      },
      {
        data: "nombre",
        className: "text-left align-middle",
      },
      {
        data: "codigo",
        className: "text-center align-middle",
        width: "100px",
      },
      {
        data: "tipo",
        className: "text-center align-middle",
        width: "100px",
        render: (data) => {
          const badgeClass = data === "Equipo" ? "estado-activo" : "estado-mantenimiento"
          return `<span class="estado-badge ${badgeClass}">${data}</span>`
        },
      },
      {
        data: "horas_trabajadas",
        className: "text-center align-middle font-weight-bold",
        width: "120px",
      },
      {
        data: "fuente",
        className: "text-center align-middle",
        width: "100px",
        render: (data) => {
          const badgeClass = data === "Manual" ? "estado-mantenimiento" : "estado-activo"
          return `<span class="estado-badge ${badgeClass}">${data}</span>`
        },
      },
      {
        data: "observaciones",
        className: "text-left align-middle",
        render: (data) => {
          if (!data || data === "-") return "-"
          return data.length > 50 ? data.substring(0, 50) + "..." : data
        },
      },
    ],
    dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
    buttons: [
      {
        extend: "copy",
        text: '<i class="bi bi-clipboard"></i> Copiar',
        className: "btn btn-sm",
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
      },
      {
        extend: "excel",
        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
        className: "btn btn-sm",
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
        filename: "Historial_Trabajo_" + new Date().toISOString().split("T")[0],
      },
      {
        extend: "pdf",
        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
        className: "btn btn-sm",
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
        customize: (doc) => {
          // Configuración del PDF similar a componentes
          doc.pageOrientation = "landscape"
          doc.defaultStyle = {
            fontSize: 8,
            color: "#333333",
          }

          // Colores
          const colores = {
            azulPastel: "#D4E6F1",
            verdePastel: "#D5F5E3",
            naranjaPastel: "#FAE5D3",
            azulOscuro: "#1A5276",
            verdeOscuro: "#186A3B",
            naranjaOscuro: "#BA4A00",
            azulPrimario: "#0055A4",
          }

          // Encabezado
          doc.content.unshift(
            {
              text: "REPORTE DE HISTORIAL DE TRABAJO DIARIO",
              style: "header",
              alignment: "center",
              margin: [0, 0, 0, 20],
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
            },
          )

          // Configurar tabla
          const tableIndex = doc.content.findIndex((item) => item.table)
          if (tableIndex !== -1) {
            doc.content[tableIndex].table.widths = [
              "12%", // Fecha
              "25%", // Nombre
              "12%", // Código
              "10%", // Tipo
              "15%", // Horas
              "10%", // Fuente
              "16%", // Observaciones
            ]

            // Estilizar tabla
            doc.content[tableIndex].table.body.forEach((row, i) => {
              if (i === 0) {
                // Encabezado
                row.forEach((cell) => {
                  cell.fillColor = colores.azulPastel
                  cell.color = colores.azulOscuro
                  cell.fontSize = 9
                  cell.bold = true
                  cell.alignment = "center"
                  cell.margin = [2, 3, 2, 3]
                })
              } else {
                // Filas de datos
                row.forEach((cell, j) => {
                  cell.fontSize = 8
                  cell.margin = [2, 2, 2, 2]

                  if (j === 0 || j === 2 || j === 3 || j === 4 || j === 5) {
                    cell.alignment = "center"
                  } else {
                    cell.alignment = "left"
                  }
                })

                // Zebra stripes
                if (i % 2 === 0) {
                  row.forEach((cell) => {
                    if (!cell.fillColor) {
                      cell.fillColor = "#f9f9f9"
                    }
                  })
                }
              }
            })
          }

          // Estilos
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
          }

          // Pie de página
          doc.footer = (currentPage, pageCount) => ({
            text: `Página ${currentPage} de ${pageCount}`,
            alignment: "center",
            fontSize: 8,
            margin: [0, 0, 0, 0],
          })
        },
        filename: "Historial_Trabajo_" + new Date().toISOString().split("T")[0],
      },
      {
        extend: "print",
        text: '<i class="bi bi-printer"></i> Imprimir',
        className: "btn btn-sm",
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] },
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
    order: [[0, "desc"]], // Ordenar por fecha descendente
    drawCallback: () => {
      if (window.hideLoading) {
        window.hideLoading()
      }
    },
    preDrawCallback: () => {
      if (window.showLoading) {
        window.showLoading()
      }
    },
  })
}

/**
 * Inicializa todos los eventos
 */
function inicializarEventos() {
  // Eventos para filtros de historial
  $("#btn-aplicar-filtros-historial").on("click", aplicarFiltrosHistorial)
  $("#btn-limpiar-filtros-historial").on("click", limpiarFiltrosHistorial)

  // Eventos para gráficas
  $("#tipo-elemento-grafica").on("change", cargarElementosDisponibles)
  $("#btn-generar-grafica").on("click", generarGrafica)
  $("#btn-limpiar-seleccion").on("click", limpiarSeleccion)

  // Eventos para tipos de gráfica
  $(".grafica-tipo").on("click", function () {
    $(".grafica-tipo").removeClass("active")
    $(this).addClass("active")

    if (graficaTrabajo) {
      const tipoGrafica = $(this).data("tipo")
      actualizarTipoGrafica(tipoGrafica)
    }
  })

  // Evento para actualizar datos
  $("#btn-actualizar").on("click", () => {
    tablaHistorial.ajax.reload()
    cargarElementosDisponibles()
    mostrarExito("Datos actualizados correctamente")
  })

  // Eventos para exportar
  $("#btn-exportar-historial").on("click", exportarHistorial)
  $("#btn-exportar-grafica").on("click", exportarGrafica)
}

/**
 * Establece las fechas por defecto
 */
function establecerFechasPorDefecto() {
  const hoy = new Date()
  const hace30Dias = new Date()
  hace30Dias.setDate(hoy.getDate() - 30)

  // Fechas para historial
  $("#fecha-desde-historial").val(hace30Dias.toISOString().split("T")[0])
  $("#fecha-hasta-historial").val(hoy.toISOString().split("T")[0])

  // Fechas para gráficas
  $("#fecha-desde-grafica").val(hace30Dias.toISOString().split("T")[0])
  $("#fecha-hasta-grafica").val(hoy.toISOString().split("T")[0])
}

/**
 * Aplica los filtros al historial
 */
function aplicarFiltrosHistorial() {
  mostrarInfo("Aplicando filtros...")
  tablaHistorial.ajax.reload()
}

/**
 * Limpia los filtros del historial
 */
function limpiarFiltrosHistorial() {
  $("#fecha-desde-historial").val("")
  $("#fecha-hasta-historial").val("")
  $("#tipo-historial").val("")
  $("#fuente-historial").val("")

  mostrarInfo("Limpiando filtros...")
  tablaHistorial.ajax.reload()
}

/**
 * Carga los elementos disponibles según el tipo seleccionado
 */
function cargarElementosDisponibles() {
  const tipo = $("#tipo-elemento-grafica").val()
  const container = $("#elementos-disponibles")

  // Mostrar loader
  container.html(`
    <div class="text-center py-3">
      <div class="spinner-border spinner-border-sm" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <div class="mt-2">Cargando elementos...</div>
    </div>
  `)

  $.ajax({
    url: getUrl("api/mantenimiento/estadistica/obtener-elementos.php"),
    type: "GET",
    data: { tipo: tipo },
    dataType: "json",
    success: (response) => {
      if (response.success) {
        elementosDisponibles = response.elementos
        renderizarElementosDisponibles()
      } else {
        mostrarError(response.error || "Error al cargar elementos")
        container.html('<div class="text-danger text-center py-2">Error al cargar elementos</div>')
      }
    },
    error: (xhr, status, error) => {
      console.error("Error al cargar elementos:", error)
      mostrarError("Error de conexión al servidor")
      container.html('<div class="text-danger text-center py-2">Error de conexión</div>')
    },
  })
}

/**
 * Renderiza la lista de elementos disponibles
 */
function renderizarElementosDisponibles() {
  const container = $("#elementos-disponibles")

  if (elementosDisponibles.length === 0) {
    container.html(`
      <div class="text-muted text-center py-3">
        <i class="bi bi-info-circle me-1"></i>
        No hay elementos disponibles
      </div>
    `)
    return
  }

  let html = ""
  elementosDisponibles.forEach((elemento) => {
    // Verificar si ya está seleccionado
    const yaSeleccionado = elementosSeleccionados.some((sel) => sel.id === elemento.id)

    if (!yaSeleccionado) {
      html += `
        <div class="elemento-item" data-id="${elemento.id}">
          <div class="elemento-info">
            <div class="elemento-nombre">${elemento.nombre}</div>
            <div class="elemento-codigo">Código: ${elemento.codigo}</div>
            ${
              elemento.tipo === "componente" && elemento.equipo_nombre
                ? `<div class="elemento-tipo">Equipo: ${elemento.equipo_nombre}</div>`
                : `<div class="elemento-tipo">${elemento.tipo_equipo || "Sin tipo"}</div>`
            }
          </div>
          <button class="btn-seleccionar" onclick="seleccionarElemento(${elemento.id})">
            <i class="bi bi-plus"></i>
          </button>
        </div>
      `
    }
  })

  if (html === "") {
    container.html(`
      <div class="text-muted text-center py-3">
        <i class="bi bi-check-circle me-1"></i>
        Todos los elementos están seleccionados
      </div>
    `)
  } else {
    container.html(html)
  }
}

/**
 * Selecciona un elemento para la gráfica
 */
function seleccionarElemento(elementoId) {
  const elemento = elementosDisponibles.find((el) => el.id === elementoId)

  if (elemento && !elementosSeleccionados.some((sel) => sel.id === elementoId)) {
    elementosSeleccionados.push(elemento)
    renderizarElementosDisponibles()
    renderizarElementosSeleccionados()
    mostrarExito(`${elemento.nombre} agregado a la selección`)
  }
}

/**
 * Quita un elemento de la selección
 */
function quitarElemento(elementoId) {
  const index = elementosSeleccionados.findIndex((el) => el.id === elementoId)

  if (index !== -1) {
    const elemento = elementosSeleccionados[index]
    elementosSeleccionados.splice(index, 1)
    renderizarElementosDisponibles()
    renderizarElementosSeleccionados()
    mostrarInfo(`${elemento.nombre} removido de la selección`)
  }
}

/**
 * Renderiza la lista de elementos seleccionados
 */
function renderizarElementosSeleccionados() {
  const container = $("#elementos-seleccionados")

  if (elementosSeleccionados.length === 0) {
    container.html(`
      <div class="text-muted text-center py-2">
        <i class="bi bi-info-circle me-1"></i>
        Seleccione elementos para graficar
      </div>
    `)
    return
  }

  let html = ""
  elementosSeleccionados.forEach((elemento) => {
    html += `
      <div class="elemento-seleccionado">
        <div class="elemento-info">
          <div class="elemento-nombre">${elemento.nombre}</div>
          <div class="elemento-codigo">Código: ${elemento.codigo}</div>
        </div>
        <button class="btn-quitar" onclick="quitarElemento(${elemento.id})">
          <i class="bi bi-x"></i>
        </button>
      </div>
    `
  })

  container.html(html)
}

/**
 * Limpia la selección de elementos
 */
function limpiarSeleccion() {
  elementosSeleccionados = []
  renderizarElementosDisponibles()
  renderizarElementosSeleccionados()

  // Limpiar gráfica
  if (graficaTrabajo) {
    graficaTrabajo.destroy()
    graficaTrabajo = null
  }

  $("#grafica-trabajo").html(`
    <div class="grafica-placeholder">
      <div class="text-center py-5">
        <i class="bi bi-graph-up grafica-placeholder-icon"></i>
        <h5 class="mt-3">Gráfica de Horas Trabajadas</h5>
        <p class="text-muted">Seleccione elementos y genere la gráfica para visualizar las horas trabajadas por día</p>
      </div>
    </div>
  `)

  mostrarInfo("Selección limpiada")
}

/**
 * Genera la gráfica con los elementos seleccionados
 */
function generarGrafica() {
  if (elementosSeleccionados.length === 0) {
    mostrarError("Debe seleccionar al menos un elemento para generar la gráfica")
    return
  }

  const fechaDesde = $("#fecha-desde-grafica").val()
  const fechaHasta = $("#fecha-hasta-grafica").val()
  const tipo = $("#tipo-elemento-grafica").val()

  if (!fechaDesde || !fechaHasta) {
    mostrarError("Debe seleccionar las fechas para generar la gráfica")
    return
  }

  if (new Date(fechaDesde) > new Date(fechaHasta)) {
    mostrarError("La fecha desde no puede ser mayor que la fecha hasta")
    return
  }

  // Mostrar loader
  mostrarLoaderGrafica()

  const datos = {
    elementos: elementosSeleccionados,
    fecha_desde: fechaDesde,
    fecha_hasta: fechaHasta,
    tipo: tipo,
  }

  $.ajax({
    url: getUrl("api/mantenimiento/estadistica/obtener-datos-grafica.php"),
    type: "POST",
    data: JSON.stringify(datos),
    contentType: "application/json",
    dataType: "json",
    success: (response) => {
      // IMPORTANTE: Ocultar loader inmediatamente
      ocultarLoaderGrafica()

      if (response.success) {
        renderizarGrafica(response.fechas, response.series)
        mostrarExito("Gráfica generada correctamente")
      } else {
        mostrarError(response.error || "Error al generar la gráfica")
      }
    },
    error: (xhr, status, error) => {
      // IMPORTANTE: Ocultar loader inmediatamente
      ocultarLoaderGrafica()
      console.error("Error al generar gráfica:", error)
      mostrarError("Error de conexión al servidor")
    },
  })
}

/**
 * Renderiza la gráfica con ApexCharts
 */
function renderizarGrafica(fechas, series) {
  const tipoGrafica = $(".grafica-tipo.active").data("tipo") || "line"

  const options = {
    chart: {
      type: tipoGrafica,
      height: 400,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: true,
          zoom: true,
          zoomin: true,
          zoomout: true,
          pan: true,
          reset: true,
        },
      },
      zoom: {
        enabled: true,
      },
    },
    series: series,
    xaxis: {
      categories: fechas,
      labels: {
        style: {
          fontSize: "10px",
        },
        rotate: -45,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      title: {
        text: "Horas Trabajadas",
      },
      labels: {
        style: {
          fontSize: "10px",
        },
        formatter: (val) => val.toFixed(1) + " hrs",
      },
    },
    stroke: {
      curve: "smooth",
      width: tipoGrafica === "line" ? 3 : 1,
    },
    fill: {
      type: tipoGrafica === "area" ? "gradient" : "solid",
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.4,
        opacityTo: 0.1,
        stops: [0, 100],
      },
    },
    legend: {
      position: "top",
      fontSize: "12px",
      markers: {
        width: 12,
        height: 12,
        radius: 2,
      },
      itemMargin: {
        horizontal: 10,
        vertical: 5,
      },
    },
    grid: {
      borderColor: "#f1f1f1",
      strokeDashArray: 4,
      xaxis: {
        lines: {
          show: false,
        },
      },
    },
    markers: {
      size: tipoGrafica === "line" ? 4 : 0,
      strokeWidth: 0,
      hover: {
        size: 6,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: (val) => val.toFixed(2) + " hrs",
      },
    },
    dataLabels: {
      enabled: false,
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300,
          },
          legend: {
            position: "bottom",
          },
        },
      },
    ],
  }

  try {
    // Destruir gráfica existente si la hay
    if (graficaTrabajo) {
      graficaTrabajo.destroy()
    }

    // Crear nueva gráfica
    graficaTrabajo = new ApexCharts(document.getElementById("grafica-trabajo"), options)
    graficaTrabajo.render()
  } catch (error) {
    console.error("Error al renderizar la gráfica:", error)
    mostrarError("Error al renderizar la gráfica: " + error.message)
  }
}

/**
 * Actualiza el tipo de gráfica
 */
function actualizarTipoGrafica(tipo) {
  if (!graficaTrabajo) return

  graficaTrabajo.updateOptions({
    chart: {
      type: tipo,
    },
    stroke: {
      width: tipo === "line" ? 3 : 1,
    },
    fill: {
      type: tipo === "area" ? "gradient" : "solid",
    },
    markers: {
      size: tipo === "line" ? 4 : 0,
    },
  })
}

/**
 * Muestra el loader en la gráfica
 */
function mostrarLoaderGrafica() {
  $("#grafica-trabajo").html(`
    <div class="grafica-loader">
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">Generando gráfica...</div>
      </div>
    </div>
  `)
}

/**
 * Oculta el loader de la gráfica
 */
function ocultarLoaderGrafica() {
  // El loader se oculta automáticamente cuando se renderiza la gráfica
  // pero podemos forzar la limpieza si es necesario
  const loader = $("#grafica-trabajo .grafica-loader")
  if (loader.length > 0) {
    loader.remove()
  }
}

/**
 * Exporta el historial
 */
function exportarHistorial() {
  // Usar los botones de DataTables para exportar
  $(".buttons-excel").trigger("click")
}

/**
 * Exporta la gráfica
 */
function exportarGrafica() {
  if (!graficaTrabajo) {
    mostrarError("No hay gráfica para exportar")
    return
  }

  graficaTrabajo.dataURI().then((uri) => {
    const link = document.createElement("a")
    link.href = uri.imgURI
    link.download = "grafica-horas-trabajadas.png"
    link.click()
    mostrarExito("Gráfica exportada correctamente")
  })
}

/**
 * Funciones de notificaciones
 */
function mostrarExito(mensaje) {
  if (typeof window.showSuccessToast === "function") {
    window.showSuccessToast(mensaje)
  } else {
    console.log("Éxito:", mensaje)
  }
}

function mostrarError(mensaje) {
  if (typeof window.showErrorToast === "function") {
    window.showErrorToast(mensaje)
  } else {
    console.error("Error:", mensaje)
  }
}

function mostrarInfo(mensaje) {
  if (typeof window.showInfoToast === "function") {
    window.showInfoToast(mensaje)
  } else {
    console.log("Info:", mensaje)
  }
}

// Hacer funciones globales para uso en HTML
window.seleccionarElemento = seleccionarElemento
window.quitarElemento = quitarElemento
