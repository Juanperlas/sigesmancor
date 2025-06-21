/**
 * Módulo de Configuración
 * SIGESMANCOR
 */

// Variables globales
let configuracionData = {};
let currentNavbarDesign = "default";
let lastSelectedDesign = "default";
let customColors = {
  navbarBg: "#1571b0",
  navbarText: "#ffffff",
  navbarActive: "#125a8a",
  navbarActiveText: "#ffffff", // NUEVO
  topbarBg: "#ffffff",
  topbarText: "#333333",
};
let isLoading = false;
let hasUnsavedChanges = false;
let originalDesign = "default";
let originalColors = {};

// Elementos DOM
const $ = jQuery;

// Verificar si las funciones de toast están disponibles
if (!window.showErrorToast) window.showErrorToast = (msg) => console.error(msg);
if (!window.showSuccessToast)
  window.showSuccessToast = (msg) => console.log(msg);
if (!window.showInfoToast) window.showInfoToast = (msg) => console.log(msg);

// Diseños predefinidos - ACTUALIZADOS CON TEXTO ACTIVO
const navbarDesigns = {
  default: {
    navbarBg: "#1571b0",
    navbarText: "#ffffff",
    navbarActive: "#ffffff",
    navbarActiveText: "#0d47a1", // NUEVO
    topbarBg: "#1571b0",
    topbarText: "#ffffff",
  },
  dark: {
    navbarBg: "#2c3e50",
    navbarText: "#ecf0f1",
    navbarActive: "#34495e",
    navbarActiveText: "#ffffff", // NUEVO
    topbarBg: "#2c3e50",
    topbarText: "#ecf0f1",
  },
  blue: {
    navbarBg: "#0d47a1",
    navbarText: "#ffffff",
    navbarActive: "#1565c0",
    navbarActiveText: "#ffffff", // NUEVO
    topbarBg: "#e3f2fd",
    topbarText: "#0d47a1",
  },
  green: {
    navbarBg: "#2e7d32",
    navbarText: "#ffffff",
    navbarActive: "#388e3c",
    navbarActiveText: "#ffffff", // NUEVO
    topbarBg: "#e8f5e8",
    topbarText: "#2e7d32",
  },
  superdark: {
    navbarBg: "#000000",
    navbarText: "#ffffff",
    navbarActive: "#ffffff",
    navbarActiveText: "#000000", // NUEVO
    topbarBg: "#000000",
    topbarText: "#ffffff",
  },
  bluetotal: {
    navbarBg: "#0d47a1",
    navbarText: "#ffffff",
    navbarActive: "#ffffff",
    navbarActiveText: "#0d47a1", // NUEVO
    topbarBg: "#0d47a1",
    topbarText: "#ffffff",
  },
  custom: customColors,
};

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  inicializarEventos();
  cargarConfiguracionCompleta();

  // Detectar intentos de salir de la página
  window.addEventListener("beforeunload", function (e) {
    if (hasUnsavedChanges) {
      e.preventDefault();
      e.returnValue =
        "¿Estás seguro de que quieres salir sin guardar los cambios?";
      return e.returnValue;
    }
  });
});

// Función para obtener la URL base
function getBaseUrl() {
  return window.location.pathname.split("/configuracion.php")[0] + "/";
}

// Función para construir URL completa
function getUrl(path) {
  return getBaseUrl() + path;
}

/**
 * Función para detectar el tema actual desde los estilos aplicados
 */
