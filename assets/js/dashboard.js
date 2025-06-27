/**
 * Dashboard JavaScript - SIGESMANCOR
 * Sistema de Gestión de Mantenimiento CORDIAL SAC
 */

// Variables globales
let chartEstadoEquipos
let chartMantenimientosMes
let chartUbicaciones
let dashboardData = {}

// Configuración de colores
const colors = {
  primary: "#1571b0", 
  success: "#20c997",
  warning: "#ff8c00",
  danger: "#e63946",
  info: "#17a2b8",
  secondary: "#6c757d",
}

// Declaración de la variable $ para evitar errores de lint
const $ = window.$

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  console.log("Inicializando Dashboard SIGESMANCOR...")

  // Cargar datos iniciales
  cargarDatosDashboard()

  // Configurar eventos
  configurarEventos()

  // Actualizar timestamp
  actualizarTimestamp()

  // Configurar actualización automática cada 5 minutos
  setInterval(cargarDatosDashboard, 300000)
})

/**
 * Configura todos los eventos del dashboard
 */
function configurarEventos() {
  // Botón actualizar datos
  $("#btn-actualizar-datos").on("click", function () {
    $(this).addClass("loading")
    cargarDatosDashboard().finally(() => {
      $(this).removeClass("loading")
    })
  })

  // Botón exportar resumen
  $("#btn-exportar-resumen").on("click", exportarResumenGeneral)

  // Botones de exportación de gráficas
  $("#btn-export-equipos").on("click", () => exportarGrafica("equipos"))
  $("#btn-export-mantenimientos").on("click", () => exportarGrafica("mantenimientos"))
  $("#btn-export-ubicaciones").on("click", () => exportarGrafica("ubicaciones"))

  // Botones de exportación de tablas
  $("#btn-export-atencion").on("click", () => exportarTabla("atencion"))
  $("#btn-export-ultimos").on("click", () => exportarTabla("ultimos"))

  // Ver todo el historial
  $("#btn-ver-todo-historial").on("click", () => {
    window.location.href = "modulos/mantenimiento/historial/"
  })
}

/**
 * Carga todos los datos del dashboard
 */
async function cargarDatosDashboard() {
  try {
    console.log("Cargando datos del dashboard...")

    // Mostrar indicadores de carga
    mostrarCargando()

    // Cargar datos desde la API
    const response = await fetch("api/dashboard_data.php")
    const data = await response.json()

    if (data.success) {
      dashboardData = data.data

      // Actualizar estadísticas principales
      actualizarEstadisticasPrincipales(dashboardData.estadisticas)

      // Crear gráficas
      crearGraficas(dashboardData.graficas)

      // Cargar tablas
      cargarTablas(dashboardData.tablas)

      // Cargar actividad reciente
      cargarActividadReciente(dashboardData.actividad)

      // Cargar alertas
      cargarAlertas(dashboardData.alertas)

      console.log("Datos del dashboard cargados exitosamente")
    } else {
      throw new Error(data.message || "Error al cargar datos")
    }
  } catch (error) {
    console.error("Error al cargar datos del dashboard:", error)
    mostrarError("Error al cargar los datos del dashboard")
  } finally {
    ocultarCargando()
    actualizarTimestamp()
  }
}

/**
 * Actualiza las estadísticas principales
 */
function actualizarEstadisticasPrincipales(stats) {
  // Animar números
  animarNumero("#total-equipos", stats.totalEquipos)
  animarNumero("#equipos-activos", stats.equiposActivos)
  animarNumero("#mantenimientos-pendientes", stats.mantenimientosPendientes)
  animarNumero("#equipos-criticos", stats.equiposCriticos)

  // Actualizar cambios y porcentajes
  $("#equipos-change").text(`+${stats.equiposNuevos} este mes`)
  $("#activos-percentage").text(`${Math.round((stats.equiposActivos / stats.totalEquipos) * 100)}%`)
  $("#pendientes-change").text("Programados")
  $("#criticos-change").text("Requieren atención")
}

/**
 * Crea todas las gráficas del dashboard
 */
function crearGraficas(graficas) {
  // Gráfica de estado de equipos (Dona)
  crearGraficaEstadoEquipos(graficas.estadoEquipos)

  // Gráfica de mantenimientos por mes (Barras)
  crearGraficaMantenimientosMes(graficas.mantenimientosMes)

  // Gráfica de distribución por ubicación (Barras horizontales)
  crearGraficaUbicaciones(graficas.ubicaciones)
}

/**
 * Crea la gráfica de estado de equipos
 */
