/**
 * Dashboard JavaScript - SIGESMANCOR
 * Funciones para gráficos y componentes interactivos del dashboard
 */

document.addEventListener("DOMContentLoaded", () => {
  console.log("Dashboard JS cargado");

  // Inicializar todos los componentes
  initCharts();
  initCalendar();
  initTooltips();
  initCounters();

  // Manejar cambios de período en los gráficos
  setupTimePeriodSelectors();

  // Manejar filtros de categorías
  setupCategoryFilters();

  // Manejar botón de actualización
  document.getElementById("refreshDashboard")?.addEventListener("click", () => {
    initCharts();
    initCalendar();
  });
});

// Variables globales para los gráficos
let equipmentStatusChart = null;
let maintenanceTypeChart = null;
let maintenanceHistoryChart = null;
let maintenanceTrendChart = null;
let equipmentCategoryChart = null;

/**
 * Inicializa todos los gráficos del dashboard
 */
function initCharts() {
  // Cargar datos iniciales para los gráficos
  loadChartData("estadisticas", null, null, function (data) {
    // Actualizar estadísticas si es necesario
    console.log("Estadísticas cargadas:", data);
  });

  loadChartData(
    "historial_mantenimiento",
    "30d",
    "todos",
    renderMaintenanceHistoryChart
  );
  loadChartData(
    "tendencia_mantenimiento",
    "7d",
    "todos",
    renderMaintenanceTrendChart
  );

  // Cargar datos para la tabla de próximos mantenimientos
  loadTableData("proximos_mantenimientos", renderMaintenanceTable);

  // Cargar eventos para el calendario
  loadTableData("eventos_calendario", function (data) {
    console.log("Eventos del calendario cargados:", data);
  });
}

/**
 * Carga datos para los gráficos mediante AJAX
 */
function loadChartData(tipo, periodo = null, categoria = null, callback) {
  // Mostrar loader
  const chartId = getChartIdByType(tipo);
  if (chartId) {
    showChartLoader(chartId);
  }

  // Construir URL con parámetros
  let url = `api/dashboard_data.php?tipo=${tipo}`;
  if (periodo) {
    url += `&periodo=${periodo}`;
  }
  if (categoria && categoria !== "todos") {
    url += `&categoria=${categoria}`;
  }

  console.log("Cargando datos desde:", url);

  // Realizar la petición AJAX
  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log(`Datos recibidos para ${tipo}:`, data);

      // Ocultar loader
      if (chartId) {
        hideChartLoader(chartId);
      }

      // Llamar al callback con los datos
      if (typeof callback === "function") {
        callback(data);
      }
    })
    .catch((error) => {
      console.error(`Error al cargar datos para ${tipo}:`, error);

      // Ocultar loader
      if (chartId) {
        hideChartLoader(chartId);
      }

      // Mostrar mensaje de error en el gráfico
      if (chartId) {
        const chartElement = document.getElementById(chartId);
        if (chartElement) {
          chartElement.innerHTML = `
            <div class="alert alert-danger">
              Error al cargar datos: ${error.message}
            </div>
          `;
        }
      }
    });
}

/**
 * Carga datos para las tablas mediante AJAX
 */
