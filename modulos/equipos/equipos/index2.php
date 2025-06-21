<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos | SIGESMANCOR</title>
    <link rel="stylesheet" href="styles.css">
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
                        <li class="menu-item active">
                            <a href="index.php" class="menu-link">
                                <i class="bi bi-truck"></i>
                                <span>Equipos</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="../componentes/index.php" class="menu-link">
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
                        <h1>Gestión de Equipos</h1>
                        <div class="breadcrumb">
                            <a href="../../../dashboard.php">Dashboard</a>
                            <span class="separator">/</span>
                            <span class="current">Equipos</span>
                        </div>
                    </div>
                    <div class="page-actions">
                        <button class="btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Nuevo Equipo
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
                            <label>Tipo</label>
                            <div class="select-container">
                                <select id="filtroTipo">
                                    <option>Todos</option>
                                    <option>General</option>
                                    <option>Maquina</option>
                                    <option>Motor</option>
                                    <option>Molino</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Ubicación</label>
                            <div class="select-container">
                                <select id="filtroUbicacion">
                                    <option>Todas</option>
                                    <option>Mina Norte</option>
                                    <option>Mina Sur</option>
                                    <option>Planta 1</option>
                                    <option>Planta 2</option>
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
                        <h2 class="panel-title">SITUACIÓN DE EQUIPOS</h2>
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
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Marca/Modelo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Odómetro</th>
                                        <th class="text-center">Ubicación</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">PROBANDO</td>
                                        <td>PROBANDO</td>
                                        <td class="text-center">General</td>
                                        <td class="text-center">PROBANDO / PROBANDO</td>
                                        <td class="text-center"><span class="estado estado-mantenimiento">Mantenimiento</span></td>
                                        <td class="text-center">0.00 hrs</td>
                                        <td class="text-center">PROBANDO</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">MAQ-006</td>
                                        <td>PROB</td>
                                        <td class="text-center">Maquina</td>
                                        <td class="text-center">PROB MAQ / PROB MAQ</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">0.00 hrs</td>
                                        <td class="text-center">ASD</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-030</td>
                                        <td>Pala Eléctrica</td>
                                        <td class="text-center">General</td>
                                        <td class="text-center">P&H / 4100XPC</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">2,322.00 hrs</td>
                                        <td class="text-center">Mina Norte</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-029</td>
                                        <td>Concentrador Secundario</td>
                                        <td class="text-center">Icon</td>
                                        <td class="text-center">ICON</td>
                                        <td class="text-center"><span class="estado estado-descanso">Descanso</span></td>
                                        <td class="text-center">150.00 hrs</td>
                                        <td class="text-center">Almacén</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-028</td>
                                        <td>Molino Terciario</td>
                                        <td class="text-center">Molino</td>
                                        <td class="text-center">Metso</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">806.75 hrs</td>
                                        <td class="text-center">Planta 1</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-027</td>
                                        <td>Motor de Ventilación</td>
                                        <td class="text-center">Motor</td>
                                        <td class="text-center">WEG</td>
                                        <td class="text-center"><span class="estado estado-averiado">Averiado</span></td>
                                        <td class="text-center">400.75 hrs</td>
                                        <td class="text-center">Mina Sur</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-026</td>
                                        <td>Motor de Bombeo</td>
                                        <td class="text-center">Motor</td>
                                        <td class="text-center">Siemens</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">1,506.00 hrs</td>
                                        <td class="text-center">Planta 2</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-025</td>
                                        <td>Cargador Subterráneo</td>
                                        <td class="text-center">Maquina</td>
                                        <td class="text-center">Caterpillar / R1700</td>
                                        <td class="text-center"><span class="estado estado-mantenimiento">Mantenimiento</span></td>
                                        <td class="text-center">1,010.75 hrs</td>
                                        <td class="text-center">Mina Norte</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-024</td>
                                        <td>Perforadora Secundaria</td>
                                        <td class="text-center">Maquina</td>
                                        <td class="text-center">Sandvik / DX800</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">1,420.50 hrs</td>
                                        <td class="text-center">Mina Sur</td>
                                        <td class="text-center acciones">
                                            <button class="btn-accion btn-ver"><i class="bi bi-eye"></i></button>
                                            <button class="btn-accion btn-editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn-accion btn-eliminar"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><img src="../../../assets/img/equipos/equipos/default.png" class="equipo-img"></td>
                                        <td class="text-center">EQP-023</td>
                                        <td>Planta Pulverizadora</td>
                                        <td class="text-center">Pulverizadora</td>
                                        <td class="text-center">FLSmidth / P300</td>
                                        <td class="text-center"><span class="estado estado-activo">Activo</span></td>
                                        <td class="text-center">1,225.25 hrs</td>
                                        <td class="text-center">Planta 1</td>
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