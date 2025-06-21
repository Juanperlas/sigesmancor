<?php
/**
 * Componente de visualización de imágenes
 * Permite ver imágenes en pantalla completa con zoom y detalles
 */
?>
<div id="image-viewer-modal" class="image-viewer-modal">
  <div class="image-viewer-content">
    <div class="image-viewer-header">
      <h3 id="image-viewer-title">Visualizador de imágenes</h3>
      <div class="image-viewer-actions">
        <button type="button" id="image-viewer-zoom-in" class="image-viewer-btn" title="Acercar">
          <i class="bi bi-zoom-in"></i>
        </button>
        <button type="button" id="image-viewer-zoom-out" class="image-viewer-btn" title="Alejar">
          <i class="bi bi-zoom-out"></i>
        </button>
        <button type="button" id="image-viewer-reset" class="image-viewer-btn" title="Restablecer">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
        <button type="button" id="image-viewer-download" class="image-viewer-btn" title="Descargar">
          <i class="bi bi-download"></i>
        </button>
        <button type="button" id="image-viewer-close" class="image-viewer-btn" title="Cerrar">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>
    <div class="image-viewer-body">
      <div class="image-viewer-container">
        <img id="image-viewer-img" src="/placeholder.svg" alt="Imagen" draggable="false">
      </div>
    </div>
    <div class="image-viewer-footer">
      <div id="image-viewer-details" class="image-viewer-details">
        <span id="image-viewer-caption"></span>
        <span id="image-viewer-info"></span>
      </div>
    </div>
  </div>
</div>
