<?php
// Incluir archivos necesarios
require_once '../../../db/funciones.php';
require_once '../../../db/conexion.php';
require_once '../../../assets/vendor/tcpdf/tcpdf.php';

// Verificar autenticación
if (!estaAutenticado()) {
    header("Location: ../../../login.php");
    exit;
}

// Verificar permiso
if (!tienePermiso('mantenimientos.preventivo.ver')) {
    header("Location: ../../../dashboard.php?error=no_autorizado");
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../../../dashboard.php?error=id_no_proporcionado");
    exit;
}

$id = intval($_GET['id']);

// Función para obtener el tipo de imagen y convertir WEBP si es necesario
function getImageInfo($path)
{
    if (!file_exists($path)) {
        return ['path' => null, 'type' => null];
    }

    $info = @getimagesize($path);
    if ($info === false) {
        return ['path' => null, 'type' => null];
    }

    $mime = $info['mime'];
    $originalPath = $path;

    // Manejar WEBP
    if ($mime === 'image/webp' && extension_loaded('gd')) {
        $webp = imagecreatefromwebp($path);
        if ($webp) {
            $tempPath = sys_get_temp_dir() . '/temp_' . uniqid() . '.png';
            imagepng($webp, $tempPath);
            imagedestroy($webp);
            return ['path' => $tempPath, 'type' => 'PNG'];
        }
    }

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            return ['path' => $originalPath, 'type' => 'JPG'];
        case 'image/png':
            return ['path' => $originalPath, 'type' => 'PNG'];
        default:
            return ['path' => null, 'type' => null];
    }
}

// Función para crear imagen circular desde cualquier formato
function createCircularImage($imagePath, $imageType, $size = 100)
{
    if (!$imagePath || !$imageType) {
        return null;
    }

    // Crear imagen desde el archivo
    switch (strtoupper($imageType)) {
        case 'JPG':
        case 'JPEG':
            $sourceImage = imagecreatefromjpeg($imagePath);
            break;
        case 'PNG':
            $sourceImage = imagecreatefrompng($imagePath);
            break;
        default:
            return null;
    }

    if (!$sourceImage) {
        return null;
    }

    // Obtener dimensiones originales
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);

    // Calcular el tamaño del cuadrado (el menor de los dos)
    $squareSize = min($originalWidth, $originalHeight);

    // Calcular posición para centrar el recorte
    $x = ($originalWidth - $squareSize) / 2;
    $y = ($originalHeight - $squareSize) / 2;

    // Crear imagen cuadrada con fondo blanco
    $squareImage = imagecreatetruecolor($squareSize, $squareSize);
    $white = imagecolorallocate($squareImage, 255, 255, 255);
    imagefill($squareImage, 0, 0, $white);

    // Copiar la imagen original sobre el fondo blanco
    imagecopyresampled($squareImage, $sourceImage, 0, 0, $x, $y, $squareSize, $squareSize, $squareSize, $squareSize);

    // Crear imagen circular final con fondo blanco
    $circularImage = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($circularImage, 255, 255, 255);
    imagefill($circularImage, 0, 0, $white);

    // Redimensionar la imagen cuadrada al tamaño final
    imagecopyresampled($circularImage, $squareImage, 0, 0, 0, 0, $size, $size, $squareSize, $squareSize);

    // Crear máscara circular
    $mask = imagecreatetruecolor($size, $size);
    $black = imagecolorallocate($mask, 0, 0, 0);
    $white = imagecolorallocate($mask, 255, 255, 255);
    imagefill($mask, 0, 0, $black);
    imagefilledellipse($mask, $size / 2, $size / 2, $size, $size, $white);

    // Aplicar máscara
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $maskPixel = imagecolorat($mask, $x, $y);
            if ($maskPixel == $black) {
                imagesetpixel($circularImage, $x, $y, $white);
            }
        }
    }

    // Guardar imagen temporal
    $tempPath = sys_get_temp_dir() . '/circular_' . uniqid() . '.png';
    imagepng($circularImage, $tempPath);

    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($squareImage);
    imagedestroy($circularImage);
    imagedestroy($mask);

    return $tempPath;
}

