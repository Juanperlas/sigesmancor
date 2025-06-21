/**
 * Componente de visualización de imágenes
 * Permite ver imágenes en pantalla completa con zoom y detalles
 */

class ImageViewer {
  constructor() {
    // Elementos DOM
    this.modal = document.getElementById("image-viewer-modal");
    this.img = document.getElementById("image-viewer-img");
    this.title = document.getElementById("image-viewer-title");
    this.caption = document.getElementById("image-viewer-caption");
    this.info = document.getElementById("image-viewer-info");
    this.zoomInBtn = document.getElementById("image-viewer-zoom-in");
    this.zoomOutBtn = document.getElementById("image-viewer-zoom-out");
    this.resetBtn = document.getElementById("image-viewer-reset");
    this.downloadBtn = document.getElementById("image-viewer-download");
    this.closeBtn = document.getElementById("image-viewer-close");

    // Estado
    this.scale = 1;
    this.posX = 0;
    this.posY = 0;
    this.startX = 0;
    this.startY = 0;
    this.isDragging = false;

    // Inicializar
    this.init();
  }

  init() {
    if (!this.modal) return;

    // Configurar botones
    this.zoomInBtn.addEventListener("click", () => this.zoomIn());
    this.zoomOutBtn.addEventListener("click", () => this.zoomOut());
    this.resetBtn.addEventListener("click", () => this.reset());
    this.downloadBtn.addEventListener("click", () => this.download());
    this.closeBtn.addEventListener("click", () => this.hide());

    // Configurar eventos de arrastre
    this.img.addEventListener("mousedown", (e) => this.startDrag(e));
    this.img.addEventListener("touchstart", (e) => this.startDrag(e), {
      passive: false,
    });

    window.addEventListener("mousemove", (e) => this.drag(e));
    window.addEventListener("touchmove", (e) => this.drag(e), {
      passive: false,
    });

    window.addEventListener("mouseup", () => this.endDrag());
    window.addEventListener("touchend", () => this.endDrag());

    // Configurar zoom con rueda del ratón
    this.modal.addEventListener("wheel", (e) => {
      e.preventDefault();
      if (e.deltaY < 0) {
        this.zoomIn();
      } else {
        this.zoomOut();
      }
    });

    // Cerrar con tecla Escape
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.modal.classList.contains("show")) {
        this.hide();
      }
    });

    // Cerrar al hacer clic fuera de la imagen
    this.modal.addEventListener("click", (e) => {
      if (e.target === this.modal) {
        this.hide();
      }
    });
  }

  /**
   * Muestra el visor de imágenes
   * @param {string} src - URL de la imagen
   * @param {string} caption - Título o descripción de la imagen
   * @param {Object} options - Opciones adicionales
   */
  show(src, caption = "", options = {}) {
    // Configurar imagen
    this.img.src = src;
    this.caption.textContent = caption;

    // Configurar título
    this.title.textContent = options.title || "Visualizador de imágenes";

    // Configurar información adicional
    if (options.info) {
      this.info.textContent = options.info;
      this.info.style.display = "block";
    } else {
      this.info.style.display = "none";
    }

    // Mostrar modal
    this.modal.classList.add("show");
    document.body.style.overflow = "hidden";

    // Resetear zoom y posición
    this.reset();

    // Cargar información de la imagen cuando esté disponible
    this.img.onload = () => {
      if (!options.info) {
        const width = this.img.naturalWidth;
        const height = this.img.naturalHeight;
        this.info.textContent = `${width} × ${height} píxeles`;
        this.info.style.display = "block";
      }
    };
  }

  /**
   * Oculta el visor de imágenes
   */
  hide() {
    this.modal.classList.remove("show");
    document.body.style.overflow = "";

    // Limpiar imagen después de la animación
    setTimeout(() => {
      if (!this.modal.classList.contains("show")) {
        this.img.src = "";
      }
    }, 300);
  }

  /**
   * Aumenta el zoom de la imagen
   */
  zoomIn() {
    this.scale = Math.min(this.scale * 1.2, 5);
    this.updateTransform();
  }

  /**
   * Disminuye el zoom de la imagen
   */
  zoomOut() {
    this.scale = Math.max(this.scale / 1.2, 0.5);
    this.updateTransform();

    // Si el zoom es mínimo, centrar la imagen
    if (this.scale <= 0.5) {
      this.posX = 0;
      this.posY = 0;
      this.updateTransform();
    }
  }

  /**
   * Restablece el zoom y la posición
   */
  reset() {
    this.scale = 1;
    this.posX = 0;
    this.posY = 0;
    this.updateTransform();
  }

  /**
   * Descarga la imagen
   */
  download() {
    const link = document.createElement("a");
    link.href = this.img.src;
    link.download = "imagen_" + new Date().getTime() + ".jpg";
    link.click();
  }

  /**
   * Inicia el arrastre de la imagen
   * @param {Event} e - Evento de mouse o touch
   */
  startDrag(e) {
    e.preventDefault();

    // Solo permitir arrastre si hay zoom
    if (this.scale <= 1) return;

    this.isDragging = true;
    this.img.classList.add("grabbing");

    // Obtener posición inicial
    if (e.type === "touchstart") {
      this.startX = e.touches[0].clientX - this.posX;
      this.startY = e.touches[0].clientY - this.posY;
    } else {
      this.startX = e.clientX - this.posX;
      this.startY = e.clientY - this.posY;
    }
  }

  /**
   * Arrastra la imagen
   * @param {Event} e - Evento de mouse o touch
   */
  drag(e) {
    if (!this.isDragging) return;
    e.preventDefault();

    // Calcular nueva posición
    if (e.type === "touchmove") {
      this.posX = e.touches[0].clientX - this.startX;
      this.posY = e.touches[0].clientY - this.startY;
    } else {
      this.posX = e.clientX - this.startX;
      this.posY = e.clientY - this.startY;
    }

    this.updateTransform();
  }

  /**
   * Finaliza el arrastre de la imagen
   */
  endDrag() {
    this.isDragging = false;
    this.img.classList.remove("grabbing");
  }

  /**
   * Actualiza la transformación de la imagen
   */
  updateTransform() {
    this.img.style.transform = `translate(${this.posX}px, ${this.posY}px) scale(${this.scale})`;
  }
}

// Crear instancia global
window.imageViewer = new ImageViewer();

// Inicializar clics en imágenes para abrir el visor
document.addEventListener("DOMContentLoaded", () => {
  // Configurar todas las imágenes con data-image-viewer
  document.addEventListener("click", (e) => {
    const target = e.target;
    if (target.tagName === "IMG" && target.hasAttribute("data-image-viewer")) {
      e.preventDefault();
      e.stopPropagation();

      // Obtener información de la fila
      const row = target.closest("tr");
      let caption = "";

      if (row) {
        // Intentar obtener el nombre o título del elemento
        const nameCell = row.querySelector("td:nth-child(3)"); // Columna de nombre
        if (nameCell) {
          caption = nameCell.textContent.trim();
        }
      }

      window.imageViewer.show(target.src, caption);
    }
  });
});
