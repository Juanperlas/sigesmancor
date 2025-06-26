/**
 * JavaScript para la gestión de horarios (manuales y predefinidos)
 * SIGESMANCOR
 */

// Variables globales
let datosEquipos = []
let datosComponentes = []
const $ = window.jQuery

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  // Cargar datos iniciales
  cargarDatos()

  // Eventos para botones
  $("#btn-guardar-manuales").on("click", guardarHorasManuales)
  $("#btn-aplicar-predefinidos").on("click", aplicarHorasPredefinidas)

  // Eventos para buscadores
  $("#buscador-manual").on("input", function () {
    filtrarTabla("manual", $(this).val())
  })

  $("#buscador-predefinido").on("input", function () {
    filtrarTabla("predefinido", $(this).val())
  })

  // Evento para checkbox general
  $("#check-todos").on("change", function () {
    const isChecked = $(this).is(":checked")
    $("#tabla-predefinidos .check-predefinido:visible").prop("checked", isChecked)
    actualizarContadorSeleccionados()
  })

  // Evento para checkboxes individuales
  $(document).on("change", "#tabla-predefinidos .check-predefinido", () => {
    actualizarContadorSeleccionados()
    actualizarCheckboxGeneral()
  })

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
 * Carga los datos desde el servidor
 */
function cargarDatos() {
  if (window.showLoading) window.showLoading()

  $.ajax({
    url: getUrl("api/administracion/horarios/listar.php"),
    type: "POST",
    data: { tipo: "todos" },
    success: (response) => {
      if (window.hideLoading) window.hideLoading()

      if (response.success) {
        datosEquipos = response.equipos || []
        datosComponentes = response.componentes || []

        // Renderizar tablas con animación
        setTimeout(() => {
          renderizarTablaManual()
          renderizarTablaPredefinidos()
          actualizarContadores()
        }, 300)
      } else {
        mostrarError("Error al cargar los datos: " + (response.error || "Error desconocido"))
      }
    },
    error: (xhr, status, error) => {
      if (window.hideLoading) window.hideLoading()
      console.error("Error en la solicitud AJAX:", error)
      mostrarError("Error al comunicarse con el servidor: " + error)
    },
  })
}

/**
 * Renderiza la tabla manual
 */
function renderizarTablaManual() {
  const tbody = $("#tabla-manuales tbody")
  tbody.empty()

  const todosDatos = [...datosEquipos, ...datosComponentes]

  if (todosDatos.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="4" class="table-empty">
          <i class="bi bi-inbox"></i>
          <div>No hay equipos o componentes disponibles</div>
        </td>
      </tr>
    `)
    return
  }

  todosDatos.forEach((item, index) => {
    const fila = $(`
      <tr class="fade-in-up" style="animation-delay: ${index * 0.05}s">
        <td class="align-middle">${item.nombre}</td>
        <td class="align-middle text-center">${item.codigo}</td>
        <td class="align-middle text-center">${item.tipo_equipo}</td>
        <td class="align-middle text-center">
          <input type="number" 
                 class="form-control2 form-control-sm orometro-input" 
                 value="0.00" 
                 data-id="${item.id}" 
                 data-tipo="${item.tipo}" 
                 step="0.01" 
                 min="0"
                 placeholder="0.00">
        </td>
      </tr>
    `)
    tbody.append(fila)
  })
}

/**
 * Renderiza la tabla de predefinidos
 */
function renderizarTablaPredefinidos() {
  const tbody = $("#tabla-predefinidos tbody")
  tbody.empty()

  const todosDatos = [...datosEquipos, ...datosComponentes]

  if (todosDatos.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="5" class="table-empty">
          <i class="bi bi-inbox"></i>
          <div>No hay equipos o componentes disponibles</div>
        </td>
      </tr>
    `)
    return
  }

  todosDatos.forEach((item, index) => {
    const fila = $(`
      <tr class="slide-in-right" style="animation-delay: ${index * 0.05}s">
        <td class="align-middle text-center">
          <input type="checkbox" 
                 class="form-check-input check-predefinido" 
                 data-id="${item.id}" 
                 data-tipo="${item.tipo}">
        </td>
        <td class="align-middle">${item.nombre}</td>
        <td class="align-middle text-center">${item.codigo}</td>
        <td class="align-middle text-center">${item.tipo_equipo}</td>
        <td class="align-middle text-center">
          <input type="number" 
                 class="form-control2 form-control-sm orometro-input" 
                 value="${item.horas}" 
                 data-id="${item.id}" 
                 data-tipo="${item.tipo}" 
                 step="0.01" 
                 min="0"
                 placeholder="0.00">
        </td>
      </tr>
    `)
    tbody.append(fila)
  })

  actualizarContadorSeleccionados()
}

/**
 * Actualiza los contadores de registros
 */
