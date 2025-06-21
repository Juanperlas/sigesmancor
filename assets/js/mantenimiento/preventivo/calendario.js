/**
 * Calendario de Mantenimientos Preventivos - SIGESMANCOR
 * Funciones para gestionar el calendario de mantenimientos preventivos
 */

document.addEventListener("DOMContentLoaded", () => {
  console.log("Calendario de Mantenimientos Preventivos JS cargado");

  // Inicializar el calendario
  initCalendar();

  // Cargar datos de las tablas laterales
  cargarProximosMantenimientos();
  cargarMantenimientosRecientes();

  // Configurar botón de actualización
  document.getElementById("refreshCalendar")?.addEventListener("click", () => {
    refreshCalendar();
  });
});

// Variable global para el calendario
let preventiveCalendar = null;

/**
 * Inicializa el calendario con eventos de mantenimiento preventivo
 */
function initCalendar() {
  const calendarEl = document.getElementById("preventiveMaintenanceCalendar");
  if (!calendarEl) return;

  // Mostrar loader
  showCalendarLoader();

  // Cargar eventos del calendario
  fetch("../../../api/mantenimiento/preventivo/calendario.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((eventos) => {
      console.log("Eventos del calendario cargados:", eventos);

      // Transformar los datos para FullCalendar
      const events = transformEventsData(eventos);

      // Inicializar FullCalendar
      preventiveCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "dayGridMonth,timeGridWeek,listMonth",
        },
        events: events,
        locale: "es", // Configurar idioma español
        height: "auto",
        contentHeight: "auto",
        aspectRatio: 1.8,
        eventTimeFormat: {
          hour: "2-digit",
          minute: "2-digit",
          meridiem: false,
        },
        eventClick: handleEventClick,
        eventClassNames: (arg) => {
          // Añadir clase según el estado del evento
          return arg.event.extendedProps.estado === "pendiente"
            ? ["event-pending"]
            : ["event-completed"];
        },
        dayMaxEvents: true,
        views: {
          dayGrid: {
            dayMaxEvents: 3,
          },
        },
        noEventsContent: "No hay mantenimientos programados",
        buttonText: {
          today: "Hoy",
          month: "Mes",
          week: "Semana",
          list: "Lista",
        },
        allDayText: "Todo el día",
        monthNames: [
          "Enero",
          "Febrero",
          "Marzo",
          "Abril",
          "Mayo",
          "Junio",
          "Julio",
          "Agosto",
          "Septiembre",
          "Octubre",
          "Noviembre",
          "Diciembre",
        ],
        monthNamesShort: [
          "Ene",
          "Feb",
          "Mar",
          "Abr",
          "May",
          "Jun",
          "Jul",
          "Ago",
          "Sep",
          "Oct",
          "Nov",
          "Dic",
        ],
        dayNames: [
          "Domingo",
          "Lunes",
          "Martes",
          "Miércoles",
          "Jueves",
          "Viernes",
          "Sábado",
        ],
        dayNamesShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
      });

      preventiveCalendar.render();

      // Ocultar loader
      hideCalendarLoader();
    })
    .catch((error) => {
      console.error("Error al cargar eventos del calendario:", error);

      // Mostrar mensaje de error
      const errorDiv = document.createElement("div");
      errorDiv.className = "alert alert-danger mt-3";
      errorDiv.textContent =
        "Error al cargar los eventos del calendario. Por favor, intente nuevamente.";
      calendarEl.parentNode.appendChild(errorDiv);

      // Ocultar loader
      hideCalendarLoader();
    });
}

/**
 * Transforma los datos de la API al formato requerido por FullCalendar
 */
function transformEventsData(data) {
  if (!data || !Array.isArray(data)) return [];

  return data.map((item) => {
    // Determinar la fecha a mostrar según el estado
    const eventDate =
      item.estado === "completado" && item.fecha_realizado
        ? item.fecha_realizado
        : item.fecha_programada;

    return {
      id: item.id,
      title: item.equipo_nombre || "Equipo sin nombre",
      start: eventDate,
      description: item.descripcion_razon || "Sin descripción",
      estado: item.estado,
      fecha_programada: item.fecha_programada,
      fecha_realizado: item.fecha_realizado,
      observaciones: item.observaciones,
      equipo_id: item.equipo_id,
      codigo: item.codigo || "Sin código",
      // Propiedades adicionales que puedan ser útiles
      allDay: true,
      className:
        item.estado === "pendiente" ? "event-pending" : "event-completed",
    };
  });
}

/**
 * Maneja el clic en un evento del calendario
 */