function loadTableData(tipo, callback) {
  // Realizar la petición AJAX
  fetch(`api/dashboard_data.php?tipo=${tipo}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log(`Datos recibidos para tabla ${tipo}:`, data);

      // Llamar al callback con los datos
      if (typeof callback === "function") {
        callback(data);
      }
    })
    .catch((error) => {
      console.error(`Error al cargar datos para tabla ${tipo}:`, error);

      // Mostrar mensaje de error en la tabla
      const tableBody = document.querySelector(".maintenance-table tbody");
      if (tableBody) {
        tableBody.innerHTML = `
            <tr>
              <td colspan="4" class="text-center">
                <div class="alert alert-danger mb-0">
                  Error al cargar datos: ${error.message}
                </div>
              </td>
            </tr>
          `;
      }
    });
}

/**
 * Obtiene el ID del contenedor del gráfico según el tipo
 */
function getChartIdByType(tipo) {
  const chartMap = {
    estado_equipos: "equipmentStatusChart",
    tipos_mantenimiento: "maintenanceTypeChart",
    historial_mantenimiento: "maintenanceHistoryChart",
    tendencia_mantenimiento: "maintenanceTrendChart",
    categorias_equipos: "equipmentCategoryChart",
  };

  return chartMap[tipo];
}

/**
 * Muestra el loader para un gráfico
 */
function showChartLoader(chartId) {
  const chartContainer = document.getElementById(chartId);
  if (!chartContainer) return;

  const chartBody = chartContainer.closest(".chart-body");
  if (!chartBody) return;

  // Crear loader si no existe
  let loader = chartBody.querySelector(".chart-loader");
  if (!loader) {
    loader = document.createElement("div");
    loader.className = "chart-loader";
    loader.innerHTML = '<div class="spinner"></div>';
    chartBody.appendChild(loader);
  }

  loader.style.display = "flex";
}

/**
 * Oculta el loader para un gráfico
 */
function hideChartLoader(chartId) {
  const chartContainer = document.getElementById(chartId);
  if (!chartContainer) return;

  const chartBody = chartContainer.closest(".chart-body");
  if (!chartBody) return;

  const loader = chartBody.querySelector(".chart-loader");
  if (loader) {
    loader.style.display = "none";
  }
}

/**
 * Renderiza el gráfico de estado de equipos (donut chart)
 */
function renderEquipmentStatusChart(data) {
  const chartElement = document.getElementById("equipmentStatusChart");
  if (!chartElement) return;

  // Verificar si hay datos
  if (!data.series || data.series.length === 0) {
    chartElement.innerHTML = `
        <div class="alert alert-info">
          No hay datos disponibles para mostrar.
        </div>
      `;
    return;
  }

  const options = {
    chart: {
      type: "donut",
      height: 300,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    series: data.series,
    labels: data.labels,
    colors: ["#2ecc71", "#3498db", "#e74c3c", "#95a5a6", "#f39c12"],
    legend: {
      position: "bottom",
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
    plotOptions: {
      pie: {
        donut: {
          size: "70%",
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: "14px",
              fontWeight: 600,
            },
            value: {
              show: true,
              fontSize: "16px",
              fontWeight: 700,
              formatter: (val) => val,
            },
            total: {
              show: true,
              label: "Total",
              fontSize: "14px",
              fontWeight: 600,
              formatter: (w) =>
                w.globals.seriesTotals.reduce((a, b) => a + b, 0),
            },
          },
        },
      },
    },
    dataLabels: {
      enabled: false,
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 250,
          },
          legend: {
            position: "bottom",
          },
        },
      },
    ],
    tooltip: {
      y: {
        formatter: (val, { seriesIndex, dataPointIndex, w }) => {
          const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
          const percent = ((val * 100) / total).toFixed(1);
          return `${val} (${percent}%)`;
        },
      },
    },
  };

  try {
    // Destruir gráfico existente si lo hay
    if (equipmentStatusChart) {
      equipmentStatusChart.destroy();
      equipmentStatusChart = null;
    }

    // Crear nuevo gráfico
    equipmentStatusChart = new ApexCharts(chartElement, options);
    equipmentStatusChart.render();
  } catch (error) {
    console.error(
      "Error al renderizar el gráfico de estado de equipos:",
      error
    );
    chartElement.innerHTML = `
        <div class="alert alert-danger">
          Error al renderizar el gráfico: ${error.message}
        </div>
      `;
  }
}

/**
 * Renderiza el gráfico de tipos de mantenimiento (pie chart)
 */
function renderMaintenanceTypeChart(data) {
  const chartElement = document.getElementById("maintenanceTypeChart");
  if (!chartElement) return;

  // Verificar si hay datos
  if (!data.series || data.series.length === 0) {
    chartElement.innerHTML = `
        <div class="alert alert-info">
          No hay datos disponibles para mostrar.
        </div>
      `;
    return;
  }

  const options = {
    chart: {
      type: "pie",
      height: 300,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    series: data.series,
    labels: data.labels,
    colors: ["#4361ee", "#e74c3c", "#f39c12"],
    legend: {
      position: "bottom",
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
    dataLabels: {
      enabled: true,
      formatter: (val, { seriesIndex, dataPointIndex, w }) =>
        Math.round(val) + "%",
      style: {
        fontSize: "12px",
        fontWeight: 500,
      },
      dropShadow: {
        enabled: false,
      },
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            height: 250,
          },
          legend: {
            position: "bottom",
          },
        },
      },
    ],
    tooltip: {
      y: {
        formatter: (val, { seriesIndex, dataPointIndex, w }) => {
          const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
          const percent = ((val * 100) / total).toFixed(1);
          return `${val} (${percent}%)`;
        },
      },
    },
  };

  try {
    // Destruir gráfico existente si lo hay
    if (maintenanceTypeChart) {
      maintenanceTypeChart.destroy();
      maintenanceTypeChart = null;
    }

    // Crear nuevo gráfico
    maintenanceTypeChart = new ApexCharts(chartElement, options);
    maintenanceTypeChart.render();
  } catch (error) {
    console.error(
      "Error al renderizar el gráfico de tipos de mantenimiento:",
      error
    );
    chartElement.innerHTML = `
        <div class="alert alert-danger">
          Error al renderizar el gráfico: ${error.message}
        </div>
      `;
  }
}

/**
 * Renderiza el gráfico de historial de mantenimiento (line chart)
 */
function renderMaintenanceHistoryChart(data) {
  const chartElement = document.getElementById("maintenanceHistoryChart");
  if (!chartElement) return;

  // Verificar si hay datos
  if (!data.fechas || data.fechas.length === 0) {
    chartElement.innerHTML = `
        <div class="alert alert-info">
          No hay datos disponibles para mostrar.
        </div>
      `;
    return;
  }

  const options = {
    chart: {
      type: "area",
      height: 300,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
      zoom: {
        enabled: false,
      },
    },
    series: data.series,
    xaxis: {
      categories: data.fechas,
      labels: {
        style: {
          fontSize: "10px",
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        style: {
          fontSize: "10px",
        },
      },
    },
    colors: ["#4361ee", "#e74c3c", "#f39c12"],
    stroke: {
      curve: "smooth",
      width: 2,
    },
    fill: {
      type: "gradient",
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.4,
        opacityTo: 0.1,
        stops: [0, 100],
      },
    },
    legend: {
      position: "bottom",
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
      size: 4,
      strokeWidth: 0,
      hover: {
        size: 6,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
    },
  };

  try {
    // Destruir gráfico existente si lo hay
    if (maintenanceHistoryChart) {
      maintenanceHistoryChart.destroy();
      maintenanceHistoryChart = null;
    }

    // Crear nuevo gráfico
    maintenanceHistoryChart = new ApexCharts(chartElement, options);
    maintenanceHistoryChart.render();
  } catch (error) {
    console.error(
      "Error al renderizar el gráfico de historial de mantenimiento:",
      error
    );
    chartElement.innerHTML = `
        <div class="alert alert-danger">
          Error al renderizar el gráfico: ${error.message}
        </div>
      `;
  }
}

/**
 * Renderiza el gráfico de tendencia de mantenimientos (area chart)
 */
function renderMaintenanceTrendChart(data) {
  const chartElement = document.getElementById("maintenanceTrendChart");
  if (!chartElement) return;

  // Verificar si hay datos
  if (!data.fechas || data.fechas.length === 0) {
    chartElement.innerHTML = `
        <div class="alert alert-info">
          No hay datos disponibles para mostrar.
        </div>
      `;
    return;
  }

  const options = {
    chart: {
      type: "area",
      height: 300,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
      zoom: {
        enabled: false,
      },
    },
    series: data.series,
    xaxis: {
      categories: data.fechas,
      labels: {
        style: {
          fontSize: "10px",
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        style: {
          fontSize: "10px",
        },
      },
    },
    colors: ["#2ecc71", "#f39c12"],
    stroke: {
      curve: "smooth",
      width: 2,
    },
    fill: {
      type: "gradient",
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.4,
        opacityTo: 0.1,
        stops: [0, 100],
      },
    },
    legend: {
      position: "bottom",
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
      size: 4,
      strokeWidth: 0,
      hover: {
        size: 6,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
    },
  };

  try {
    // Destruir gráfico existente si lo hay
    if (maintenanceTrendChart) {
      maintenanceTrendChart.destroy();
      maintenanceTrendChart = null;
    }

    // Crear nuevo gráfico
    maintenanceTrendChart = new ApexCharts(chartElement, options);
    maintenanceTrendChart.render();
  } catch (error) {
    console.error(
      "Error al renderizar el gráfico de tendencia de mantenimientos:",
      error
    );
    chartElement.innerHTML = `
        <div class="alert alert-danger">
          Error al renderizar el gráfico: ${error.message}
        </div>
      `;
  }
}

/**
 * Renderiza el gráfico de categorías de equipos (bar chart)
 */
function renderEquipmentCategoryChart(data) {
  const chartElement = document.getElementById("equipmentCategoryChart");
  if (!chartElement) return;

  // Verificar si hay datos
  if (!data.categorias || data.categorias.length === 0) {
    chartElement.innerHTML = `
        <div class="alert alert-info">
          No hay datos disponibles para mostrar.
        </div>
      `;
    return;
  }

  const options = {
    chart: {
      type: "bar",
      height: 300,
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    series: data.series,
    xaxis: {
      categories: data.categorias,
      labels: {
        style: {
          fontSize: "10px",
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        style: {
          fontSize: "10px",
        },
      },
    },
    colors: ["#4361ee", "#2ecc71", "#f39c12", "#9b59b6", "#3498db", "#e74c3c"],
    plotOptions: {
      bar: {
        borderRadius: 4,
        columnWidth: "60%",
        distributed: true,
        dataLabels: {
          position: "top",
        },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => val,
      offsetY: -20,
      style: {
        fontSize: "10px",
        colors: ["#304758"],
      },
    },
    legend: {
      show: false,
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
    tooltip: {
      shared: false,
      intersect: true,
    },
  };

  try {
    // Destruir gráfico existente si lo hay
    if (equipmentCategoryChart) {
      equipmentCategoryChart.destroy();
      equipmentCategoryChart = null;
    }

    // Crear nuevo gráfico
    equipmentCategoryChart = new ApexCharts(chartElement, options);
    equipmentCategoryChart.render();
  } catch (error) {
    console.error(
      "Error al renderizar el gráfico de categorías de equipos:",
      error
    );
    chartElement.innerHTML = `
        <div class="alert alert-danger">
          Error al renderizar el gráfico: ${error.message}
        </div>
      `;
  }
}

/**
 * Renderiza la tabla de próximos mantenimientos
 */
function renderMaintenanceTable(data) {
  const tableBody = document.querySelector(".maintenance-table tbody");
  if (!tableBody) return;

  // Limpiar tabla
  tableBody.innerHTML = "";

  // Verificar si hay datos
  if (!data || data.length === 0) {
    tableBody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center">
            No hay mantenimientos programados próximamente.
          </td>
        </tr>
      `;
    return;
  }

  // Añadir filas
  data.forEach((item) => {
    const row = document.createElement("tr");

    // Determinar clase de estado
    let statusClass = "pending";
    if (item.estado === "completado") {
      statusClass = "completed";
    } else if (item.tipo === "programado") {
      statusClass = "programmed";
    }

    // Determinar texto de tipo
    let tipoTexto = "Preventivo";
    if (item.tipo === "correctivo") {
      tipoTexto = "Correctivo";
    } else if (item.tipo === "programado") {
      tipoTexto = "Programado";
    }

    // Crear contenido de la fila
    row.innerHTML = `
        <td>${item.equipo}</td>
        <td>${tipoTexto}</td>
        <td>${item.fecha}</td>
        <td><span class="status ${statusClass}">${
      item.estado === "pendiente" ? "Pendiente" : "Completado"
    }</span></td>
      `;

    tableBody.appendChild(row);
  });
}