function detectarTemaActual() {
  // Obtener el estilo dinámico actual si existe
  const dynamicStyle = document.getElementById("dynamic-navbar-styles");
  const userPrefsStyle = document.getElementById("user-navbar-preferences");

  if (dynamicStyle || userPrefsStyle) {
    // Obtener colores actuales del sidebar y topbar
    const sidebar = document.querySelector(".sidebar");
    const topbar = document.querySelector(".topbar");

    if (sidebar && topbar) {
      const sidebarStyles = window.getComputedStyle(sidebar);
      const topbarStyles = window.getComputedStyle(topbar);

      const currentColors = {
        navbarBg: rgbToHex(sidebarStyles.backgroundColor),
        topbarBg: rgbToHex(topbarStyles.backgroundColor),
        navbarText: rgbToHex(sidebarStyles.color),
        topbarText: rgbToHex(topbarStyles.color),
        navbarActiveText: "#ffffff", // Default, se actualizará si se encuentra
      };

      // Intentar obtener el color activo del primer elemento activo
      const activeElement = document.querySelector(
        ".sidebar .sidebar-menu-item.active .sidebar-menu-link"
      );
      if (activeElement) {
        const activeStyles = window.getComputedStyle(activeElement);
        currentColors.navbarActive = rgbToHex(activeStyles.backgroundColor);
        currentColors.navbarActiveText = rgbToHex(activeStyles.color);
      }

      console.log("Colores detectados del sistema:", currentColors);

      // Comparar con diseños predefinidos
      for (const [designName, designColors] of Object.entries(navbarDesigns)) {
        if (designName === "custom") continue;

        if (coloresCoinciden(currentColors, designColors)) {
          console.log("Tema detectado:", designName);
          return { design: designName, colors: designColors };
        }
      }

      // Si no coincide con ningún predefinido, es personalizado
      console.log("Tema personalizado detectado");
      return { design: "custom", colors: currentColors };
    }
  }

  // Si no se puede detectar, usar default
  return { design: "default", colors: navbarDesigns.default };
}

/**
 * Función para convertir RGB a HEX
 */
