/**
 * Script principal para SIGESMANCOR
 */

document.addEventListener("DOMContentLoaded", () => {
  // Aplicar el estado del sidebar inmediatamente
  applyStoredSidebarState();

  // Inicializar componentes
  initSidebar();
  initDropdowns();
  initAlerts();
  initMobileSearch();

  // Ocultar el preloader cuando la página esté cargada
  hidePreloader();
});

/**
 * Aplica el estado guardado del sidebar
 */
function applyStoredSidebarState() {
  const savedState = localStorage.getItem("sidebar-collapsed");
  if (savedState === "true") {
    document.body.classList.add("sidebar-collapsed");
  } else {
    document.body.classList.remove("sidebar-collapsed");
  }
}

/**
 * Inicializa la funcionalidad del sidebar
 */
function initSidebar() {
  const mobileToggle = document.getElementById("sidebarToggle");
  const desktopToggle = document.getElementById("sidebarToggleLg");
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");

  // Manejador para toggle móvil
  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      sidebar.classList.toggle("show");
      if (overlay) overlay.classList.toggle("show");
      document.body.style.overflow = sidebar.classList.contains("show")
        ? "hidden"
        : "";
    });
  }

  // Manejador para toggle desktop
  if (desktopToggle) {
    desktopToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      document.body.classList.toggle("sidebar-collapsed");
      localStorage.setItem(
        "sidebar-collapsed",
        document.body.classList.contains("sidebar-collapsed") ? "true" : "false"
      );
    });
  }

  // Cerrar sidebar al hacer click en el overlay
  if (overlay) {
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("show");
      overlay.classList.remove("show");
      document.body.style.overflow = "";
    });
  }

  // Inicializar submenús
  initSubmenus();
}

/**
 * Inicializa los submenús del sidebar
 */
function initSubmenus() {
  const submenuToggles = document.querySelectorAll(".sidebar-submenu-toggle");

  submenuToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // En móvil o cuando no está colapsado, toggle normal del submenu
      if (
        window.innerWidth < 992 ||
        !document.body.classList.contains("sidebar-collapsed")
      ) {
        const menuItem = this.closest(".sidebar-menu-item");
        menuItem.classList.toggle("active");
      }
    });
  });

  // Abrir submenu si hay un item activo
  const activeSubmenuItems = document.querySelectorAll(
    ".sidebar-submenu-item.active"
  );
  activeSubmenuItems.forEach((item) => {
    const parentMenuItem = item.closest(".sidebar-menu-item");
    if (parentMenuItem) {
      parentMenuItem.classList.add("active");
    }
  });
}

/**
 * Inicializa los dropdowns
 */
function initDropdowns() {
  // Check if Bootstrap is available
  let bootstrap;
  try {
    bootstrap = window.bootstrap;
  } catch (e) {
    bootstrap = undefined;
  }

  // Si Bootstrap no está disponible, implementar manualmente
  if (typeof bootstrap === "undefined") {
    console.warn(
      "Bootstrap is not available. Implementing dropdown functionality manually."
    );

    const dropdownToggles = document.querySelectorAll(
      '[data-bs-toggle="dropdown"]'
    );

    dropdownToggles.forEach((toggle) => {
      toggle.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const parent = this.closest(".dropdown");
        const menu = parent.querySelector(".dropdown-menu");

        // Cerrar todos los otros dropdowns
        document.querySelectorAll(".dropdown-menu.show").forEach((openMenu) => {
          if (openMenu !== menu) {
            openMenu.classList.remove("show");
          }
        });

        // Toggle el dropdown actual
        menu.classList.toggle("show");
      });
    });

    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".dropdown")) {
        document.querySelectorAll(".dropdown-menu.show").forEach((menu) => {
          menu.classList.remove("show");
        });
      }
    });
  } else {
    // Bootstrap is available, let it handle dropdowns
  }
}

/**
 * Inicializa las alertas para que se puedan cerrar
 */
function initAlerts() {
  const closeButtons = document.querySelectorAll(".alert .btn-close");

  closeButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const alert = this.closest(".alert");

      // Añadir clase para animación de fade out
      alert.classList.add("fade");

      // Remover el alert después de la animación
      setTimeout(() => {
        alert.remove();
      }, 150);
    });
  });

  // Auto-cerrar alertas después de 5 segundos
  document.querySelectorAll(".alert:not(.alert-important)").forEach((alert) => {
    setTimeout(() => {
      if (alert) {
        // Añadir clase para animación de fade out
        alert.classList.add("fade");

        // Remover el alert después de la animación
        setTimeout(() => {
          if (alert.parentNode) {
            alert.remove();
          }
        }, 150);
      }
    }, 5000);
  });
}

/**
 * Inicializa la búsqueda móvil
 */
function initMobileSearch() {
  const searchToggle = document.getElementById("searchToggle");
  const closeSearch = document.getElementById("closeSearch");
  const mobileSearchBar = document.querySelector(".mobile-search-bar");

  if (searchToggle && mobileSearchBar) {
    searchToggle.addEventListener("click", () => {
      mobileSearchBar.classList.add("show");
      searchToggle.classList.add("active");
      mobileSearchBar.querySelector("input").focus();
    });
  }

  if (closeSearch && mobileSearchBar) {
    closeSearch.addEventListener("click", () => {
      mobileSearchBar.classList.remove("show");
      const searchToggle = document.getElementById("searchToggle");
      if (searchToggle) {
        searchToggle.classList.remove("active");
      }
    });
  }
}

/**
 * Oculta el preloader
 */
function hidePreloader() {
  const preloader = document.querySelector("#preloader");
  if (preloader) {
    setTimeout(() => {
      preloader.style.display = "none";
    }, 500);
  }
}
