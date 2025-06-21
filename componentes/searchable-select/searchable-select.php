<?php

/**
 * Componente de Select con Búsqueda
 * Permite buscar y filtrar opciones en tiempo real
 */
?>

<!-- Estructura del componente -->
<div class="searchable-select-container" data-searchable-select>
    <div class="searchable-select-input-container">
        <input type="text"
            class="searchable-select-input"
            placeholder="Buscar..."
            autocomplete="off"
            readonly>
        <div class="searchable-select-arrow">
            <i class="bi bi-chevron-down"></i>
        </div>
    </div>

    <div class="searchable-select-dropdown">
        <div class="searchable-select-search">
            <input type="text"
                class="searchable-select-search-input"
                placeholder="Escriba para buscar..."
                autocomplete="off">
        </div>
        <div class="searchable-select-options">
            <div class="searchable-select-loading">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span>Cargando opciones...</span>
            </div>
        </div>
        <div class="searchable-select-no-results" style="display: none;">
            <i class="bi bi-search"></i>
            <span>No se encontraron resultados</span>
        </div>
    </div>

    <!-- Input hidden para el valor seleccionado -->
    <input type="hidden" class="searchable-select-value">
</div>