<div class="card">
    <div class="card-header">
        <i class="bi bi-gear me-2"></i> Carga Predeterminada de Horas
    </div>
    <div class="card-body">
        <p class="card-text">Aplique las horas predeterminadas a los equipos/componentes seleccionados.</p>
        
        <!-- Buscador -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           id="buscador-predefinido" 
                           class="form-control" 
                           placeholder="Buscar por nombre, código o tipo...">
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="tabla-predefinidos" class="table table-sm table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th class="text-center">
                            <input type="checkbox" id="check-todos" class="form-check-input" title="Seleccionar todos">
                        </th>
                        <th>Equipo/Componente</th>
                        <th class="text-center">Código</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Horas Predefinidas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="d-flex justify-content-center align-items-center py-3">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                Cargando datos...
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-end mt-3">
            <button id="btn-aplicar-predefinidos" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Aplicar Horas Predeterminadas
            </button>
        </div>
    </div>
</div>
