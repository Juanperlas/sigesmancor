//import { Chart } from "@/components/ui/chart";
/**
 * Script para navegación SPA (Single Page Application)
 * Este script maneja la carga de contenido mediante AJAX para evitar recargar la página completa
 */

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM cargado - Inicializando SPA");
  initSpaNavigation();

  // Verificar si estamos en una URL que no sea dashboard y necesitamos cargar contenido
  const currentPath = window.location.pathname;
  if (
    currentPath.includes("/modulos/") &&
    !document.body.getAttribute("data-content-loaded")
  ) {
    // Marcar que ya hemos intentado cargar contenido para evitar bucles
    document.body.setAttribute("data-content-loaded", "true");
    console.log("Detectada URL de módulo al cargar la página:", currentPath);
    loadContent(currentPath);
  }
});

/**
 * Inicializa la navegación SPA
 */
function initSpaNavigation() {
  // Seleccionar todos los enlaces con el atributo data-spa-link
  const spaLinks = document.querySelectorAll("[data-spa-link]");
  console.log("Enlaces SPA encontrados:", spaLinks.length);

  // Eliminar eventos anteriores para evitar duplicados
  spaLinks.forEach((link) => {
    link.removeEventListener("click", handleSpaNavigation);
    link.addEventListener("click", handleSpaNavigation);
  });

  // Manejar navegación con botones de atrás/adelante del navegador
  window.removeEventListener("popstate", handlePopState);
  window.addEventListener("popstate", handlePopState);
}

/**
 * Maneja el evento click en enlaces SPA
 * @param {Event} e - Evento click
 */
function handleSpaNavigation(e) {
  e.preventDefault();

  const url = this.getAttribute("href");
  console.log("Click en enlace SPA:", url);

  // No hacer nada si es la misma URL
  if (url === window.location.pathname) {
    console.log("Misma URL, no se hace nada");
    return;
  }

  // Cargar el contenido
  loadContent(url);

  // Actualizar la URL en el navegador sin recargar la página
  window.history.pushState({ url: url }, "", url);
}

/**
 * Maneja el evento popstate (botones atrás/adelante del navegador)
 * @param {PopStateEvent} e - Evento popstate
 */
function handlePopState(e) {
  console.log("Evento popstate:", e.state);
  if (e.state && e.state.url) {
    loadContent(e.state.url);
  }
}

/**
 * Limpia los recursos para evitar fugas de memoria
 */
function cleanupResources() {
  console.log("Limpiando recursos para evitar fugas de memoria");

  // Destruir todos los gráficos existentes
  if (window.Chart) {
    // Si Chart.js está disponible y tiene instancias
    if (window.Chart.instances) {
      Object.keys(window.Chart.instances).forEach((key) => {
        window.Chart.instances[key].destroy();
      });
    }

    // Alternativa para destruir gráficos específicos
    if (window.equipmentChart) {
      window.equipmentChart.destroy();
      window.equipmentChart = null;
    }
  }

  // Eliminar todos los event listeners de elementos dinámicos
  const mainContent = document.getElementById("main-content");
  if (mainContent) {
    const clickableElements = mainContent.querySelectorAll(
      "button, a, input, select"
    );
    clickableElements.forEach((element) => {
      // Clonar y reemplazar para eliminar todos los event listeners
      const newElement = element.cloneNode(true);
      if (element.parentNode) {
        element.parentNode.replaceChild(newElement, element);
      }
    });
  }

  // Forzar la recolección de basura (aunque esto es solo una sugerencia al navegador)
  if (window.gc) {
    window.gc();
  }
}

/**
 * Carga el contenido de una URL mediante AJAX
 * @param {string} url - URL a cargar
 */