function rgbToHex(rgb) {
  if (!rgb || rgb === "rgba(0, 0, 0, 0)" || rgb === "transparent") {
    return "#ffffff";
  }

  const result = rgb.match(/\d+/g);
  if (!result || result.length < 3) {
    return "#ffffff";
  }

  const r = parseInt(result[0]);
  const g = parseInt(result[1]);
  const b = parseInt(result[2]);

  return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

/**
 * Función para comparar colores (con tolerancia)
 */
function coloresCoinciden(colores1, colores2) {
  const keys = ["navbarBg", "topbarBg"];

  for (const key of keys) {
    if (colores1[key] && colores2[key]) {
      if (colores1[key].toLowerCase() !== colores2[key].toLowerCase()) {
        return false;
      }
    }
  }

  return true;
}

/**
 * Inicializa todos los eventos del módulo
 */
function inicializarEventos() {
  // Navegación entre secciones - CON VERIFICACIÓN DE CAMBIOS
  $(".config-nav-item").on("click", function (e) {
    const section = $(this).data("section");
    const currentSection = $(".config-nav-item.active").data("section");

    // Si hay cambios sin guardar y estamos saliendo de preferencias
    if (
      hasUnsavedChanges &&
      currentSection === "preferencias" &&
      section !== "preferencias"
    ) {
      e.preventDefault();
      mostrarDialogoGuardarCambios(() => {
        cambiarSeccion(section);
      });
      return;
    }

    cambiarSeccion(section);
  });

  // Botones de guardar configuración
  $("#btn-guardar-preferencias").on("click", () => {
    guardarPreferencias();
  });

  $("#btn-guardar-notificaciones").on("click", () => {
    guardarNotificaciones();
  });

  $("#btn-guardar-seguridad").on("click", () => {
    guardarSeguridad();
  });

  $("#btn-guardar-sistema").on("click", () => {
    guardarSistema();
  });

  // Cerrar otras sesiones
  $("#btn-cerrar-otras-sesiones").on("click", () => {
    cerrarOtrasSesiones();
  });

  // Eventos para diseños de navbar - CON DETECCIÓN DE CAMBIOS
  $(".design-option").on("click", function () {
    const design = $(this).data("design");
    seleccionarDiseno(design);
    detectarCambios();
  });

  // Eventos para controles de color - CON DETECCIÓN DE CAMBIOS - ACTUALIZADO
  $(
    "#navbar-bg-color, #navbar-text-color, #navbar-active-bg-color, #navbar-active-text-color, #topbar-bg-color, #topbar-text-color"
  ).on("input", function () {
    actualizarColorPersonalizado(this);
    detectarCambios();
  });

  // Eventos para inputs de texto de color - CON DETECCIÓN DE CAMBIOS - ACTUALIZADO
  $(
    "#navbar-bg-color-text, #navbar-text-color-text, #navbar-active-bg-color-text, #navbar-active-text-color-text, #topbar-bg-color-text, #topbar-text-color-text"
  ).on("input", function () {
    actualizarColorDesdeTexto(this);
    detectarCambios();
  });

  // Interceptar clics en enlaces del navbar para verificar cambios
  $(".sidebar-menu-link").on("click", function (e) {
    if (hasUnsavedChanges) {
      e.preventDefault();
      const href = $(this).attr("href");
      mostrarDialogoGuardarCambios(() => {
        window.location.href = href;
      });
    }
  });
}

/**
 * Detecta si hay cambios sin guardar
 */
function detectarCambios() {
  const currentColors = obtenerColoresActuales();

  // Comparar diseño actual con el original
  const designChanged = currentNavbarDesign !== originalDesign;

  // Comparar colores actuales con los originales
  const colorsChanged =
    JSON.stringify(currentColors) !== JSON.stringify(originalColors);

  hasUnsavedChanges = designChanged || colorsChanged;

  // Actualizar UI para mostrar que hay cambios
  if (hasUnsavedChanges) {
    $("#btn-guardar-preferencias")
      .addClass("btn-warning")
      .removeClass("btn-primary");
    $("#btn-guardar-preferencias").html(
      '<i class="bi bi-exclamation-triangle me-1"></i> Guardar Cambios'
    );
  } else {
    $("#btn-guardar-preferencias")
      .addClass("btn-primary")
      .removeClass("btn-warning");
    $("#btn-guardar-preferencias").html(
      '<i class="bi bi-check-lg me-1"></i> Guardar Preferencias'
    );
  }
}

/**
 * Muestra diálogo para guardar cambios
 */
function mostrarDialogoGuardarCambios(callback) {
  const modal = `
    <div class="modal fade" id="unsavedChangesModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cambios sin guardar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Tienes cambios sin guardar en la configuración de temas.</p>
            <p>¿Qué deseas hacer?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="discardChanges">
              Descartar cambios
            </button>
            <button type="button" class="btn btn-primary" id="saveAndContinue">
              Guardar y continuar
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Remover modal existente si existe
  $("#unsavedChangesModal").remove();

  // Agregar modal al DOM
  $("body").append(modal);

  // Mostrar modal
  const modalInstance = new bootstrap.Modal(
    document.getElementById("unsavedChangesModal")
  );
  modalInstance.show();

  // Eventos del modal
  $("#saveAndContinue").on("click", function () {
    modalInstance.hide();
    guardarPreferencias(() => {
      callback();
    });
  });

  $("#discardChanges").on("click", function () {
    modalInstance.hide();
    // Restaurar configuración original
    restaurarConfiguracionOriginal();
    callback();
  });
}

/**
 * Restaura la configuración original
 */
function restaurarConfiguracionOriginal() {
  currentNavbarDesign = originalDesign;

  if (originalDesign === "custom") {
    customColors = { ...originalColors };
    navbarDesigns.custom = customColors;
  }

  // Aplicar configuración original
  seleccionarDisenoSinAplicar(originalDesign);
  const coloresOriginales =
    originalDesign === "custom"
      ? originalColors
      : navbarDesigns[originalDesign];
  aplicarColoresAlSistema(coloresOriginales);

  hasUnsavedChanges = false;
  detectarCambios();
}

/**
 * Cambia la sección activa
 */
function cambiarSeccion(section) {
  // Actualizar navegación
  $(".config-nav-item").removeClass("active");
  $(`.config-nav-item[data-section="${section}"]`).addClass("active");

  // Actualizar contenido
  $(".config-section").removeClass("active");
  $(`#section-${section}`).addClass("active");
}

/**
 * Carga la configuración completa desde el servidor - CORREGIDO
 */
function cargarConfiguracionCompleta() {
  if (isLoading) return;
  isLoading = true;

  $.ajax({
    url: getUrl("api/configuracion/cargar-preferencias.php"),
    type: "GET",
    dataType: "json",
    success: (response) => {
      if (response.success && response.data) {
        const data = response.data;

        // Actualizar configuración global
        configuracionData = {
          tema: data.tema || "claro",
          idioma: data.idioma || "es",
          paginaInicio: data.pagina_inicio || "dashboard",
          elementosPagina: data.elementos_por_pagina || 25,
          notifMantenimientos: true,
          notifEquipos: true,
          notifAsignaciones: false,
          frecuenciaMantenimiento: "diario",
          resumenActividades: "semanal",
          perfilVisible: true,
          mostrarActividad: false,
        };

        // NUEVO: Detectar tema actual del sistema
        const temaActual = detectarTemaActual();

        // Si hay datos guardados en BD, usarlos; si no, usar lo detectado
        if (data.navbar_design) {
          currentNavbarDesign = data.navbar_design;
          lastSelectedDesign = data.navbar_design;

          // Si hay colores guardados, actualizar customColors - ACTUALIZADO
          if (data.navbar_bg_color || data.topbar_bg_color) {
            customColors = {
              navbarBg: data.navbar_bg_color || "#1571b0",
              navbarText: data.navbar_text_color || "#ffffff",
              navbarActive: data.navbar_active_bg_color || "#125a8a",
              navbarActiveText: data.navbar_active_text_color || "#ffffff", // NUEVO
              topbarBg: data.topbar_bg_color || "#ffffff",
              topbarText: data.topbar_text_color || "#333333",
            };
            navbarDesigns.custom = customColors;
          }
        } else {
          // Usar tema detectado del sistema
          currentNavbarDesign = temaActual.design;
          lastSelectedDesign = temaActual.design;

          if (temaActual.design === "custom") {
            customColors = { ...temaActual.colors };
            navbarDesigns.custom = customColors;
          }
        }

        originalDesign = currentNavbarDesign;

        // Guardar colores originales
        originalColors =
          currentNavbarDesign === "custom"
            ? { ...customColors }
            : { ...navbarDesigns[currentNavbarDesign] };

        console.log("Configuración cargada:", {
          design: currentNavbarDesign,
          colors: originalColors,
        });

        aplicarConfiguracionSinCambiarColores();
      } else {
        // Usar valores por defecto pero detectar tema actual
        cargarConfiguracionPorDefecto();
      }
      isLoading = false;
    },
    error: (xhr, status, error) => {
      console.error("Error cargando configuración:", error);
      cargarConfiguracionPorDefecto();
      isLoading = false;
    },
  });
}

/**
 * Carga configuración por defecto - CORREGIDO
 */
function cargarConfiguracionPorDefecto() {
  configuracionData = {
    tema: "claro",
    idioma: "es",
    paginaInicio: "dashboard",
    elementosPagina: 25,
    notifMantenimientos: true,
    notifEquipos: true,
    notifAsignaciones: false,
    frecuenciaMantenimiento: "diario",
    resumenActividades: "semanal",
    perfilVisible: true,
    mostrarActividad: false,
  };

  // Detectar tema actual del sistema
  const temaActual = detectarTemaActual();
  currentNavbarDesign = temaActual.design;
  originalDesign = temaActual.design;
  lastSelectedDesign = temaActual.design;

  if (temaActual.design === "custom") {
    customColors = { ...temaActual.colors };
    navbarDesigns.custom = customColors;
    originalColors = { ...customColors };
  } else {
    originalColors = { ...navbarDesigns[temaActual.design] };
  }

  aplicarConfiguracionSinCambiarColores();
}

/**
 * Aplica la configuración cargada SIN cambiar los colores del sistema
 */
function aplicarConfiguracionSinCambiarColores() {
  // Preferencias básicas
  $("#config-tema").val(configuracionData.tema);
  $("#config-idioma").val(configuracionData.idioma);
  $("#config-pagina-inicio").val(configuracionData.paginaInicio);
  $("#config-elementos-pagina").val(configuracionData.elementosPagina);

  // Notificaciones
  $("#notif-mantenimientos").prop(
    "checked",
    configuracionData.notifMantenimientos
  );
  $("#notif-equipos").prop("checked", configuracionData.notifEquipos);
  $("#notif-asignaciones").prop("checked", configuracionData.notifAsignaciones);
  $("#config-frecuencia-mantenimiento").val(
    configuracionData.frecuenciaMantenimiento
  );
  $("#config-resumen-actividades").val(configuracionData.resumenActividades);

  // Seguridad
  $("#privacidad-perfil").prop("checked", configuracionData.perfilVisible);
  $("#privacidad-actividad").prop(
    "checked",
    configuracionData.mostrarActividad
  );

  // Aplicar tema (esto no afecta los colores del navbar)
  aplicarTema(configuracionData.tema);

  // CORREGIDO: Seleccionar el tema actual detectado
  seleccionarDisenoSoloUI(currentNavbarDesign);

  // Resetear estado de cambios
  hasUnsavedChanges = false;
  detectarCambios();

  console.log(
    "Configuración aplicada - Tema seleccionado:",
    currentNavbarDesign
  );
}

/**
 * Selecciona diseño SOLO en la UI, sin aplicar colores - CORREGIDO
 */
function seleccionarDisenoSoloUI(design) {
  console.log("Seleccionando diseño en UI:", design);

  // Actualizar UI
  $(".design-option").removeClass("active");
  $(`.design-option[data-design="${design}"]`).addClass("active");

  // Mostrar/ocultar controles personalizados
  if (design === "custom") {
    $("#custom-colors-section").show();
    // Cargar colores personalizados en los controles
    cargarColoresEnControles(customColors);
    console.log("Modo personalizado - Colores cargados:", customColors);
  } else {
    $("#custom-colors-section").hide();
    // Para diseños predefinidos, cargar sus colores en los controles
    cargarColoresEnControles(navbarDesigns[design]);
    console.log("Modo predefinido - Colores cargados:", navbarDesigns[design]);
  }

  actualizarVistaPrevia();
}

/**
 * Selecciona diseño sin aplicar automáticamente - MANTENER PARA COMPATIBILIDAD
 */
function seleccionarDisenoSinAplicar(design) {
  seleccionarDisenoSoloUI(design);
}

/**
 * Función nueva para cargar colores en los controles - ACTUALIZADA
 */
function cargarColoresEnControles(colors) {
  $("#navbar-bg-color").val(colors.navbarBg);
  $("#navbar-bg-color-text").val(colors.navbarBg);
  $("#navbar-text-color").val(colors.navbarText);
  $("#navbar-text-color-text").val(colors.navbarText);
  $("#navbar-active-bg-color").val(colors.navbarActive);
  $("#navbar-active-bg-color-text").val(colors.navbarActive);
  $("#navbar-active-text-color").val(colors.navbarActiveText); // NUEVO
  $("#navbar-active-text-color-text").val(colors.navbarActiveText); // NUEVO
  $("#topbar-bg-color").val(colors.topbarBg);
  $("#topbar-bg-color-text").val(colors.topbarBg);
  $("#topbar-text-color").val(colors.topbarText);
  $("#topbar-text-color-text").val(colors.topbarText);
}

/**
 * Selecciona diseño y lo aplica (para cuando el usuario hace clic)
 */
function seleccionarDiseno(design) {
  // Si no es personalizado, actualizar el último tema seleccionado
  if (design !== "custom") {
    lastSelectedDesign = design;
  }

  currentNavbarDesign = design;

  // Actualizar UI
  $(".design-option").removeClass("active");
  $(`.design-option[data-design="${design}"]`).addClass("active");

  // Mostrar/ocultar controles personalizados
  if (design === "custom") {
    $("#custom-colors-section").show();
    // Cargar colores del último tema seleccionado
    const coloresDelUltimoTema = navbarDesigns[lastSelectedDesign];
    cargarColoresEnControles(coloresDelUltimoTema);
    // Actualizar customColors con los colores del último tema
    customColors = { ...coloresDelUltimoTema };
    navbarDesigns.custom = customColors;
    // Aplicar los colores al sistema
    aplicarColoresAlSistema(customColors);
  } else {
    $("#custom-colors-section").hide();
    // Aplicar colores del diseño seleccionado
    aplicarDiseno(navbarDesigns[design]);
  }

  actualizarVistaPrevia();
}

/**
 * Aplica el tema seleccionado
 */
function aplicarTema(tema) {
  const body = document.body;
  body.classList.remove("tema-claro", "tema-oscuro");

  if (tema === "oscuro") {
    body.classList.add("tema-oscuro");
  } else if (tema === "auto") {
    // Detectar preferencia del sistema
    if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      body.classList.add("tema-oscuro");
    } else {
      body.classList.add("tema-claro");
    }
  } else {
    body.classList.add("tema-claro");
  }
}