try {
    // Obtener datos del mantenimiento preventivo
    $conexion = new Conexion();

    // Consulta principal para obtener datos del mantenimiento
    $mantenimiento = $conexion->selectOne(
        "SELECT mp.*, 
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN 'equipo'
                    WHEN mp.componente_id IS NOT NULL THEN 'componente'
                END as tipo_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.nombre
                    WHEN mp.componente_id IS NOT NULL THEN c.nombre
                END as nombre_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.codigo
                    WHEN mp.componente_id IS NOT NULL THEN c.codigo
                END as codigo_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.marca
                    WHEN mp.componente_id IS NOT NULL THEN c.marca
                END as marca_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.modelo
                    WHEN mp.componente_id IS NOT NULL THEN c.modelo
                END as modelo_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.tipo_orometro
                    WHEN mp.componente_id IS NOT NULL THEN c.tipo_orometro
                END as tipo_orometro,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.orometro_actual
                    WHEN mp.componente_id IS NOT NULL THEN c.orometro_actual
                END as orometro_actual,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.anterior_orometro
                    WHEN mp.componente_id IS NOT NULL THEN c.anterior_orometro
                END as anterior_orometro,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.proximo_orometro
                    WHEN mp.componente_id IS NOT NULL THEN c.proximo_orometro
                END as proximo_orometro,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.mantenimiento
                    WHEN mp.componente_id IS NOT NULL THEN c.mantenimiento
                END as intervalo_mantenimiento,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.imagen
                    WHEN mp.componente_id IS NOT NULL THEN c.imagen
                END as imagen_item,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.ubicacion
                    ELSE 'N/A'
                END as ubicacion,
                CASE 
                    WHEN mp.equipo_id IS NOT NULL THEN e.estado
                    WHEN mp.componente_id IS NOT NULL THEN c.estado
                END as estado_item
         FROM mantenimiento_preventivo mp
         LEFT JOIN equipos e ON mp.equipo_id = e.id
         LEFT JOIN componentes c ON mp.componente_id = c.id
         WHERE mp.id = ?",
        [$id]
    );

    if (!$mantenimiento) {
        header("Location: ../../../dashboard.php?error=mantenimiento_no_encontrado");
        exit;
    }

    // Obtener historial de mantenimientos del mismo equipo/componente
    $historial = [];
    if ($mantenimiento['equipo_id']) {
        $historial = $conexion->select(
            "SELECT hm.*, 'preventivo' as origen
             FROM historial_mantenimiento hm
             WHERE hm.equipo_id = ? AND hm.tipo_mantenimiento = 'preventivo'
             UNION ALL
             SELECT mp.id, mp.descripcion_razon as descripcion, mp.fecha_realizado, 
                    NULL as orometro_realizado, mp.observaciones, mp.imagen, 
                    mp.creado_en, 'preventivo' as tipo_mantenimiento, 
                    mp.id as mantenimiento_id, mp.equipo_id, NULL as componente_id, 'actual' as origen
             FROM mantenimiento_preventivo mp
             WHERE mp.equipo_id = ? AND mp.estado = 'completado'
             ORDER BY fecha_realizado DESC
             LIMIT 10",
            [$mantenimiento['equipo_id'], $mantenimiento['equipo_id']]
        );
    } else {
        $historial = $conexion->select(
            "SELECT hm.*, 'preventivo' as origen
             FROM historial_mantenimiento hm
             WHERE hm.componente_id = ? AND hm.tipo_mantenimiento = 'preventivo'
             UNION ALL
             SELECT mp.id, mp.descripcion_razon as descripcion, mp.fecha_realizado, 
                    NULL as orometro_realizado, mp.observaciones, mp.imagen, 
                    mp.creado_en, 'preventivo' as tipo_mantenimiento, 
                    mp.id as mantenimiento_id, NULL as equipo_id, mp.componente_id, 'actual' as origen
             FROM mantenimiento_preventivo mp
             WHERE mp.componente_id = ? AND mp.estado = 'completado'
             ORDER BY fecha_realizado DESC
             LIMIT 10",
            [$mantenimiento['componente_id'], $mantenimiento['componente_id']]
        );
    }

    // Verificar imagen del equipo/componente
    $imagenPath = !empty($mantenimiento['imagen_item']) && file_exists('../../../' . $mantenimiento['imagen_item'])
        ? '../../../' . $mantenimiento['imagen_item']
        : '../../../assets/img/mantenimiento/preventivo/default.png';

    $imagenInfo = getImageInfo($imagenPath);
    $imagenPath = $imagenInfo['path'] ?: '../../../assets/img/mantenimiento/preventivo/default.png';
    $imagenType = $imagenInfo['type'] ?: 'PNG';

    // Crear imagen circular
    $circularImagePath = createCircularImage($imagenPath, $imagenType, 120);

    // Verificar logo
    $logoPath = '../../../assets/img/logo.png';
    $logoInfo = getImageInfo($logoPath);
    $logoPath = $logoInfo['path'] ?: null;
    $logoType = $logoInfo['type'] ?: null;

    // Obtener el nombre del usuario autenticado
    $usuarioActual = getUsuarioActual();
    $autor = $usuarioActual['nombre'] ?: 'SIGESMANCOR';

    // Crear una clase personalizada de TCPDF para manejar el pie de página
    class MYPDF extends TCPDF
    {
        protected $fontname;
        protected $autor;

        public function setCustomFont($fontname)
        {
            $this->fontname = $fontname;
        }

        public function setAutor($autor)
        {
            $this->autor = $autor;
        }

        // Pie de página personalizado
        public function Footer()
        {
            // Posición a 18 mm del final
            $this->SetY(-18);

            // Línea decorativa
            $this->SetDrawColor(21, 113, 176);
            $this->SetLineWidth(0.3);
            $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());
            $this->Ln(4);

            // SIGESMAN
            $this->SetFont($this->fontname, 'B', 8);
            $this->SetTextColor(21, 113, 176);
            $this->Cell(0, 3, 'SIGESMANCOR - Sistema de Gestión de Mantenimiento de Cordial', 0, 1, 'C');

            // Informe generado
            $this->SetFont($this->fontname, '', 7);
            $this->SetTextColor(108, 117, 125);
            $this->Cell(0, 3, 'Informe generado el ' . date('d/m/Y H:i'), 0, 1, 'C');

            // Número de página alineado a la derecha
            $this->SetXY(24, $this->GetY());
            $this->SetFont($this->fontname, '', 7);
            $this->Cell($this->getPageWidth() - 24, 3, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 1, 'R');
        }
    }

    // Crear instancia de TCPDF personalizada
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar fuente personalizada Exo 2
    $exo2FontPath = '../../../assets/fonts/Exo2-Regular.ttf';
    if (file_exists($exo2FontPath)) {
        $fontname = TCPDF_FONTS::addTTFfont($exo2FontPath, 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($fontname, '', 12);
        $pdf->setCustomFont($fontname);
    } else {
        $pdf->SetFont('dejavusans', '', 12);
        $fontname = 'dejavusans';
        $pdf->setCustomFont($fontname);
    }

    // Configurar autor para el pie de página
    $pdf->setAutor($autor);

    // Configuración del documento
    $pdf->SetCreator('SIGESMANCOR');
    $pdf->SetAuthor($autor);
    $pdf->SetTitle('Informe de Mantenimiento Preventivo - ' . $mantenimiento['codigo_item']);
    $pdf->SetSubject('Informe generado desde SIGESMANCOR');
    $pdf->SetKeywords('mantenimiento, preventivo, informe, SIGESMANCOR');

    // Configurar márgenes más compactos
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(12);

    // Deshabilitar header automático y habilitar footer personalizado
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->setFontSubsetting(true);

    // Agregar una página
    $pdf->AddPage();

    // === HEADER CORPORATIVO COMPACTO ===
    // Fondo con colores corporativos
    $pdf->SetFillColor(21, 113, 176); // Color primario corporativo
    $pdf->Rect(0, 0, $pdf->getPageWidth(), 35, 'F');

    // Elementos decorativos sutiles
    $pdf->SetAlpha(0.15);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Circle(25, -5, 40, 0, 360, 'F');
    $pdf->Circle($pdf->getPageWidth() - 25, 40, 35, 0, 360, 'F');
    $pdf->SetAlpha(1);

    // Logo si está disponible
    if ($logoPath && $logoType) {
        $pdf->Image($logoPath, $pdf->getPageWidth() - 45, 8, 35, '', $logoType, '', 'T', false, 300, '', false, false, 0);
    }

    // Título principal compacto
    $pdf->SetFont($fontname, 'B', 24);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(12, 10);
    $pdf->Cell(0, 8, 'INFORME DE MANTENIMIENTO PREVENTIVO', 0, 1, 'L');

    // Subtítulo
    $pdf->SetFont($fontname, '', 11);
    $pdf->SetTextColor(200, 220, 240);
    $pdf->SetXY(12, 22);
    $pdf->Cell(0, 5, 'Sistema de Gestión de Mantenimiento', 0, 1, 'L');

    // Reset y posicionamiento
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetY(42);

    // === SECCIÓN DE PERFIL COMPACTA ===
    // Contenedor para la información del perfil
    $pdf->SetFillColor(248, 249, 250);
    $pdf->RoundedRect(12, 42, $pdf->getPageWidth() - 24, 35, 3, '1111', 'F');
    $pdf->SetDrawColor(21, 113, 176);
    $pdf->SetLineWidth(0.2);
    $pdf->RoundedRect(12, 42, $pdf->getPageWidth() - 24, 35, 3, '1111', 'D');

    // Imagen del equipo/componente circular
    if ($circularImagePath) {
        $pdf->Image($circularImagePath, 20, 48, 24, 24, 'PNG', '', 'T', false, 300, '', false, false, 0, 'C');
    }

    // Información del mantenimiento
    $pdf->SetFont($fontname, 'B', 16);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY(50, 48);
    $pdf->Cell(100, 6, strtoupper($mantenimiento['nombre_item']), 0, 1, 'L');

    $pdf->SetFont($fontname, '', 11);
    $pdf->SetTextColor(21, 113, 176);
    $pdf->SetXY(50, 56);
    $pdf->Cell(100, 5, 'Código: ' . $mantenimiento['codigo_item'], 0, 1, 'L');

    // Estado con badge corporativo
    $estado = $mantenimiento['estado'];
    $estadoColor = $estado === 'completado' ? array(32, 201, 151) : array(255, 193, 7); // success/warning

    $pdf->SetFont($fontname, 'B', 9);
    $pdf->SetXY(50, 64);
    $pdf->SetFillColor($estadoColor[0], $estadoColor[1], $estadoColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(50, 6, strtoupper($estado), 0, 1, 'C', 1, '', 0, false, 'T', 'M', true, false, 'T', 'C');

    // Posicionamiento para el contenido principal
    $pdf->SetY(82);

    // === FUNCIONES AUXILIARES COMPACTAS ===
    function addCompactSectionHeader($pdf, $title, $icon, $fontname, $color, $y = null)
    {
        if ($y !== null) {
            $pdf->SetY($y);
        }

        // Fondo del encabezado con colores corporativos
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->RoundedRect(12, $pdf->GetY(), $pdf->getPageWidth() - 24, 8, 4, '1111', 'F');

        // Título de la sección
        $pdf->SetFont($fontname, 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(18, $pdf->GetY() + 0.5);
        $pdf->Cell($pdf->getPageWidth() - 36, 7, $icon . ' ' . $title, 0, 1, 'L');

        // Espacio mínimo después del encabezado
        $pdf->Ln(2);

        return $pdf->GetY();
    }

    function addCompactInfoRow($pdf, $label, $value, $fontname, $isLast = false)
    {
        // Fondo para la etiqueta
        $pdf->SetFillColor(240, 245, 250);
        $pdf->Rect(12, $pdf->GetY(), 50, 7, 'F');

        // Etiqueta
        $pdf->SetFont($fontname, 'B', 9);
        $pdf->SetTextColor(21, 113, 176);
        $pdf->SetXY(15, $pdf->GetY());
        $pdf->Cell(44, 7, $label, 0, 0, 'L');

        // Valor
        $pdf->SetFont($fontname, '', 9);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetXY(65, $pdf->GetY());
        $pdf->Cell($pdf->getPageWidth() - 77, 7, $value, 0, 1, 'L');

        // Línea divisoria sutil excepto para la última fila
        if (!$isLast) {
            $pdf->SetDrawColor(230, 235, 240);
            $pdf->SetLineWidth(0.1);
            $pdf->Line(12, $pdf->GetY(), $pdf->getPageWidth() - 12, $pdf->GetY());
        }

        return $pdf->GetY();
    }

    // === SECCIÓN INFORMACIÓN DEL MANTENIMIENTO ===
    $y = addCompactSectionHeader($pdf, 'INFORMACIÓN DEL MANTENIMIENTO', '🔧', $fontname, [33, 150, 243]);

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 42, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 42, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Tipo', ucfirst($mantenimiento['tipo_item']), $fontname);
    $y = addCompactInfoRow($pdf, 'Descripción', $mantenimiento['descripcion_razon'] ?: 'No especificada', $fontname);
    $y = addCompactInfoRow($pdf, 'Fecha Programada', $mantenimiento['fecha_programada'] ? date('d/m/Y', strtotime($mantenimiento['fecha_programada'])) : 'No programada', $fontname);
    $y = addCompactInfoRow($pdf, 'Fecha Realizada', $mantenimiento['fecha_realizado'] ? date('d/m/Y', strtotime($mantenimiento['fecha_realizado'])) : 'Pendiente', $fontname);
    $y = addCompactInfoRow($pdf, 'Estado', ucfirst($mantenimiento['estado']), $fontname);
    $y = addCompactInfoRow($pdf, 'Observaciones', $mantenimiento['observaciones'] ?: 'Sin observaciones', $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN INFORMACIÓN DEL EQUIPO/COMPONENTE ===
    $y = addCompactSectionHeader($pdf, 'INFORMACIÓN DEL ' . strtoupper($mantenimiento['tipo_item']), '⚙️', $fontname, [156, 39, 176]);

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Nombre', $mantenimiento['nombre_item'], $fontname);
    $y = addCompactInfoRow($pdf, 'Código', $mantenimiento['codigo_item'], $fontname);
    $y = addCompactInfoRow($pdf, 'Marca', $mantenimiento['marca_item'] ?: 'No especificada', $fontname);
    $y = addCompactInfoRow($pdf, 'Modelo', $mantenimiento['modelo_item'] ?: 'No especificado', $fontname);
    $y = addCompactInfoRow($pdf, 'Ubicación', $mantenimiento['ubicacion'] ?: 'No especificada', $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN INFORMACIÓN DE ORÓMETRO ===
    $unidad = $mantenimiento['tipo_orometro'] === 'kilometros' ? 'km' : 'hrs';
    $y = addCompactSectionHeader($pdf, 'INFORMACIÓN DE ORÓMETRO', '📊', $fontname, [244, 67, 54]);

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 28, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 28, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Tipo de Orómetro', ucfirst($mantenimiento['tipo_orometro']), $fontname);
    $y = addCompactInfoRow($pdf, 'Orómetro Anterior', number_format($mantenimiento['anterior_orometro'], 2) . ' ' . $unidad, $fontname);
    $y = addCompactInfoRow($pdf, 'Orómetro Actual', number_format($mantenimiento['orometro_actual'], 2) . ' ' . $unidad, $fontname);
    $y = addCompactInfoRow($pdf, 'Próximo Mantenimiento', number_format($mantenimiento['proximo_orometro'], 2) . ' ' . $unidad, $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN HISTORIAL DE MANTENIMIENTOS ===
    if (!empty($historial)) {
        $y = addCompactSectionHeader($pdf, 'HISTORIAL DE MANTENIMIENTOS', '📋', $fontname, [76, 175, 80]);

        // Contenedor compacto
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'F');
        $pdf->SetDrawColor(220, 230, 240);
        $pdf->SetLineWidth(0.1);
        $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'D');

        // Encabezados de tabla
        $pdf->SetFont($fontname, 'B', 8);
        $pdf->SetTextColor(21, 113, 176);
        $pdf->SetXY(15, $y + 2);
        $pdf->Cell(30, 5, 'Fecha', 0, 0, 'L');
        $pdf->Cell(60, 5, 'Descripción', 0, 0, 'L');
        $pdf->Cell(80, 5, 'Observaciones', 0, 1, 'L');

        $pdf->SetFont($fontname, '', 7);
        $pdf->SetTextColor(50, 50, 50);

        $count = 0;
        foreach ($historial as $item) {
            if ($count >= 4) break; // Limitar a 4 registros para que quepa en la página

            $fecha = $item['fecha_realizado'] ? date('d/m/Y', strtotime($item['fecha_realizado'])) : 'Pendiente';
            $descripcion = substr($item['descripcion'], 0, 40) . (strlen($item['descripcion']) > 40 ? '...' : '');
            $observaciones = substr($item['observaciones'] ?: 'Sin observaciones', 0, 50) . (strlen($item['observaciones'] ?: '') > 50 ? '...' : '');

            $pdf->SetXY(15, $pdf->GetY());
            $pdf->Cell(30, 4, $fecha, 0, 0, 'L');
            $pdf->Cell(60, 4, $descripcion, 0, 0, 'L');
            $pdf->Cell(80, 4, $observaciones, 0, 1, 'L');

            $count++;
        }
    }

    // Limpiar archivos temporales
    if ($circularImagePath) {
        @unlink($circularImagePath);
    }
    if (isset($imagenInfo['path']) && $imagenInfo['path'] && $imagenInfo['path'] !== $imagenPath) {
        @unlink($imagenInfo['path']);
    }
    if (isset($logoInfo['path']) && $logoInfo['path'] && $logoInfo['path'] !== $logoPath) {
        @unlink($logoInfo['path']);
    }

    // Generar y descargar el PDF
    $nombreArchivo = 'informe_mantenimiento_preventivo_' . strtolower(str_replace(' ', '_', $mantenimiento['codigo_item'])) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($nombreArchivo, 'I');
} catch (Exception $e) {
    header("Location: ../../../dashboard.php?error=error_generar_informe&mensaje=" . urlencode($e->getMessage()));
    exit;
}