function loadContent(url) {
  console.log("Cargando contenido de:", url);

  const mainContent = document.getElementById("main-content");
  if (!mainContent) {
    console.error("No se encontró el contenedor principal #main-content");
    return;
  }

  cleanupResources();

  const loaderOverlay = document.createElement("div");
  loaderOverlay.className = "content-loader";
  loaderOverlay.innerHTML = '<div class="loader-spinner"></div>';
  mainContent.appendChild(loaderOverlay);

  const ajaxUrl = url + (url.includes("?") ? "&" : "?") + "ajax=1";

  const isDashboard =
    url.includes("dashboard.php") ||
    url === "/sigesmancor/" ||
    url === "/sigesmancor/dashboard";

  if (isDashboard) {
    console.log("Cargando dashboard, haciendo recarga completa");
    window.location.href = url;
    return;
  }

  var xhr = new XMLHttpRequest();
  xhr.open("GET", ajaxUrl, true);
  xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  xhr.timeout = 10000;

  xhr.onreadystatechange = () => {
    if (xhr.readyState === 4) {
      const loaderOverlay = mainContent.querySelector(".content-loader");
      if (loaderOverlay) {
        loaderOverlay.remove();
      }

      if (xhr.status === 200) {
        console.log("Respuesta recibida correctamente");

        // Insertar el contenido
        mainContent.innerHTML = xhr.responseText;

        // Procesar scripts en el contenido cargado
        const parser = new DOMParser();
        const doc = parser.parseFromString(xhr.responseText, "text/html");
        const scripts = doc.querySelectorAll("script");
        scripts.forEach((script) => {
          const newScript = document.createElement("script");
          if (script.src) {
            newScript.src = script.src;
            newScript.async = false;
          } else {
            newScript.textContent = script.textContent;
          }
          document.body.appendChild(newScript);
        });

        // Actualizar el título de la página
        const titleMatch = xhr.responseText.match(
          /<h1[^>]*class="page-title"[^>]*>(.*?)<\/h1>/
        );
        if (titleMatch && titleMatch[1]) {
          document.title = titleMatch[1] + " | SIGESMANCOR";
        }

        updateActiveLink(url);
        initNewContentScripts();

        console.log("Contenido cargado correctamente");
      } else {
        console.error("Error:", xhr.status, xhr.statusText);
        mainContent.innerHTML = `
                    <div class="container-fluid">
                        <div class="alert alert-danger mt-4">
                            <h4 class="alert-heading">Error al cargar el contenido</h4>
                            <p>No se pudo cargar el contenido solicitado. Por favor, inténtelo de nuevo más tarde.</p>
                            <hr>
                            <p class="mb-0">Si el problema persiste, contacte al administrador del sistema.</p>
                            <p class="mb-0">Detalles: ${xhr.status} ${xhr.statusText}</p>
                        </div>
                    </div>
                `;
      }
    }
  };

  xhr.ontimeout = () => {
    console.error("La solicitud ha excedido el tiempo de espera");
    mainContent.innerHTML = `
            <div class="container-fluid">
                <div class="alert alert-danger mt-4">
                    <h4 class="alert-heading">Error de tiempo de espera</h4>
                    <p>La solicitud ha tardado demasiado tiempo. Por favor, inténtelo de nuevo más tarde.</p>
                    <hr>
                    <p class="mb-0">Si el problema persiste, contacte al administrador del sistema.</p>
                </div>
            </div>
        `;
  };

  xhr.onerror = () => {
    console.error("Error de red");
    mainContent.innerHTML = `
            <div class="container-fluid">
                <div class="alert alert-danger mt-4">
                    <h4 class="alert-heading">Error de red</h4>
                    <p>No se pudo establecer conexión con el servidor. Por favor, verifique su conexión a internet.</p>
                    <hr>
                    <p class="mb-0">Si el problema persiste, contacte al administrador del sistema.</p>
                </div>
            </div>
        `;
  };

  xhr.send();
}

/**
 * Actualiza el enlace activo en el sidebar
 * @param {string} url - URL actual
 */
function updateActiveLink(url) {
  // Eliminar la clase active de todos los enlaces
  document.querySelectorAll(".sidebar-menu-item").forEach((item) => {
    item.classList.remove("active");
  });

  // Encontrar y marcar el enlace activo
  const activeLink = document.querySelector(
    `.sidebar-menu-link[href="${url}"]`
  );
  if (activeLink) {
    const menuItem = activeLink.closest(".sidebar-menu-item");
    if (menuItem) {
      menuItem.classList.add("active");
    }
  }
}

/**
 * Inicializa scripts para el nuevo contenido cargado
 */
function initNewContentScripts() {
  // Inicializar gráficos si existen
  if (document.getElementById("equipmentDistributionChart")) {
    initDashboardCharts();
  }

  // Volver a inicializar los enlaces SPA en el nuevo contenido
  document.querySelectorAll("[data-spa-link]").forEach((link) => {
    link.removeEventListener("click", handleSpaNavigation);
    link.addEventListener("click", handleSpaNavigation);
  });
}

/**
 * Inicializa los gráficos del dashboard
 */
function initDashboardCharts() {
  console.log("Inicializando gráficos del dashboard");

  // Verificar si Chart.js está disponible
  if (typeof Chart === "undefined") {
    console.error("Chart.js no está disponible");
    return;
  }

  // Destruir el gráfico anterior si existe
  if (window.equipmentChart) {
    window.equipmentChart.destroy();
    window.equipmentChart = null;
  }

  const distributionCtx = document.getElementById("equipmentDistributionChart");
  if (!distributionCtx) {
    console.error("No se encontró el elemento canvas para el gráfico");
    return;
  }

  const ctx = distributionCtx.getContext("2d");
  const distributionData = {
    labels: [
      "Mina Norte",
      "Mina Sur",
      "Mina Este",
      "Mina Oeste",
      "Taller Central",
      "Almacén",
    ],
    datasets: [
      {
        label: "Cantidad de Equipos",
        data: [35, 28, 22, 18, 12, 10],
        backgroundColor: [
          "rgba(54, 162, 235, 0.7)",
          "rgba(75, 192, 192, 0.7)",
          "rgba(255, 206, 86, 0.7)",
          "rgba(153, 102, 255, 0.7)",
          "rgba(255, 159, 64, 0.7)",
          "rgba(255, 99, 132, 0.7)",
        ],
        borderColor: [
          "rgba(54, 162, 235, 1)",
          "rgba(75, 192, 192, 1)",
          "rgba(255, 206, 86, 1)",
          "rgba(153, 102, 255, 1)",
          "rgba(255, 159, 64, 1)",
          "rgba(255, 99, 132, 1)",
        ],
        borderWidth: 1,
      },
    ],
  };

  // Guardar referencia al gráfico
  window.equipmentChart = new Chart(ctx, {
    type: "bar",
    data: distributionData,
    options: {
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}