// Función para aplicar diseño
function aplicarDiseno(colors) {
  // Actualizar controles
  cargarColoresEnControles(colors);

  // Aplicar colores al sistema
  aplicarColoresAlSistema(colors);
}

// Función para actualizar color personalizado
function actualizarColorPersonalizado(input) {
  const colorId = input.id.replace("-color", "");
  const textInput = $(`#${colorId}-color-text`);
  textInput.val(input.value);

  // Actualizar colores personalizados
  actualizarColoresPersonalizados();
  actualizarVistaPrevia();

  if (currentNavbarDesign === "custom") {
    aplicarColoresAlSistema(customColors);
  }
}

// Función para actualizar color desde texto
function actualizarColorDesdeTexto(input) {
  const colorId = input.id.replace("-text", "");
  const colorInput = $(`#${colorId}`);

  if (isValidHexColor(input.value)) {
    colorInput.val(input.value);
    actualizarColoresPersonalizados();
    actualizarVistaPrevia();

    if (currentNavbarDesign === "custom") {
      aplicarColoresAlSistema(customColors);
    }
  }
}

// Función para validar color hexadecimal
function isValidHexColor(hex) {
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex);
}

// Función para actualizar colores personalizados - ACTUALIZADA
function actualizarColoresPersonalizados() {
  customColors = {
    navbarBg: $("#navbar-bg-color").val(),
    navbarText: $("#navbar-text-color").val(),
    navbarActive: $("#navbar-active-bg-color").val(),
    navbarActiveText: $("#navbar-active-text-color").val(), // NUEVO
    topbarBg: $("#topbar-bg-color").val(),
    topbarText: $("#topbar-text-color").val(),
  };
  navbarDesigns.custom = customColors;
}

