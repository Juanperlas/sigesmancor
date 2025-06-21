/**
 * Componente SearchableSelect
 * Select con funcionalidad de búsqueda en tiempo real
 */

class SearchableSelect {
  constructor(container, options = {}) {
    this.container =
      typeof container === "string"
        ? document.querySelector(container)
        : container;
    this.options = {
      placeholder: "Seleccione una opción",
      searchPlaceholder: "Escriba para buscar...",
      noResultsText: "No se encontraron resultados",
      loadingText: "Cargando opciones...",
      size: "normal", // 'normal' o 'sm'
      required: false,
      disabled: false,
      data: [], // Array de opciones estáticas
      ajax: null, // Configuración para carga dinámica
      onSelect: null, // Callback cuando se selecciona una opción
      onOpen: null, // Callback cuando se abre el dropdown
      onClose: null, // Callback cuando se cierra el dropdown
      ...options,
    };

    this.isOpen = false;
    this.selectedOption = null;
    this.filteredOptions = [];
    this.highlightedIndex = -1;
    this.searchTimeout = null;

    this.init();
  }

  init() {
    this.createElements();
    this.bindEvents();
    this.loadOptions();
  }

  createElements() {
    // Aplicar clases según configuración
    if (this.options.size === "sm") {
      this.container.classList.add("size-sm");
    }

    // Obtener elementos del DOM
    this.input = this.container.querySelector(".searchable-select-input");
    this.dropdown = this.container.querySelector(".searchable-select-dropdown");
    this.searchInput = this.container.querySelector(
      ".searchable-select-search-input"
    );
    this.optionsContainer = this.container.querySelector(
      ".searchable-select-options"
    );
    this.loadingElement = this.container.querySelector(
      ".searchable-select-loading"
    );
    this.noResultsElement = this.container.querySelector(
      ".searchable-select-no-results"
    );
    this.hiddenInput = this.container.querySelector(".searchable-select-value");

    // Configurar placeholders
    this.input.placeholder = this.options.placeholder;
    this.searchInput.placeholder = this.options.searchPlaceholder;

    // Configurar atributos
    if (this.options.required) {
      this.hiddenInput.required = true;
    }

    if (this.options.disabled) {
      this.disable();
    }
  }

