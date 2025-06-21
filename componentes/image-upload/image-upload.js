/**
 * Componente de carga de imágenes con cámara integrada
 * Permite subir, previsualizar, capturar y eliminar imágenes
 */

class ImageUpload {
  /**
   * Constructor del componente
   * @param {string} containerId - ID del contenedor
   * @param {Object} options - Opciones de configuración
   */
  constructor(containerId, options = {}) {
    // Opciones por defecto
    this.options = {
      maxSize: 2 * 1024 * 1024, // 5MB
      acceptedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
      inputName: "imagen",
      defaultImage: "/assets/img/default.png",
      existingImage: "",
      uploadPath: "uploads/",
      position: "center", // Posición de la cámara: 'center', 'top-left', etc.
      ...options,
    };

    // Elementos DOM
    this.container = document.getElementById(containerId);
    if (!this.container) {
      console.error(`Contenedor con ID '${containerId}' no encontrado`);
      return;
    }

    // Estado
    this.cameraActive = false;
    this.stream = null;

    // Inicializar componente
    this.init();
  }

  /**
   * Inicializa el componente
   */
  init() {
    // Crear estructura HTML
    this.createStructure();

    // Inicializar eventos
    this.initEvents();
  }

  /**
   * Crea la estructura HTML del componente
   */
  createStructure() {
    // Limpiar contenedor
    this.container.innerHTML = "";
    this.container.classList.add("image-upload-container");

    // Crear elementos
    const previewContainer = document.createElement("div");
    previewContainer.className = "image-upload-preview-container";

    // Imagen de previsualización
    const preview = document.createElement("img");
    preview.className = "image-upload-preview";
    preview.id = `preview-${this.container.id}`;
    preview.src = this.options.existingImage || this.options.defaultImage;
    preview.alt = "Imagen";
    previewContainer.appendChild(preview);

    // Video para la cámara (inicialmente oculto)
    const video = document.createElement("video");
    video.className = "image-upload-video";
    video.id = `video-${this.container.id}`;
    video.autoplay = true;
    video.playsinline = true;
    video.style.display = "none";
    previewContainer.appendChild(video);

    // Canvas para capturar la imagen (oculto)
    const canvas = document.createElement("canvas");
    canvas.className = "image-upload-canvas";
    canvas.id = `canvas-${this.container.id}`;
    canvas.style.display = "none";
    previewContainer.appendChild(canvas);

    // Botón de cámara
    const cameraButton = document.createElement("button");
    cameraButton.type = "button";
    cameraButton.className = "image-upload-camera";
    cameraButton.innerHTML = '<i class="bi bi-camera"></i>';
    cameraButton.setAttribute("data-action", "camera");
    cameraButton.title = "Tomar foto";

    // Posicionar la cámara según la opción
    if (this.options.position === "center") {
      cameraButton.style.position = "absolute";
      cameraButton.style.left = "50%";
      cameraButton.style.top = "50%";
      cameraButton.style.transform = "translate(-50%, -50%)";
    } else {
      cameraButton.style.position = "absolute";
      cameraButton.style.top = "10px";
      cameraButton.style.left = "10px";
    }

    previewContainer.appendChild(cameraButton);

    // Botón de capturar (inicialmente oculto)
    const captureButton = document.createElement("button");
    captureButton.type = "button";
    captureButton.className = "image-upload-capture";
    captureButton.innerHTML = '<i class="bi bi-camera"></i>';
    captureButton.setAttribute("data-action", "capture");
    captureButton.title = "Capturar";
    captureButton.style.display = "none";
    previewContainer.appendChild(captureButton);

    // Botón de cancelar cámara (inicialmente oculto)
    const cancelButton = document.createElement("button");
    cancelButton.type = "button";
    cancelButton.className = "image-upload-cancel";
    cancelButton.innerHTML = '<i class="bi bi-x"></i>';
    cancelButton.setAttribute("data-action", "cancel");
    cancelButton.title = "Cancelar";
    cancelButton.style.display = "none";
    previewContainer.appendChild(cancelButton);

    // Botón de eliminar
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "image-upload-remove";
    removeButton.innerHTML = '<i class="bi bi-x"></i>';
    removeButton.setAttribute("data-action", "remove");
    removeButton.title = "Eliminar imagen";
    removeButton.style.display = this.options.existingImage ? "flex" : "none";
    previewContainer.appendChild(removeButton);

    // Input de archivo oculto
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.className = "image-upload-input";
    fileInput.id = `input-${this.container.id}`;
    fileInput.name = this.options.inputName;
    fileInput.accept = this.options.acceptedTypes.join(",");
    fileInput.style.display = "none";

    // Input para imagen existente
    const existingInput = document.createElement("input");
    existingInput.type = "hidden";
    existingInput.id = `existing-${this.container.id}`;
    existingInput.name = `existing_imagen`; // Cambiar a nombre fijo para coincidir con guardar.php
    existingInput.value = this.options.existingImage || "";

    // Input para marcar eliminación
    const removedInput = document.createElement("input");
    removedInput.type = "hidden";
    removedInput.id = `removed-${this.container.id}`;
    removedInput.name = `removed_${this.options.inputName}`;
    removedInput.value = "0";

    // Agregar elementos al contenedor
    this.container.appendChild(previewContainer);
    this.container.appendChild(fileInput);
    this.container.appendChild(existingInput);
    this.container.appendChild(removedInput);

    // Guardar referencias
    this.preview = preview;
    this.video = video;
    this.canvas = canvas;
    this.fileInput = fileInput;
    this.existingInput = existingInput;
    this.removedInput = removedInput;
    this.cameraButton = cameraButton;
    this.captureButton = captureButton;
    this.cancelButton = cancelButton;
    this.removeButton = removeButton;
  }