// Función para actualizar vista previa - ACTUALIZADA
function actualizarVistaPrevia() {
  const colors =
    currentNavbarDesign === "custom"
      ? customColors
      : navbarDesigns[currentNavbarDesign];

  const preview = document.getElementById("navbar-preview");
  if (preview) {
    preview.style.setProperty("--preview-navbar-bg", colors.navbarBg);
    preview.style.setProperty("--preview-navbar-text", colors.navbarText);
    preview.style.setProperty("--preview-navbar-active", colors.navbarActive);
    preview.style.setProperty(
      "--preview-navbar-active-text",
      colors.navbarActiveText
    ); // NUEVO
    preview.style.setProperty("--preview-topbar-bg", colors.topbarBg);
    preview.style.setProperty("--preview-topbar-text", colors.topbarText);
  }
}

// Función para aplicar colores al sistema - ACTUALIZADA
function aplicarColoresAlSistema(colors) {
  // Crear o actualizar el estilo dinámico
  let styleElement = document.getElementById("dynamic-navbar-styles");
  if (!styleElement) {
    styleElement = document.createElement("style");
    styleElement.id = "dynamic-navbar-styles";
    document.head.appendChild(styleElement);
  }

  // Usar el color de texto activo específico - CORREGIDO
  const activeTextColor = colors.navbarActiveText || "#ffffff";
  console.log("Aplicando colores:", colors);
  console.log("Color texto activo:", activeTextColor);

  const css = `
    /* Navbar (Sidebar) */
    .sidebar {
      background-color: ${colors.navbarBg} !important;
      color: ${colors.navbarText} !important;
    }
    
    .sidebar .sidebar-menu-link {
      color: ${colors.navbarText} !important;
    }
    
    .sidebar .sidebar-menu-item.active .sidebar-menu-link {
      background-color: ${colors.navbarActive} !important;
      color: ${colors.navbarActiveText || "#ffffff"} !important;
    }

    .sidebar .sidebar-menu-link:hover {
      background-color: ${colors.navbarActive} !important;
      color: ${colors.navbarActiveText || "#ffffff"} !important;
    }
    
    .sidebar .sidebar-section-title {
      color: ${colors.navbarText} !important;
      opacity: 0.8;
    }
    
    /* Logo */
    .sidebar .logo {
      filter: brightness(0) invert(1);
      mix-blend-mode: normal;
    }
    
    .sidebar .logo[src*="logo.png"] {
      filter: brightness(0) saturate(100%) invert(1) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(100%) contrast(100%);
    }
    
    /* Topbar */
    .topbar {
      background-color: ${colors.topbarBg} !important;
      color: ${colors.topbarText} !important;
    }
    
    .topbar .user-name {
      color: ${colors.topbarText} !important;
    }
    
    .topbar .user-role {
      color: ${colors.topbarText} !important;
      opacity: 0.7;
    }
    
    /* Botón toggle del sidebar */
    .sidebar-toggle {
      color: ${colors.topbarText} !important;
    }
    
    /* Barra de búsqueda */
    .search-container .form-control {
      color: ${colors.topbarText} !important;
    }
    
    .search-container .form-control::placeholder {
      color: ${colors.topbarText} !important;
      opacity: 0.6;
    }
    
    .search-container .input-group-text {
      color: ${colors.topbarText} !important;
    }
    
    .search-container .btn {
      color: ${colors.topbarText} !important;
    }
    
    /* Iconos de la topbar */
    .topbar .btn-icon {
      color: ${colors.topbarText} !important;
    }
    
    .topbar .notification-btn {
      color: ${colors.topbarText} !important;
    }
    
    /* Barra de búsqueda móvil */
    .mobile-search-bar {
      background-color: ${colors.topbarBg} !important;
    }
    
    .mobile-search-bar .form-control {
      color: ${colors.topbarText} !important;
    }
    
    .mobile-search-bar .form-control::placeholder {
      color: ${colors.topbarText} !important;
      opacity: 0.6;
    }
    
    .mobile-search-bar .input-group-text,
    .mobile-search-bar .btn {
      color: ${colors.topbarText} !important;
    }
  `;

  styleElement.textContent = css;
}