function actualizarContadores() {
  const totalEquipos = datosEquipos.length
  const totalComponentes = datosComponentes.length
  const total = totalEquipos + totalComponentes

  // Agregar contador en la pestaña manual
  if (!$("#contador-manual").length) {
    $("#manual .card-body").prepend(`
      <div id="contador-manual" class="records-counter">
        <i class="bi bi-info-circle"></i>
        <strong>${total}</strong> registros disponibles 
        (<strong>${totalEquipos}</strong> equipos, <strong>${totalComponentes}</strong> componentes)
      </div>
    `)
  }

  // Agregar contador en la pestaña predefinidos
  if (!$("#contador-predefinidos").length) {
    $("#predefinido .card-body").prepend(`
      <div id="contador-predefinidos" class="records-counter">
        <i class="bi bi-info-circle"></i>
        <strong>${total}</strong> registros disponibles 
        (<strong>${totalEquipos}</strong> equipos, <strong>${totalComponentes}</strong> componentes)
        <span id="seleccionados-info" class="ms-2" style="display: none;"></span>
      </div>
    `)
  }
}

/**
 * Actualiza el contador de seleccionados
 */
function actualizarContadorSeleccionados() {
  const seleccionados = $("#tabla-predefinidos .check-predefinido:checked:visible").length
  const infoSpan = $("#seleccionados-info")

  if (seleccionados > 0) {
    infoSpan.html(`| <strong class="text-success">${seleccionados}</strong> seleccionados`).show()
  } else {
    infoSpan.hide()
  }
}

/**
 * Actualiza el estado del checkbox general
 */
function actualizarCheckboxGeneral() {
  const totalVisibles = $("#tabla-predefinidos .check-predefinido:visible").length
  const seleccionados = $("#tabla-predefinidos .check-predefinido:checked:visible").length

  const checkGeneral = $("#check-todos")

  if (seleccionados === 0) {
    checkGeneral.prop("indeterminate", false).prop("checked", false)
  } else if (seleccionados === totalVisibles) {
    checkGeneral.prop("indeterminate", false).prop("checked", true)
  } else {
    checkGeneral.prop("indeterminate", true)
  }
}

/**
 * Filtra la tabla según el texto de búsqueda
 */
function filtrarTabla(tipo, textoBusqueda) {
  const tabla = tipo === "manual" ? "#tabla-manuales" : "#tabla-predefinidos"
  const filas = $(tabla + " tbody tr")

  if (!textoBusqueda.trim()) {
    filas.show()
    if (tipo === "predefinido") {
      actualizarCheckboxGeneral()
      actualizarContadorSeleccionados()
    }
    return
  }

  const busqueda = textoBusqueda.toLowerCase()

  filas.each(function () {
    const fila = $(this)
    const texto = fila.text().toLowerCase()

    if (texto.includes(busqueda)) {
      fila.show()
    } else {
      fila.hide()
    }
  })

  if (tipo === "predefinido") {
    actualizarCheckboxGeneral()
    actualizarContadorSeleccionados()
  }
}

/**
 * Guarda las horas ingresadas manualmente
 */
function guardarHorasManuales() {
  const registros = []
  let totalEquipos = 0
  let totalComponentes = 0

  // Recopilar datos de la tabla
  $("#tabla-manuales .orometro-input").each(function () {
    const $input = $(this)
    const horas = Number.parseFloat($input.val())

    if (horas > 0) {
      const tipo = $input.data("tipo")
      registros.push({
        id: $input.data("id"),
        tipo: tipo,
        horas: horas,
      })

      if (tipo === "equipo") {
        totalEquipos++
      } else {
        totalComponentes++
      }
    }
  })

  if (registros.length === 0) {
    mostrarAdvertencia("No hay registros con horas mayores a 0 para procesar.")
    return
  }

  // Mostrar modal de confirmación elegante
  mostrarModalConfirmacionElegante({
    titulo: "Confirmar Carga Manual de Horas",
    icono: "bi-keyboard",
    mensaje: "¿Está seguro de que desea agregar las horas trabajadas a los equipos y componentes seleccionados?",
    totalRegistros: registros.length,
    totalEquipos: totalEquipos,
    totalComponentes: totalComponentes,
    callback: () => enviarHorasManuales(registros),
  })
}

/**
 * Aplica las horas predefinidas a los equipos/componentes seleccionados
 */
function aplicarHorasPredefinidas() {
  const registros = []
  let totalEquipos = 0
  let totalComponentes = 0

  // Recopilar datos de la tabla
  $("#tabla-predefinidos tbody tr:visible").each(function () {
    const $row = $(this)
    const $checkbox = $row.find(".check-predefinido")
    const $input = $row.find(".orometro-input")

    if ($checkbox.is(":checked")) {
      const horas = Number.parseFloat($input.val())

      if (horas > 0) {
        const tipo = $checkbox.data("tipo")
        registros.push({
          id: $checkbox.data("id"),
          tipo: tipo,
          horas: horas,
          seleccionado: true,
        })

        if (tipo === "equipo") {
          totalEquipos++
        } else {
          totalComponentes++
        }
      }
    }
  })

  if (registros.length === 0) {
    mostrarAdvertencia("No hay registros seleccionados con horas mayores a 0 para procesar.")
    return
  }

  // Mostrar modal de confirmación elegante
  mostrarModalConfirmacionElegante({
    titulo: "Confirmar Aplicación de Horas Predefinidas",
    icono: "bi-gear",
    mensaje: "¿Está seguro de que desea aplicar las horas predefinidas a los equipos y componentes seleccionados?",
    totalRegistros: registros.length,
    totalEquipos: totalEquipos,
    totalComponentes: totalComponentes,
    callback: () => enviarHorasPredefinidas(registros),
  })
}