function crearGraficaEstadoEquipos(data) {
  const ctx = document.getElementById("chart-estado-equipos").getContext("2d")

  if (chartEstadoEquipos) {
    chartEstadoEquipos.destroy()
  }

  chartEstadoEquipos = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: data.labels,
      datasets: [
        {
          data: data.values,
          backgroundColor: [colors.success, colors.warning, colors.danger, colors.secondary, colors.info],
          borderWidth: 3,
          borderColor: "#fff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            padding: 15,
            usePointStyle: true,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percentage = Math.round((context.parsed / total) * 100)
              return `${context.label}: ${context.parsed} (${percentage}%)`
            },
          },
        },
      },
      cutout: "60%",
      animation: {
        animateRotate: true,
        duration: 1500,
      },
    },
  })
}

/**
 * Crea la gráfica de mantenimientos por mes
 */
function crearGraficaMantenimientosMes(data) {
  const ctx = document.getElementById("chart-mantenimientos-mes").getContext("2d")

  if (chartMantenimientosMes) {
    chartMantenimientosMes.destroy()
  }

  chartMantenimientosMes = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Preventivo",
          data: data.preventivo,
          backgroundColor: colors.success,
          borderRadius: 4,
          borderSkipped: false,
        },
        {
          label: "Correctivo",
          data: data.correctivo,
          backgroundColor: colors.danger,
          borderRadius: 4,
          borderSkipped: false,
        },
        {
          label: "Programado",
          data: data.programado,
          backgroundColor: colors.warning,
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            usePointStyle: true,
            padding: 15,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
        y: {
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  })
}

/**
 * Crea la gráfica de distribución por ubicaciones
 */
function crearGraficaUbicaciones(data) {
  const ctx = document.getElementById("chart-ubicaciones").getContext("2d")

  if (chartUbicaciones) {
    chartUbicaciones.destroy()
  }

  chartUbicaciones = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Equipos",
          data: data.values,
          backgroundColor: colors.primary,
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: "y",
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
        y: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  })
}

/**
 * Carga las tablas del dashboard
 */
function cargarTablas(tablas) {
  // Tabla de equipos que requieren atención
  cargarTablaEquiposAtencion(tablas.equiposAtencion)

  // Tabla de últimos mantenimientos
  cargarTablaUltimosMantenimientos(tablas.ultimosMantenimientos)
}

/**
 * Carga la tabla de equipos que requieren atención
 */
function cargarTablaEquiposAtencion(equipos) {
  const tbody = $("#tabla-equipos-atencion tbody")
  tbody.empty()

  if (equipos.length === 0) {
    tbody.append(`
            <tr>
                <td colspan="5" class="text-center">No hay equipos que requieran atención inmediata</td>
            </tr>
        `)
    return
  }

  equipos.forEach((equipo) => {
    const prioridadClass = equipo.prioridad.toLowerCase()
    const estadoClass = equipo.estado.toLowerCase()

    tbody.append(`
            <tr>
                <td>
                    <strong>${equipo.nombre}</strong><br>
                    <small class="text-muted">${equipo.codigo}</small>
                </td>
                <td>${equipo.ubicacion}</td>
                <td><span class="status-badge ${estadoClass}">${equipo.estado}</span></td>
                <td>
                    <strong>${equipo.proximoMantenimiento}</strong><br>
                    <small class="text-muted">${equipo.tiempoRestante}</small>
                </td>
                <td>
                    <button class="btn-table-action" onclick="verEquipo(${equipo.id})" title="Ver detalles">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn-table-action" onclick="programarMantenimiento(${equipo.id})" title="Programar mantenimiento">
                        <i class="bi bi-calendar-plus"></i>
                    </button>
                </td>
            </tr>
        `)
  })
}

/**
 * Carga la tabla de últimos mantenimientos
 */
function cargarTablaUltimosMantenimientos(mantenimientos) {
  const tbody = $("#tabla-ultimos-mantenimientos tbody")
  tbody.empty()

  if (mantenimientos.length === 0) {
    tbody.append(`
            <tr>
                <td colspan="4" class="text-center">No hay mantenimientos recientes</td>
            </tr>
        `)
    return
  }

  mantenimientos.forEach((mantenimiento) => {
    const tipoClass = mantenimiento.tipo.toLowerCase()
    const estadoClass = mantenimiento.estado.toLowerCase()

    tbody.append(`
            <tr>
                <td>${mantenimiento.fecha}</td>
                <td>
                    <strong>${mantenimiento.equipo}</strong><br>
                    <small class="text-muted">${mantenimiento.codigo}</small>
                </td>
                <td><span class="status-badge ${tipoClass}">${mantenimiento.tipo}</span></td>
                <td>${mantenimiento.descripcion}</td>
            </tr>
        `)
  })
}

/**
 * Carga la actividad reciente
 */