/**
 * Función corregida para obtener colores actuales - ACTUALIZADA
 */
function obtenerColoresActuales() {
  // Si estamos en modo personalizado, leer desde los controles
  if (currentNavbarDesign === "custom") {
    return {
      navbarBg: $("#navbar-bg-color").val(),
      navbarText: $("#navbar-text-color").val(),
      navbarActive: $("#navbar-active-bg-color").val(),
      navbarActiveText: $("#navbar-active-text-color").val(), // NUEVO
      topbarBg: $("#topbar-bg-color").val(),
      topbarText: $("#topbar-text-color").val(),
    };
  } else {
    // Para diseños predefinidos, usar los valores del diseño
    return navbarDesigns[currentNavbarDesign];
  }
}

/**
 * Guarda las preferencias personales - CON CALLBACK - ACTUALIZADA
 */
function guardarPreferencias(callback) {
  const tema = $("#config-tema").val();
  const idioma = $("#config-idioma").val();
  const paginaInicio = $("#config-pagina-inicio").val();
  const elementosPagina = $("#config-elementos-pagina").val();

  // Obtener colores actuales
  const coloresActuales = obtenerColoresActuales();

  console.log("Guardando preferencias:", {
    tema,
    idioma,
    paginaInicio,
    elementosPagina,
    navbar_design: currentNavbarDesign,
    colores: coloresActuales,
  });

  // Enviar al servidor
  $.ajax({
    url: getUrl("api/configuracion/preferencias.php"),
    type: "POST",
    data: {
      tema: tema,
      idioma: idioma,
      pagina_inicio: paginaInicio,
      elementos_pagina: elementosPagina,
      navbar_design: currentNavbarDesign,
      navbar_bg_color: coloresActuales.navbarBg,
      navbar_text_color: coloresActuales.navbarText,
      navbar_active_bg_color: coloresActuales.navbarActive,
      navbar_active_text_color: coloresActuales.navbarActiveText, // NUEVO
      topbar_bg_color: coloresActuales.topbarBg,
      topbar_text_color: coloresActuales.topbarText,
    },
    dataType: "json",
    success: (response) => {
      if (response.success) {
        mostrarToast(
          "success",
          "Éxito",
          "Preferencias guardadas correctamente"
        );

        // Actualizar configuración en memoria
        configuracionData.tema = tema;
        configuracionData.idioma = idioma;
        configuracionData.paginaInicio = paginaInicio;
        configuracionData.elementosPagina = elementosPagina;

        // Actualizar valores originales
        originalDesign = currentNavbarDesign;
        originalColors = { ...coloresActuales };

        // Si estamos en modo personalizado, actualizar customColors
        if (currentNavbarDesign === "custom") {
          customColors = { ...coloresActuales };
          navbarDesigns.custom = customColors;
        } else {
          // Actualizar el último tema seleccionado
          lastSelectedDesign = currentNavbarDesign;
        }

        // Resetear estado de cambios
        hasUnsavedChanges = false;
        detectarCambios();

        // Aplicar tema y colores
        aplicarTema(tema);
        aplicarColoresAlSistema(coloresActuales);

        // Ejecutar callback si existe
        if (callback && typeof callback === "function") {
          callback();
        }
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar preferencias"
        );
      }
    },
    error: (xhr, status, error) => {
      mostrarToast("error", "Error", "Error al guardar preferencias");
      console.error(error);
    },
  });
}