/**
 * Inicializa el calendario con eventos de mantenimiento
 */
function initCalendar() {
  const calendarEl = document.getElementById("maintenanceCalendar");
  if (!calendarEl) return;

  // Cargar eventos del calendario
  fetch("api/dashboard_data.php?tipo=eventos_calendario")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((eventos) => {
      console.log("Eventos del calendario:", eventos);

      // Inicializar FullCalendar
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "dayGridMonth,listWeek",
        },
        events: eventos,
        locale: "es",
        height: "auto",
        contentHeight: "auto",
        aspectRatio: 1.5,
        eventTimeFormat: {
          hour: "2-digit",
          minute: "2-digit",
          meridiem: false,
        },
        eventClick: (info) => {
          // Mostrar detalles del evento
          alert(
            `${info.event.title}\n${info.event.extendedProps.description || ""}`
          );
        },
        eventClassNames: (arg) => {
          // Añadir clase según el tipo de evento
          return [arg.event.extendedProps.tipo];
        },
        dayMaxEvents: true,
        views: {
          dayGrid: {
            dayMaxEvents: 2,
          },
        },
        noEventsContent: "No hay eventos programados",
      });

      calendar.render();
    })
    .catch((error) => {
      console.error("Error al cargar eventos del calendario:", error);
      calendarEl.innerHTML = `
          <div class="alert alert-danger">
            Error al cargar el calendario: ${error.message}
          </div>
        `;
    });
}