/**
 * Muestra un modal de confirmación elegante
 */
function mostrarModalConfirmacionElegante(opciones) {
  const modalHtml = `
    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="${opciones.icono}"></i>
              ${opciones.titulo}
            </h5>
          </div>
          <div class="modal-body">
            <div class="modal-confirmation-content">
              <div class="modal-confirmation-icon">
                <i class="bi bi-question-circle"></i>
              </div>
              <div class="modal-confirmation-text">
                ${opciones.mensaje}
              </div>
              <div class="modal-confirmation-details">
                <div class="modal-stats">
                  <div class="modal-stat-item">
                    <span class="modal-stat-number">${opciones.totalRegistros}</span>
                    <span class="modal-stat-label">Total de Registros</span>
                  </div>
                  <div class="modal-stat-item">
                    <span class="modal-stat-number">${opciones.totalEquipos}</span>
                    <span class="modal-stat-label">Equipos</span>
                  </div>
                  <div class="modal-stat-item">
                    <span class="modal-stat-number">${opciones.totalComponentes}</span>
                    <span class="modal-stat-label">Componentes</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-modal btn-cancelar" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i>
              Cancelar
            </button>
            <button type="button" class="btn btn-modal btn-confirmar" id="btnConfirmarModal">
              <i class="bi bi-check-circle me-1"></i>
              Confirmar
            </button>
          </div>
        </div>
      </div>
    </div>
  `

  // Remover modal anterior si existe
  $("#modalConfirmacion").remove()

  // Agregar nuevo modal
  $("body").append(modalHtml)

  // Configurar eventos
  $("#btnConfirmarModal").on("click", () => {
    $("#modalConfirmacion").modal("hide")
    opciones.callback()
  })

  // Mostrar modal
  $("#modalConfirmacion").modal("show")
}

/**
 * Envía las horas manuales al servidor
 */
function enviarHorasManuales(registros) {
  if (window.showLoading) window.showLoading()

  $.ajax({
    url: getUrl("api/administracion/horarios/agregar-manuales.php"),
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({ registros: registros }),
    success: (response) => {
      if (window.hideLoading) window.hideLoading()

      if (response.success) {
        mostrarExito(response.message)

        // Limpiar los inputs con animación
        $("#tabla-manuales .orometro-input").each(function (index) {
          const $input = $(this)
          setTimeout(() => {
            $input.val("0.00").addClass("fade-in-up")
          }, index * 50)
        })
      } else {
        mostrarError(response.error || "Error al procesar la solicitud")
      }
    },
    error: (xhr, status, error) => {
      if (window.hideLoading) window.hideLoading()
      console.error("Error en la solicitud AJAX:", error)
      mostrarError("Error al comunicarse con el servidor: " + error)
    },
  })
}

/**
 * Envía las horas predefinidas al servidor
 */
function enviarHorasPredefinidas(registros) {
  if (window.showLoading) window.showLoading()

  $.ajax({
    url: getUrl("api/administracion/horarios/aplicar-predefinidos.php"),
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({ registros: registros }),
    success: (response) => {
      if (window.hideLoading) window.hideLoading()

      if (response.success) {
        mostrarExito(response.message)

        // Limpiar checkboxes con animación
        $("#tabla-predefinidos .check-predefinido").each(function (index) {
          const $checkbox = $(this)
          setTimeout(() => {
            $checkbox.prop("checked", false).closest("tr").addClass("fade-in-up")
          }, index * 30)
        })

        $("#check-todos").prop("checked", false)
        actualizarContadorSeleccionados()
      } else {
        mostrarError(response.error || "Error al procesar la solicitud")
      }
    },
    error: (xhr, status, error) => {
      if (window.hideLoading) window.hideLoading()
      console.error("Error en la solicitud AJAX:", error)
      mostrarError("Error al comunicarse con el servidor: " + error)
    },
  })
}

/**
 * Funciones auxiliares para mostrar mensajes
 */
function mostrarExito(mensaje) {
  if (window.showSuccessToast) {
    window.showSuccessToast(mensaje)
  } else {
    alert(mensaje)
  }
}

function mostrarError(mensaje) {
  if (window.showErrorToast) {
    window.showErrorToast(mensaje)
  } else {
    alert(mensaje)
  }
}

function mostrarAdvertencia(mensaje) {
  if (window.showWarningToast) {
    window.showWarningToast(mensaje)
  } else {
    alert(mensaje)
  }
}