/**
 * Guarda la configuración de notificaciones
 */
function guardarNotificaciones() {
  const notifMantenimientos = $("#notif-mantenimientos").is(":checked");
  const notifEquipos = $("#notif-equipos").is(":checked");
  const notifAsignaciones = $("#notif-asignaciones").is(":checked");
  const frecuenciaMantenimiento = $("#config-frecuencia-mantenimiento").val();
  const resumenActividades = $("#config-resumen-actividades").val();

  // Actualizar configuración en memoria
  configuracionData.notifMantenimientos = notifMantenimientos;
  configuracionData.notifEquipos = notifEquipos;
  configuracionData.notifAsignaciones = notifAsignaciones;
  configuracionData.frecuenciaMantenimiento = frecuenciaMantenimiento;
  configuracionData.resumenActividades = resumenActividades;

  mostrarToast("success", "Éxito", "Configuración de notificaciones guardada");
}

/**
 * Guarda la configuración de seguridad
 */
function guardarSeguridad() {
  const perfilVisible = $("#privacidad-perfil").is(":checked");
  const mostrarActividad = $("#privacidad-actividad").is(":checked");

  // Actualizar configuración en memoria
  configuracionData.perfilVisible = perfilVisible;
  configuracionData.mostrarActividad = mostrarActividad;

  mostrarToast("success", "Éxito", "Configuración de seguridad guardada");
}

