<?php

/**
 * Componente de carga de imágenes
 * Permite subir, previsualizar y eliminar imágenes
 * 
 * @param string $inputName Nombre del input
 * @param string $containerId ID del contenedor
 * @param string $defaultImage Imagen por defecto
 * @param string $title Título del componente
 * @param bool $required Indica si el campo es requerido
 */
function renderImageUpload($inputName, $containerId, $defaultImage, $title = 'Imagen', $required = false)
{
  $requiredAttr = $required ? 'required' : '';
  $requiredMark = $required ? '<span class="text-danger">*</span>' : '';
?>
  <div class="form-group">
    <label class="form-label small"><?php echo $title; ?> <?php echo $requiredMark; ?></label>
    <div id="container-<?php echo $containerId; ?>" class="image-upload-container" style="height: 200px;">
      <!-- El componente se inicializará mediante JavaScript -->
    </div>
    <div class="form-text small text-muted mt-1">
      Haga clic en la imagen para seleccionar un archivo o use el botón de cámara para tomar una foto.
    </div>
  </div>
<?php
}
?>