  /**
   * Inicializa los eventos del componente
   */
  initEvents() {
    // Evento de clic en el contenedor de previsualización
    this.preview.addEventListener("click", () => {
      if (!this.cameraActive) {
        this.fileInput.click();
      }
    });

    // Evento de cambio en el input de archivo
    this.fileInput.addEventListener("change", (e) => {
      this.handleFileSelect(e);
    });

    // Evento de clic en el botón de cámara
    this.cameraButton.addEventListener("click", (e) => {
      e.stopPropagation();
      this.toggleCamera();
    });

    // Evento de clic en el botón de capturar
    this.captureButton.addEventListener("click", (e) => {
      e.stopPropagation();
      this.captureImage();
    });

    // Evento de clic en el botón de cancelar
    this.cancelButton.addEventListener("click", (e) => {
      e.stopPropagation();
      this.stopCamera();
    });

    // Evento de clic en el botón de eliminar
    this.removeButton.addEventListener("click", (e) => {
      e.stopPropagation();
      this.removeImage();
    });

    // Prevenir arrastrar y soltar por defecto
    this.container.addEventListener(
      "dragover",
      (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.container.classList.add("dragover");
      },
      { once: false }
    );

    this.container.addEventListener(
      "dragleave",
      (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.container.classList.remove("dragover");
      },
      { once: false }
    );

    this.container.addEventListener(
      "drop",
      (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.container.classList.remove("dragover");

        if (e.dataTransfer.files.length > 0) {
          const file = e.dataTransfer.files[0];
          // Validar tamaño de archivo primero
          if (file.size > this.options.maxSize) {
            showErrorToast(
              `El archivo es demasiado grande. El tamaño máximo es ${this.formatSize(
                this.options.maxSize
              )}.`
            );
            return;
          }
          // Validar tipo de archivo
          if (!this.options.acceptedTypes.includes(file.type)) {
            showErrorToast(
              "Tipo de archivo no permitido. Por favor, seleccione una imagen válida (JPEG, PNG, GIF, WEBP)."
            );
            return;
          }
          this.handleFile(file);
        }
      },
      { once: false }
    );
  }

  /**
   * Maneja la selección de archivos
   * @param {Event} e - Evento de cambio
   */
  handleFileSelect(e) {
    if (e.target.files.length > 0) {
      this.handleFile(e.target.files[0]);
    }
  }

  /**
   * Procesa el archivo seleccionado
   * @param {File} file - Archivo seleccionado
   */
  handleFile(file) {
    // Validar tamaño de archivo primero
    if (file.size > this.options.maxSize) {
      showErrorToast(
        `El archivo es demasiado grande. El tamaño máximo es ${this.formatSize(
          this.options.maxSize
        )}.`
      );
      return;
    }
    // Validar tipo de archivo
    if (!this.options.acceptedTypes.includes(file.type)) {
      showErrorToast(
        "Tipo de archivo no permitido. Por favor, seleccione una imagen válida (JPEG, PNG, GIF, WEBP)."
      );
      return;
    }
    // Crear URL de objeto para previsualización
    const reader = new FileReader();
    reader.onload = (e) => {
      this.preview.src = e.target.result;
      this.removeButton.style.display = "flex";
      this.existingInput.value = "";
      this.removedInput.value = "0";
      // Actualizar el input de archivo
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      this.fileInput.files = dataTransfer.files;
    };
    reader.readAsDataURL(file);
  }

  /**
   * Activa o desactiva la cámara
   */
  toggleCamera() {
    if (this.cameraActive) {
      this.stopCamera();
    } else {
      this.startCamera();
    }
  }