/**
 * Guarda la configuración del sistema (solo administradores)
 */
function guardarSistema() {
  const sistemaNombre = $("#sistema-nombre").val();
  const sistemaTimezone = $("#sistema-timezone").val();
  const sistemaDiasNotificacion = $("#sistema-dias-notificacion").val();
  const sistemaAutoPreventivo = $("#sistema-auto-preventivo").val();

  // Aquí se enviaría al servidor para guardar en base de datos
  $.ajax({
    url: getUrl("api/configuracion/sistema.php"),
    type: "POST",
    data: {
      nombre: sistemaNombre,
      timezone: sistemaTimezone,
      dias_notificacion: sistemaDiasNotificacion,
      auto_preventivo: sistemaAutoPreventivo,
    },
    dataType: "json",
    success: (response) => {
      if (response.success) {
        mostrarToast("success", "Éxito", "Configuración del sistema guardada");
      } else {
        mostrarToast(
          "error",
          "Error",
          response.message || "Error al guardar la configuración"
        );
      }
    },
    error: (xhr, status, error) => {
      mostrarToast(
        "error",
        "Error",
        "Error al guardar la configuración del sistema"
      );
      console.error(error);
    },
  });
}

/**
 * Cierra todas las sesiones excepto la actual
 */
function cerrarOtrasSesiones() {
  if (
    confirm("¿Está seguro que desea cerrar todas las demás sesiones activas?")
  ) {
    $.ajax({
      url: getUrl("api/auth/cerrar-otras-sesiones.php"),
      type: "POST",
      dataType: "json",
      success: (response) => {
        if (response.success) {
          mostrarToast(
            "success",
            "Éxito",
            "Otras sesiones cerradas correctamente"
          );
        } else {
          mostrarToast(
            "error",
            "Error",
            response.message || "Error al cerrar las sesiones"
          );
        }
      },
      error: (xhr, status, error) => {
        mostrarToast("error", "Error", "Error al cerrar las otras sesiones");
        console.error(error);
      },
    });
  }
}

/**
 * Muestra un mensaje toast
 */
function mostrarToast(tipo, titulo, mensaje, duracion) {
  if (typeof showToast === "function") {
    showToast(tipo, titulo, mensaje, duracion);
  } else if (window.showSuccessToast && tipo === "success") {
    window.showSuccessToast(mensaje);
  } else if (window.showErrorToast && tipo === "error") {
    window.showErrorToast(mensaje);
  } else if (window.showInfoToast && (tipo === "info" || tipo === "warning")) {
    window.showInfoToast(mensaje);
  } else {
    console.log(`${titulo}: ${mensaje}`);
  }
}