function handleEventClick(info) {
  // Obtener datos del evento
  const event = info.event;
  const props = event.extendedProps;

  // Actualizar el modal con los datos del evento
  document.getElementById("modalEquipo").textContent = event.title;
  document.getElementById("modalDescripcion").textContent =
    props.description || "Sin descripción";
  document.getElementById("modalFechaProgramada").textContent = formatDate(
    props.fecha_programada
  );

  // Mostrar/ocultar fecha realizada según el estado
  const rowFechaRealizada = document.getElementById("rowFechaRealizada");
  if (props.estado === "completado" && props.fecha_realizado) {
    rowFechaRealizada.style.display = "flex";
    document.getElementById("modalFechaRealizada").textContent = formatDate(
      props.fecha_realizado
    );
  } else {
    rowFechaRealizada.style.display = "none";
  }

  // Mostrar/ocultar observaciones
  const rowObservaciones = document.getElementById("rowObservaciones");
  if (props.observaciones) {
    rowObservaciones.style.display = "flex";
    document.getElementById("modalObservaciones").textContent =
      props.observaciones;
  } else {
    rowObservaciones.style.display = "none";
  }

  // Actualizar estado
  const estadoElement = document.getElementById("modalEstado");
  estadoElement.textContent =
    props.estado === "pendiente" ? "Pendiente" : "Completado";
  estadoElement.className =
    props.estado === "pendiente" ? "badge bg-warning" : "badge bg-info";

  // Actualizar enlace para ver detalle completo
  const btnVerDetalle = document.getElementById("btnVerDetalle");
  btnVerDetalle.href = `obtener.php?id=${event.id}`;

  // Mostrar el modal
  const maintenanceDetailsModal = new bootstrap.Modal(
    document.getElementById("maintenanceDetailsModal")
  );
  maintenanceDetailsModal.show();
}

/**
 * Carga los próximos mantenimientos para la tabla lateral
 */
function cargarProximosMantenimientos() {
  const tableBody = document.querySelector(
    "#proximosMantenimientosTable tbody"
  );
  if (!tableBody) return;

  // Realizar petición AJAX
  fetch("../../../api/mantenimiento/preventivo/calendario.php?tipo=proximos")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Próximos mantenimientos cargados:", data);

      // Limpiar tabla
      tableBody.innerHTML = "";

      // Verificar si hay datos
      if (!data || data.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center">No hay mantenimientos próximos</td>
          </tr>
        `;
        return;
      }

      // Llenar tabla con datos
      data.forEach((item) => {
        const row = document.createElement("tr");

        // Calcular clase para días restantes
        let diasRestantesClass = "normal";
        if (item.dias_restantes <= 3) {
          diasRestantesClass = "urgente";
        } else if (item.dias_restantes <= 7) {
          diasRestantesClass = "proximo";
        }

        row.innerHTML = `
          <td>${item.codigo || "Sin código"}</td>
          <td>${item.equipo_nombre}</td>
          <td>${formatDate(item.fecha_programada)}</td>
          <td><span class="dias-restantes ${diasRestantesClass}">${
          item.dias_restantes
        }</span></td>
        `;

        tableBody.appendChild(row);
      });
    })
    .catch((error) => {
      console.error("Error al cargar próximos mantenimientos:", error);

      // Mostrar mensaje de error
      tableBody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center text-danger">
            Error al cargar datos. Intente nuevamente.
          </td>
        </tr>
      `;
    });
}

/**
 * Carga los mantenimientos recientes para la tabla lateral
 */
function cargarMantenimientosRecientes() {
  const tableBody = document.querySelector(
    "#mantenimientosRecientesTable tbody"
  );
  if (!tableBody) return;

  // Realizar petición AJAX
  fetch("../../../api/mantenimiento/preventivo/calendario.php?tipo=recientes")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Mantenimientos recientes cargados:", data);

      // Limpiar tabla
      tableBody.innerHTML = "";

      // Verificar si hay datos
      if (!data || data.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="3" class="text-center">No hay mantenimientos recientes</td>
          </tr>
        `;
        return;
      }

      // Llenar tabla con datos
      data.forEach((item) => {
        const row = document.createElement("tr");

        row.innerHTML = `
          <td>${item.codigo || "Sin código"}</td>
          <td>${item.equipo_nombre}</td>
          <td>${formatDate(item.fecha_realizado)}</td>
        `;

        tableBody.appendChild(row);
      });
    })
    .catch((error) => {
      console.error("Error al cargar mantenimientos recientes:", error);

      // Mostrar mensaje de error
      tableBody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-danger">
            Error al cargar datos. Intente nuevamente.
          </td>
        </tr>
      `;
    });
}

/**
 * Actualiza el calendario y las tablas laterales
 */
function refreshCalendar() {
  // Recargar calendario
  if (preventiveCalendar) {
    preventiveCalendar.refetchEvents();
  } else {
    initCalendar();
  }

  // Recargar tablas laterales
  cargarProximosMantenimientos();
  cargarMantenimientosRecientes();
}

/**
 * Muestra el loader del calendario
 */
function showCalendarLoader() {
  const calendarEl = document.getElementById("preventiveMaintenanceCalendar");
  if (!calendarEl) return;

  // Crear loader si no existe
  let loader = calendarEl.querySelector(".calendar-loader");
  if (!loader) {
    loader = document.createElement("div");
    loader.className = "calendar-loader";
    loader.innerHTML =
      '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
    calendarEl.parentNode.appendChild(loader);
  }

  loader.style.display = "flex";
}

/**
 * Oculta el loader del calendario
 */
function hideCalendarLoader() {
  const calendarEl = document.getElementById("preventiveMaintenanceCalendar");
  if (!calendarEl) return;

  const loader = calendarEl.parentNode.querySelector(".calendar-loader");
  if (loader) {
    loader.style.display = "none";
  }
}

/**
 * Formatea una fecha para mostrarla en el formato DD/MM/YYYY
 */
function formatDate(dateString) {
  if (!dateString) return "No disponible";

  const date = new Date(dateString);
  return date.toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
