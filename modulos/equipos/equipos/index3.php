<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Componentes | SIGESMANCOR</title>
    <link rel="stylesheet" href="styles3.css">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../../../assets/img/logo.png" alt="Logo" class="logo">
                <span class="logo-text">SIGESMANCOR</span>
            </div>

            <div class="sidebar-menu">
                <div class="menu-section">
                    <h3 class="menu-section-title">GENERAL</h3>
                    <ul class="menu-items">
                        <li class="menu-item">
                            <a href="../../../dashboard.php" class="menu-link">
                                <i class="bi bi-house-door"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <h3 class="menu-section-title">EQUIPOS</h3>
                    <ul class="menu-items">
                        <li class="menu-item">
                            <a href="../equipos/index.php" class="menu-link">
                                <i class="bi bi-truck"></i>
                                <span>Equipos</span>
                            </a>
                        </li>
                        <li class="menu-item active">
                            <a href="index.php" class="menu-link">
                                <i class="bi bi-gear"></i>
                                <span>Componentes</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <h3 class="menu-section-title">MANTENIMIENTO</h3>
                    <ul class="menu-items">
                        <li class="menu-item">
                            <a href="../../mantenimiento/preventivo/index.php" class="menu-link">
                                <i class="bi bi-tools"></i>
                                <span>Preventivo</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="../../mantenimiento/correctivo/index.php" class="menu-link">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Correctivo</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="../../mantenimiento/programado/index.php" class="menu-link">
                                <i class="bi bi-calendar-check"></i>
                                <span>Programado</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <h3 class="menu-section-title">ADMINISTRACIÓN</h3>
                    <ul class="menu-items">
                        <li class="menu-item">
                            <a href="../../administracion/usuarios/index.php" class="menu-link">
                                <i class="bi bi-people"></i>
                                <span>Usuarios</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="../../administracion/personal/index.php" class="menu-link">
                                <i class="bi bi-person-badge"></i>
                                <span>Personal</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="../../administracion/rolespermisos/index.php" class="menu-link">
                                <i class="bi bi-shield-lock"></i>
                                <span>Roles y Permisos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="../../../assets/img/usuarios/superadmin.png" alt="Usuario" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name">Admin Usuario</span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Buscar...">
                        <button class="filter-button">
                            <i class="bi bi-sliders"></i>
                        </button>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-icons">
                        <button class="icon-button">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge"></span>
                        </button>
                        <button class="icon-button">
                            <i class="bi bi-gear"></i>
                        </button>
                    </div>
                    <div class="user-dropdown">
                        <img src="../../../assets/img/usuarios/superadmin.png" alt="Usuario" class="user-avatar-small">
                        <span class="user-name-small">Admin</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title">
                        <h1>Gestión de Componentes</h1>
                        <div class="breadcrumb">
                            <a href="../../../dashboard.php">Dashboard</a>
                            <span class="separator">/</span>
                            <span class="current">Componentes</span>
                        </div>
                    </div>
                    <div class="page-actions">
                        <button class="btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Nuevo Componente
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <h3>Filtros</h3>
                    <div class="filters-container">
                        <div class="filter-group">
                            <label>Estado</label>
                            <div class="select-container">
                                <select id="filtroEstado">
                                    <option>Todos</option>
                                    <option>Activo</option>
                                    <option>Mantenimiento</option>
                                    <option>Averiado</option>
                                    <option>Descanso</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Equipo</label>
                            <div class="select-container">
                                <select id="filtroEquipo">
                                    <option>Todos</option>
                                    <option>Molino</option>
                                    <option>Motor</option>
                                    <option>Perforadora</option>
                                    <option>Planta</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Marca</label>
                            <div class="select-container">
                                <select id="filtroMarca">
                                    <option>Todas</option>
                                    <option>Metso</option>
                                    <option>Siemens</option>
                                    <option>Sandvik</option>
                                    <option>FLSmidth</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="btn-apply">
                                <i class="bi bi-funnel"></i>
                                Aplicar
                            </button>
                            <button class="btn-clear">
                                <i class="bi bi-x"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Panel -->
                <div class="panel">
                    <div class="panel-heading">
                        <h2 class="panel-title">SITUACIÓN DE COMPONENTES</h2>
                    </div>
                    <div class="panel-body">
                        <div class="export-buttons">
                            <button class="btn-export"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                            <button class="btn-export"><i class="bi bi-filetype-csv"></i> CSV</button>
                            <button class="btn-export"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                            <button class="btn-export"><i class="bi bi-printer"></i> Imprimir</button>
                            <div class="search-box table-search">
                                <i class="bi bi-search"></i>
                                <input type="text" placeholder="Buscar:">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center">Imagen</th>
                                        <th class="text-center">Código</th>
                                        <th class="text-center">Nombre</th>
                                        <th class="text-center">Equipo</th>
                                        <th class="text-center">Marca</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Progreso Mantenimiento</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-020</td>
                                        <td>Cámara de Molienda</td>
                                        <td class="text-center">EQP-028 - Molino Terciario</td>
                                        <td class="text-center">Metso</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>726.75 hrs</span>
                                                <span>1,000.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 72%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 1,000.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-019</td>
                                        <td>Impulsor</td>
                                        <td class="text-center">EQP-026 - Motor de Bombeo</td>
                                        <td class="text-center">Siemens</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>1,320.50 hrs</span>
                                                <span>2,000.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 66%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 800.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-018</td>
                                        <td>Brazo Perforador</td>
                                        <td class="text-center">EQP-024 - Perforadora Secundaria</td>
                                        <td class="text-center">Sandvik</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>1,229.50 hrs</span>
                                                <span>2,000.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 61%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 800.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-017</td>
                                        <td>Sistema de Trituración</td>
                                        <td class="text-center">EQP-023 - Planta Pulverizadora</td>
                                        <td class="text-center">FLSmidth</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>1,029.00 hrs</span>
                                                <span>1,500.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 69%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 1,000.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-016</td>
                                        <td>Motor Secundario</td>
                                        <td class="text-center">EQP-021 - Camión Minero Secundario</td>
                                        <td class="text-center">Komatsu</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>1,721.50 hrs</span>
                                                <span>2,000.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 86%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 800.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-015</td>
                                        <td>Rotor Secundario</td>
                                        <td class="text-center">EQP-019 - Molino Secundario</td>
                                        <td class="text-center">Outotec</td>
                                        <td class="text-center"><span class="estado estado-averiado">Averiado</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>500.75 hrs</span>
                                                <span>1,000.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 50%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 800.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-014</td>
                                        <td>Concentrador</td>
                                        <td class="text-center">EQP-018 - Concentrador ICON</td>
                                        <td class="text-center">ICON</td>
                                        <td class="text-center"><span class="estado estado-descanso">Descanso</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>80.00 hrs</span>
                                                <span>300.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 27%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 600.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-013</td>
                                        <td>Sistema de Alimentación</td>
                                        <td class="text-center">EQP-017 - Equipo de Remolienda</td>
                                        <td class="text-center">FLSmidth</td>
                                        <td class="text-center"><span class="estado estado-mantenimiento">Mantenimiento</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>472.50 hrs</span>
                                                <span>500.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 95%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 800.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-012</td>
                                        <td>Cámara de Molienda</td>
                                        <td class="text-center">EQP-016 - Molino Principal</td>
                                        <td class="text-center">Metso</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>609.75 hrs</span>
                                                <span>800.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 76%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 1,000.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/componentes/default.png" class="equipo-img"></td>
                                        <td class="text-center">CMP-011</td>
                                        <td>Rotor</td>
                                        <td class="text-center">EQP-012 - Motor Secundario</td>
                                        <td class="text-center">WEG</td>
                                        <td class="text-center"><span class="estado estado-mantenimiento">Mantenimiento</span></td>
                                        <td class="progreso-cell">
                                            <div class="progreso-info">
                                                <span>923.50 hrs</span>
                                                <span>1,500.00 hrs</span>
                                            </div>
                                            <div class="progreso-bar-container">
                                                <div class="progreso-bar" style="width: 62%"></div>
                                            </div>
                                            <div class="progreso-label">Mant. cada 600.00 hrs</div>
                                        </td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Mostrar <select>
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                    <option>100</option>
                                </select> registros
                            </div>
                            <div class="pagination">
                                <button class="page-btn">Primero</button>
                                <button class="page-btn">Anterior</button>
                                <button class="page-btn active">1</button>
                                <button class="page-btn">2</button>
                                <button class="page-btn">3</button>
                                <button class="page-btn">4</button>
                                <button class="page-btn">Siguiente</button>
                                <button class="page-btn">Último</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const menuToggle = document.querySelector('.menu-toggle');
            const appWrapper = document.querySelector('.app-wrapper');

            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    appWrapper.classList.toggle('sidebar-collapsed');
                });
            }

            // Table row selection
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Remove selected-row class from all rows
                    rows.forEach(r => r.classList.remove('selected-row'));
                    // Add selected-row class to the clicked row
                    this.classList.add('selected-row');
                });
            });
        });
    </script>
</body>

</html>