function cargarActividadReciente(actividades) {
  const container = $("#timeline-actividad")
  container.empty()

  if (actividades.length === 0) {
    container.append(`
            <div class="timeline-loading">
                <span>No hay actividad reciente</span>
            </div>
        `)
    return
  }

  actividades.forEach((actividad) => {
    const iconClass = getActivityIconClass(actividad.tipo)

    container.append(`
            <div class="timeline-item">
                <div class="timeline-icon ${iconClass}">
                    <i class="bi ${getActivityIcon(actividad.tipo)}"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">${actividad.titulo}</div>
                    <div class="timeline-description">${actividad.descripcion}</div>
                    <div class="timeline-time">${actividad.tiempo}</div>
                </div>
            </div>
        `)
  })
}

/**
 * Carga las alertas y notificaciones
 */
function cargarAlertas(alertas) {
  const container = $("#container-alertas")
  const totalAlertas = $("#total-alertas")

  container.empty()
  totalAlertas.text(alertas.length)

  if (alertas.length === 0) {
    container.append(`
            <div class="alert-loading">
                <span>No hay alertas pendientes</span>
            </div>
        `)
    return
  }

  alertas.forEach((alerta) => {
    const tipoClass = alerta.tipo.toLowerCase()

    container.append(`
            <div class="alert-item ${tipoClass}">
                <div class="alert-icon">
                    <i class="bi ${getAlertIcon(alerta.tipo)}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">${alerta.titulo}</div>
                    <div class="alert-description">${alerta.descripcion}</div>
                    <div class="alert-time">${alerta.tiempo}</div>
                </div>
            </div>
        `)
  })
}

/**
 * Funciones auxiliares
 */
function getActivityIconClass(tipo) {
  const classes = {
    mantenimiento: "success",
    alerta: "warning",
    error: "danger",
    info: "info",
  }
  return classes[tipo] || "info"
}

function getActivityIcon(tipo) {
  const icons = {
    mantenimiento: "bi-tools",
    alerta: "bi-exclamation-triangle",
    error: "bi-x-circle",
    info: "bi-info-circle",
  }
  return icons[tipo] || "bi-info-circle"
}

function getAlertIcon(tipo) {
  const icons = {
    critical: "bi-exclamation-triangle-fill",
    warning: "bi-exclamation-circle-fill",
    info: "bi-info-circle-fill",
  }
  return icons[tipo] || "bi-info-circle-fill"
}

/**
 * Anima un número desde 0 hasta el valor final
 */
function animarNumero(selector, valorFinal, duracion = 1500) {
  const elemento = $(selector)
  const valorInicial = 0
  const incremento = valorFinal / (duracion / 16)
  let valorActual = valorInicial

  const timer = setInterval(() => {
    valorActual += incremento
    if (valorActual >= valorFinal) {
      valorActual = valorFinal
      clearInterval(timer)
    }
    elemento.text(Math.floor(valorActual).toLocaleString())
  }, 16)
}

/**
 * Funciones de exportación
 */
function exportarResumenGeneral() {
  window.open("api/dashboard_data.php?export=resumen", "_blank")
}

function exportarGrafica(tipo) {
  let chart
  switch (tipo) {
    case "equipos":
      chart = chartEstadoEquipos
      break
    case "mantenimientos":
      chart = chartMantenimientosMes
      break
    case "ubicaciones":
      chart = chartUbicaciones
      break
  }

  if (chart) {
    const url = chart.toBase64Image()
    const link = document.createElement("a")
    link.download = `grafica-${tipo}-${new Date().toISOString().split("T")[0]}.png`
    link.href = url
    link.click()
  }
}

function exportarTabla(tipo) {
  window.open(`api/dashboard_data.php?export=tabla&tipo=${tipo}`, "_blank")
}

/**
 * Funciones de navegación
 */
function verEquipo(id) {
  window.location.href = `modulos/equipos/equipos/?id=${id}`
}

function programarMantenimiento(id) {
  window.location.href = `modulos/mantenimiento/programado/?equipo=${id}`
}

/**
 * Funciones de utilidad
 */
function mostrarCargando() {
  // Implementar indicadores de carga si es necesario
}

function ocultarCargando() {
  // Ocultar indicadores de carga
}

function mostrarError(mensaje) {
  console.error(mensaje)
  // Implementar notificación de error
}

function actualizarTimestamp() {
  const ahora = new Date()
  const timestamp = ahora.toLocaleString("es-ES", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
  $("#ultima-actualizacion").text(timestamp)
}

// Funciones adicionales para interactividad
$(document).on("click", ".stat-card", function () {
  $(this).addClass("clicked")
  setTimeout(() => {
    $(this).removeClass("clicked")
  }, 200)
})

// Efecto de hover en las gráficas
$("canvas").hover(
  function () {
    $(this).css("cursor", "pointer")
  },
  function () {
    $(this).css("cursor", "default")
  },
)