/**
 * Inicializa los tooltips de Bootstrap
 */
function initTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );

  // Check if Bootstrap's tooltip class is available
  if (
    typeof bootstrap !== "undefined" &&
    typeof bootstrap.Tooltip === "function"
  ) {
    tooltipTriggerList.map(
      (tooltipTriggerEl) =>
        new bootstrap.Tooltip(tooltipTriggerEl, {
          boundary: document.body,
        })
    );
  } else {
    console.warn(
      "Bootstrap tooltips are not initialized. Ensure Bootstrap is properly loaded."
    );
  }
}

/**
 * Inicializa los contadores animados
 */
function initCounters() {
  const counters = document.querySelectorAll(".counter-value");

  counters.forEach((counter) => {
    const target = Number.parseInt(counter.getAttribute("data-target") || "0");
    const duration = 1500; // ms
    const step = target / (duration / 16); // 60fps

    let current = 0;
    const updateCounter = () => {
      current += step;
      if (current < target) {
        counter.textContent = Math.ceil(current);
        requestAnimationFrame(updateCounter);
      } else {
        counter.textContent = target;
      }
    };

    updateCounter();
  });
}

/**
 * Configura los selectores de período de tiempo para los gráficos
 */
function setupTimePeriodSelectors() {
  const timePeriodButtons = document.querySelectorAll(".time-period .btn");

  timePeriodButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Remover la clase active de todos los botones
      this.parentElement.querySelectorAll(".btn").forEach((btn) => {
        btn.classList.remove("active");
      });

      // Añadir la clase active al botón clickeado
      this.classList.add("active");

      // Obtener el período seleccionado
      const period = this.dataset.period;

      // Obtener el tipo de gráfico
      const chartCard = this.closest(".chart-card");
      const chartBody = chartCard.querySelector(".chart-body");
      const chartCanvas = chartBody.querySelector("div");

      if (chartCanvas) {
        const chartId = chartCanvas.id;

        // Determinar el tipo de datos a cargar
        let dataType = "";
        if (chartId === "maintenanceHistoryChart") {
          dataType = "historial_mantenimiento";
        } else if (chartId === "maintenanceTrendChart") {
          dataType = "tendencia_mantenimiento";
        } else if (chartId === "maintenanceTypeChart") {
          dataType = "tipos_mantenimiento";
        }

        // Cargar datos actualizados
        if (dataType) {
          // Obtener categoría seleccionada si existe
          const filterDropdown = chartCard.querySelector(".filter-dropdown");
          let categoria = "todos";

          if (filterDropdown) {
            const activeFilter = filterDropdown.querySelector(
              ".dropdown-item.active"
            );
            if (activeFilter) {
              categoria = activeFilter.dataset.categoria;
            }
          }

          loadChartData(
            dataType,
            period,
            categoria,
            getCallbackForChart(chartId)
          );
        }
      }
    });
  });
}