  bindEvents() {
    // Click en el input principal
    this.input.addEventListener("click", (e) => {
      e.preventDefault();
      if (!this.options.disabled) {
        this.toggle();
      }
    });

    // Búsqueda en tiempo real
    this.searchInput.addEventListener("input", (e) => {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.search(e.target.value);
      }, 300);
    });

    // Navegación con teclado en el input de búsqueda
    this.searchInput.addEventListener("keydown", (e) => {
      this.handleKeyNavigation(e);
    });

    // Click fuera del componente para cerrar
    document.addEventListener("click", (e) => {
      if (!this.container.contains(e.target)) {
        this.close();
      }
    });

    // Prevenir que el dropdown se cierre al hacer click dentro
    this.dropdown.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  }

  async loadOptions() {
    this.showLoading();

    try {
      let options = [];

      if (this.options.ajax) {
        // Carga dinámica via AJAX
        const response = await this.fetchOptions();
        options = response;
      } else {
        // Usar datos estáticos
        options = this.options.data;
      }

      this.setOptions(options);
    } catch (error) {
      console.error("Error loading options:", error);
      this.showNoResults();
    }
  }

  async fetchOptions(searchTerm = "") {
    const ajaxConfig = this.options.ajax;
    const url =
      typeof ajaxConfig.url === "function"
        ? ajaxConfig.url(searchTerm)
        : ajaxConfig.url;

    const params = new URLSearchParams({
      ...(ajaxConfig.data || {}),
      q: searchTerm,
    });

    const response = await fetch(`${url}?${params}`, {
      method: ajaxConfig.method || "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
        ...(ajaxConfig.headers || {}),
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      return data.data || [];
    } else {
      throw new Error(data.message || "Error loading options");
    }
  }

  setOptions(options) {
    this.filteredOptions = options;
    this.renderOptions();
  }

  renderOptions() {
    this.hideLoading();
    this.optionsContainer.innerHTML = "";

    if (this.filteredOptions.length === 0) {
      this.showNoResults();
      return;
    }

    this.hideNoResults();

    this.filteredOptions.forEach((option, index) => {
      const optionElement = this.createOptionElement(option, index);
      this.optionsContainer.appendChild(optionElement);
    });
  }

  createOptionElement(option, index) {
    const div = document.createElement("div");
    div.className = "searchable-select-option";
    div.dataset.index = index;
    div.dataset.value = option.id || option.value;

    // Verificar si está seleccionado
    if (
      this.selectedOption &&
      (this.selectedOption.id || this.selectedOption.value) ===
        (option.id || option.value)
    ) {
      div.classList.add("selected");
    }

    // Crear contenido de la opción
    const mainText = document.createElement("div");
    mainText.className = "searchable-select-option-main";
    mainText.textContent = option.texto || option.text || option.label;

    div.appendChild(mainText);

    // Agregar texto secundario si existe
    if (option.subtexto || option.subtext || option.description) {
      const subText = document.createElement("div");
      subText.className = "searchable-select-option-sub";
      subText.textContent =
        option.subtexto || option.subtext || option.description;
      div.appendChild(subText);
    }

    // Event listener para selección
    div.addEventListener("click", () => {
      this.selectOption(option);
    });

    return div;
  }

  search(term) {
    if (this.options.ajax) {
      // Búsqueda dinámica
      this.searchRemote(term);
    } else {
      // Búsqueda local
      this.searchLocal(term);
    }
  }

  async searchRemote(term) {
    this.showLoading();

    try {
      const options = await this.fetchOptions(term);
      this.setOptions(options);
    } catch (error) {
      console.error("Error searching options:", error);
      this.showNoResults();
    }
  }

  searchLocal(term) {
    if (!term.trim()) {
      this.filteredOptions = this.options.data;
    } else {
      const searchTerm = term.toLowerCase();
      this.filteredOptions = this.options.data.filter((option) => {
        const text = (
          option.texto ||
          option.text ||
          option.label ||
          ""
        ).toLowerCase();
        const subtext = (
          option.subtexto ||
          option.subtext ||
          option.description ||
          ""
        ).toLowerCase();
        const code = (option.codigo || option.code || "").toLowerCase();

        return (
          text.includes(searchTerm) ||
          subtext.includes(searchTerm) ||
          code.includes(searchTerm)
        );
      });
    }

    this.renderOptions();
  }

  selectOption(option) {
    this.selectedOption = option;
    this.input.value = option.texto || option.text || option.label;
    this.hiddenInput.value = option.id || option.value;

    // Actualizar UI
    this.updateSelectedOption();
    this.close();

    // Callback
    if (this.options.onSelect) {
      this.options.onSelect(option);
    }

    // Disparar evento change
    this.hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
  }

  updateSelectedOption() {
    // Actualizar opciones visuales
    this.container
      .querySelectorAll(".searchable-select-option")
      .forEach((el) => {
        el.classList.remove("selected");
      });

    if (this.selectedOption) {
      const selectedEl = this.container.querySelector(
        `[data-value="${this.selectedOption.id || this.selectedOption.value}"]`
      );
      if (selectedEl) {
        selectedEl.classList.add("selected");
      }
    }
  }

  handleKeyNavigation(e) {
    const options = this.container.querySelectorAll(
      ".searchable-select-option"
    );

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault();
        this.highlightedIndex = Math.min(
          this.highlightedIndex + 1,
          options.length - 1
        );
        this.updateHighlight();
        break;

      case "ArrowUp":
        e.preventDefault();
        this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
        this.updateHighlight();
        break;

      case "Enter":
        e.preventDefault();
        if (this.highlightedIndex >= 0 && options[this.highlightedIndex]) {
          const optionIndex = Number.parseInt(
            options[this.highlightedIndex].dataset.index
          );
          this.selectOption(this.filteredOptions[optionIndex]);
        }
        break;

      case "Escape":
        e.preventDefault();
        this.close();
        break;
    }
  }

  updateHighlight() {
    const options = this.container.querySelectorAll(
      ".searchable-select-option"
    );

    options.forEach((option, index) => {
      option.classList.toggle("highlighted", index === this.highlightedIndex);
    });

    // Scroll al elemento destacado
    if (this.highlightedIndex >= 0 && options[this.highlightedIndex]) {
      options[this.highlightedIndex].scrollIntoView({
        block: "nearest",
      });
    }
  }

  open() {
    if (this.isOpen || this.options.disabled) return;

    this.isOpen = true;
    this.container.classList.add("open");
    this.searchInput.focus();
    this.highlightedIndex = -1;

    // Callback
    if (this.options.onOpen) {
      this.options.onOpen();
    }
  }

  close() {
    if (!this.isOpen) return;

    this.isOpen = false;
    this.container.classList.remove("open");
    this.searchInput.value = "";
    this.highlightedIndex = -1;

    // Restaurar opciones originales si es búsqueda local
    if (!this.options.ajax) {
      this.filteredOptions = this.options.data;
      this.renderOptions();
    }

    // Callback
    if (this.options.onClose) {
      this.options.onClose();
    }
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  showLoading() {
    this.loadingElement.style.display = "flex";
    this.hideNoResults();
  }

  hideLoading() {
    this.loadingElement.style.display = "none";
  }

  showNoResults() {
    this.noResultsElement.style.display = "flex";
    this.hideLoading();
  }

  hideNoResults() {
    this.noResultsElement.style.display = "none";
  }

  // Métodos públicos
  getValue() {
    return this.hiddenInput.value;
  }

  setValue(value) {
    const option = this.filteredOptions.find(
      (opt) => (opt.id || opt.value) == value
    );
    if (option) {
      this.selectOption(option);
    }
  }

  clear() {
    this.selectedOption = null;
    this.input.value = "";
    this.hiddenInput.value = "";
    this.updateSelectedOption();
  }

  disable() {
    this.options.disabled = true;
    this.input.disabled = true;
    this.container.classList.add("disabled");
  }

  enable() {
    this.options.disabled = false;
    this.input.disabled = false;
    this.container.classList.remove("disabled");
  }

  destroy() {
    // Limpiar eventos y elementos
    this.container.innerHTML = "";
    clearTimeout(this.searchTimeout);
  }
}

// Función helper para inicializar múltiples selects
window.SearchableSelect = SearchableSelect;

// Auto-inicialización para elementos con data-searchable-select
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-searchable-select]").forEach((element) => {
    if (!element.searchableSelect) {
      element.searchableSelect = new SearchableSelect(element);
    }
  });
});