  /**
   * Inicia la cámara
   */
  startCamera() {
    // Verificar si el navegador soporta getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert(
        "Su navegador no soporta la captura de cámara. Por favor, actualice su navegador."
      );
      return;
    }

    // Solicitar acceso a la cámara
    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: "environment" }, audio: false })
      .then((stream) => {
        // Guardar referencia al stream
        this.stream = stream;

        // Configurar video
        this.video.srcObject = stream;

        // Mostrar elementos de cámara
        this.video.style.display = "block";
        this.preview.style.display = "none";
        this.cameraButton.style.display = "none";
        this.captureButton.style.display = "flex";
        this.cancelButton.style.display = "flex";

        // Actualizar estado
        this.cameraActive = true;
      })
      .catch((error) => {
        console.error("Error al acceder a la cámara:", error);

        // Mensaje de error más específico
        let errorMessage = "No se pudo acceder a la cámara.";
        if (
          error.name === "NotAllowedError" ||
          error.name === "PermissionDeniedError"
        ) {
          errorMessage =
            "Permiso denegado para acceder a la cámara. Por favor, permita el acceso en la configuración de su navegador.";
        } else if (
          error.name === "NotFoundError" ||
          error.name === "DevicesNotFoundError"
        ) {
          errorMessage = "No se encontró ninguna cámara en su dispositivo.";
        } else if (
          error.name === "NotReadableError" ||
          error.name === "TrackStartError"
        ) {
          errorMessage = "La cámara está siendo utilizada por otra aplicación.";
        } else if (error.name === "OverconstrainedError") {
          errorMessage =
            "No se encontró ninguna cámara que cumpla con los requisitos.";
        } else if (error.name === "TypeError") {
          errorMessage = "Parámetros incorrectos para acceder a la cámara.";
        }

        alert(errorMessage);
      });
  }

  /**
   * Detiene la cámara
   */
  stopCamera() {
    if (this.stream) {
      // Detener todos los tracks
      this.stream.getTracks().forEach((track) => track.stop());
      this.stream = null;
    }

    // Ocultar elementos de cámara
    this.video.style.display = "none";
    this.preview.style.display = "block";
    this.cameraButton.style.display = "flex";
    this.captureButton.style.display = "none";
    this.cancelButton.style.display = "none";

    // Actualizar estado
    this.cameraActive = false;
  }

  /**
   * Captura una imagen de la cámara
   */
  captureImage() {
    if (!this.cameraActive) return;

    // Configurar canvas
    const videoWidth = this.video.videoWidth;
    const videoHeight = this.video.videoHeight;

    this.canvas.width = videoWidth;
    this.canvas.height = videoHeight;

    // Dibujar frame actual en el canvas
    const context = this.canvas.getContext("2d");
    context.drawImage(this.video, 0, 0, videoWidth, videoHeight);

    // Convertir a base64
    const imageData = this.canvas.toDataURL("image/jpeg", 0.9);

    // Mostrar en la previsualización
    this.preview.src = imageData;

    // Convertir base64 a blob para el formulario
    this.dataURLtoFile(imageData, "camera_capture.jpg")
      .then((file) => {
        // Verificar tamaño del archivo
        if (file.size > this.options.maxSize) {
          showErrorToast(
            `El archivo es demasiado grande. El tamaño máximo es ${this.formatSize(
              this.options.maxSize
            )}.`
          );
          return;
        }

        // Crear un objeto FileList simulado
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        this.fileInput.files = dataTransfer.files;

        // Actualizar estado
        this.removeButton.style.display = "flex";
        this.existingInput.value = "";
        this.removedInput.value = "0";

        // Detener cámara
        this.stopCamera();
      })
      .catch((error) => {
        console.error("Error al convertir imagen:", error);
        this.showError("Error al procesar la imagen capturada.");
        this.stopCamera();
      });
  }

  /**
   * Convierte un Data URL a File
   * @param {string} dataUrl - Data URL
   * @param {string} filename - Nombre del archivo
   * @returns {Promise<File>} - Promesa con el archivo
   */
  dataURLtoFile(dataUrl, filename) {
    return new Promise((resolve, reject) => {
      try {
        // Convertir base64 a blob
        const arr = dataUrl.split(",");
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);

        while (n--) {
          u8arr[n] = bstr.charCodeAt(n);
        }

        // Crear archivo
        const file = new File([u8arr], filename, { type: mime });
        resolve(file);
      } catch (error) {
        reject(error);
      }
    });
  }

  /**
   * Elimina la imagen seleccionada
   */
  removeImage() {
    this.preview.src = this.options.defaultImage;
    this.fileInput.value = "";
    this.removeButton.style.display = "none";

    // Si había una imagen existente, marcarla como eliminada
    if (this.existingInput.value) {
      this.removedInput.value = "1";
    }

    this.existingInput.value = "";
  }

  /**
   * Formatea el tamaño de archivo en unidades legibles
   * @param {number} bytes - Tamaño en bytes
   * @returns {string} Tamaño formateado
   */
  formatSize(bytes) {
    if (bytes < 1024) return bytes + " bytes";
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + " KB";
    else return (bytes / 1048576).toFixed(2) + " MB";
  }
  /**
   * Muestra un mensaje de error con debounce
   * @param {string} message - Mensaje de error
   */
  showError(message) {
    if (!this.lastToast || Date.now() - this.lastToast > 1000) {
      if (typeof showErrorToast === "function") {
        showErrorToast(message);
      } else {
        alert(message);
      }
      this.lastToast = Date.now();
    }
  }
}