/**
 * Configura los filtros de categorías para los gráficos
 */
function setupCategoryFilters() {
  const filterDropdowns = document.querySelectorAll(".filter-dropdown");

  filterDropdowns.forEach((dropdown) => {
    const dropdownItems = dropdown.querySelectorAll(".dropdown-item");

    dropdownItems.forEach((item) => {
      item.addEventListener("click", function (e) {
        e.preventDefault();

        // Remover la clase active de todos los items
        dropdownItems.forEach((di) => di.classList.remove("active"));

        // Añadir la clase active al item clickeado
        this.classList.add("active");

        // Actualizar el texto del botón
        const dropdownToggle = dropdown.querySelector(".dropdown-toggle");
        if (dropdownToggle) {
          dropdownToggle.innerHTML = `<i class="bi bi-funnel me-1"></i> ${this.textContent}`;
        }

        // Obtener la categoría seleccionada
        const categoria = this.dataset.categoria || "todos";

        // Obtener el tipo de gráfico
        const chartCard = dropdown.closest(".chart-card");
        const chartBody = chartCard.querySelector(".chart-body");
        const chartCanvas = chartBody.querySelector("div");

        if (chartCanvas) {
          const chartId = chartCanvas.id;

          // Determinar el tipo de datos a cargar
          let dataType = "";
          if (chartId === "maintenanceHistoryChart") {
            dataType = "historial_mantenimiento";
          } else if (chartId === "equipmentStatusChart") {
            dataType = "estado_equipos";
          }

          // Obtener período seleccionado si existe
          let period = "1y";
          const timePeriod = chartCard.querySelector(".time-period");
          if (timePeriod) {
            const activeButton = timePeriod.querySelector(".btn.active");
            if (activeButton) {
              period = activeButton.dataset.period;
            }
          }

          // Cargar datos actualizados
          if (dataType) {
            loadChartData(
              dataType,
              period,
              categoria,
              getCallbackForChart(chartId)
            );
          }
        }
      });
    });
  });
}

/**
 * Obtiene la función de callback adecuada para cada gráfico
 */
function getCallbackForChart(chartId) {
  const callbackMap = {
    equipmentStatusChart: renderEquipmentStatusChart,
    maintenanceTypeChart: renderMaintenanceTypeChart,
    maintenanceHistoryChart: renderMaintenanceHistoryChart,
    maintenanceTrendChart: renderMaintenanceTrendChart,
    equipmentCategoryChart: renderEquipmentCategoryChart,
  };

  return callbackMap[chartId];
}
