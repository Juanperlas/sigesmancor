/**
 * Navbar and Topbar functionality
 */
document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const sidebar = document.querySelector(".sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebarToggleLg = document.getElementById("sidebarToggleLg");
  const sidebarClose = document.getElementById("sidebarClose");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");
  const searchToggle = document.getElementById("searchToggle");
  const mobileSearchBar = document.querySelector(".mobile-search-bar");
  const closeSearch = document.getElementById("closeSearch");
  const body = document.body;

  // Check for saved sidebar state immediately
  const savedSidebarState = localStorage.getItem("sidebar-collapsed");
  if (savedSidebarState === "true") {
    body.classList.add("sidebar-collapsed");
  }

  // Toggle sidebar on mobile
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("show");
      if (sidebarOverlay) {
        sidebarOverlay.classList.toggle("show");
      }
      body.style.overflow = sidebar.classList.contains("show") ? "hidden" : "";
    });
  }

  // Toggle sidebar on desktop
  if (sidebarToggleLg) {
    sidebarToggleLg.addEventListener("click", () => {
      toggleSidebarCollapsed();
    });
  }

  // Close sidebar on mobile
  if (sidebarClose) {
    sidebarClose.addEventListener("click", () => {
      sidebar.classList.remove("show");
      if (sidebarOverlay) {
        sidebarOverlay.classList.remove("show");
      }
      body.style.overflow = "";
    });
  }

  // Close sidebar when clicking overlay
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", () => {
      sidebar.classList.remove("show");
      sidebarOverlay.classList.remove("show");
      body.style.overflow = "";
    });
  }

  // Toggle mobile search bar
  if (searchToggle && mobileSearchBar) {
    searchToggle.addEventListener("click", () => {
      mobileSearchBar.classList.add("show");
    });
  }

  // Close mobile search bar
  if (closeSearch && mobileSearchBar) {
    closeSearch.addEventListener("click", () => {
      mobileSearchBar.classList.remove("show");
    });
  }

  // Toggle sidebar collapsed state
  const toggleSidebarCollapsed = () => {
    body.classList.toggle("sidebar-collapsed");

    // Save preference in localStorage
    localStorage.setItem(
      "sidebar-collapsed",
      body.classList.contains("sidebar-collapsed") ? "true" : "false"
    );
  };

  // Handle window resize
  window.addEventListener("resize", () => {
    if (window.innerWidth < 992 && sidebar) {
      sidebar.classList.remove("show");
      if (sidebarOverlay) {
        sidebarOverlay.classList.remove("show");
      }
      body.style.overflow = "";
    }
  });

  // Add active class to parent menu item if submenu item is active
  const activeSubmenuItem = document.querySelector(
    ".sidebar-submenu-item.active"
  );
  if (activeSubmenuItem) {
    const parentMenuItem = activeSubmenuItem.closest(".sidebar-menu-item");
    if (parentMenuItem) {
      parentMenuItem.classList.add("active");
    }
  }
});
