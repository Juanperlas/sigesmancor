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
if (!tienePermiso('administracion.usuarios.ver')) {
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

    // Copiar la imagen original sobre el fondo blanco (esto convierte transparencia a blanco)
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
                // Fuera del círculo, poner blanco
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
    // Obtener datos del usuario
    $conexion = new Conexion();
    $usuario = $conexion->selectOne(
        "SELECT u.*
         FROM usuarios u
         WHERE u.id = ?",
        [$id]
    );

    if (!$usuario) {
        header("Location: ../../../dashboard.php?error=usuario_no_encontrado");
        exit;
    }

    // Obtener roles del usuario
    $roles = $conexion->select(
        "SELECT r.nombre
         FROM roles r
         INNER JOIN usuarios_roles ur ON r.id = ur.rol_id
         WHERE ur.usuario_id = ?",
        [$id]
    );

    // Verificar fotografía
    $fotografiaPath = !empty($usuario['fotografia']) && file_exists('../../../' . $usuario['fotografia'])
        ? '../../../' . $usuario['fotografia']
        : '../../../assets/img/administracion/usuarios/default.png';

    $fotografiaInfo = getImageInfo($fotografiaPath);
    $fotografiaPath = $fotografiaInfo['path'] ?: '../../../assets/img/administracion/usuarios/default.png';
    $fotografiaType = $fotografiaInfo['type'] ?: 'PNG';

    // Crear imagen circular
    $circularImagePath = createCircularImage($fotografiaPath, $fotografiaType, 120);

    // Verificar logo
    $logoPath = '../../../assets/img/logo.png';
    $logoInfo = getImageInfo($logoPath);
    $logoPath = $logoInfo['path'] ?: null;
    $logoType = $logoInfo['type'] ?: null;

    // Obtener el nombre del usuario autenticado
    $usuarioActual = getUsuarioActual();
    $autor = $usuarioActual['nombre'] ?: 'SIGESMAN';

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
            $this->Cell(0, 3, 'SIGESMAN - Sistema de Gestión de Mantenimiento', 0, 1, 'C');

            // Informe generado
            $this->SetFont($this->fontname, '', 7);
            $this->SetTextColor(108, 117, 125);
            $this->Cell(0, 3, 'Informe generado el ' . date('d/m/Y H:i'), 0, 1, 'C');

            // Número de página alineado a la derecha
            $this->SetXY(24, $this->GetY()); // Reiniciar X al margen izquierdo
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
    $pdf->SetCreator('SIGESMAN');
    $pdf->SetAuthor($autor);
    $pdf->SetTitle('Informe de Usuario - ' . $usuario['nombre_completo']);
    $pdf->SetSubject('Informe generado desde SIGESMAN');
    $pdf->SetKeywords('usuario, informe, SIGESMAN');

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
    $pdf->Cell(0, 8, 'INFORME DE USUARIO', 0, 1, 'L');

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

    // Fotografía del usuario circular
    if ($circularImagePath) {
        $pdf->Image($circularImagePath, 20, 48, 24, 24, 'PNG', '', 'T', false, 300, '', false, false, 0, 'C');
    }

    // Información del usuario
    $pdf->SetFont($fontname, 'B', 16);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY(50, 48);
    $pdf->Cell(100, 6, strtoupper($usuario['nombre_completo']), 0, 1, 'L');

    $pdf->SetFont($fontname, '', 11);
    $pdf->SetTextColor(21, 113, 176);
    $pdf->SetXY(50, 56);
    $pdf->Cell(100, 5, '@' . $usuario['username'], 0, 1, 'L');

    // Estado con badge corporativo
    $estado = $usuario['esta_activo'] ? 'ACTIVO' : 'INACTIVO';
    $estadoColor = $usuario['esta_activo'] ? array(32, 201, 151) : array(230, 57, 70); // success/danger

    $pdf->SetFont($fontname, 'B', 9);
    $pdf->SetXY(50, 64);
    $pdf->SetFillColor($estadoColor[0], $estadoColor[1], $estadoColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(50, 6, $estado, 0, 1, 'C', 1, '', 0, false, 'T', 'M', true, false, 'T', 'C');

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

    // === SECCIÓN INFORMACIÓN PERSONAL ===
    $y = addCompactSectionHeader($pdf, 'INFORMACIÓN PERSONAL', '👤', $fontname, [33, 150, 243]); // Azul vibrante

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 35, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Usuario', $usuario['username'], $fontname);
    $y = addCompactInfoRow($pdf, 'Nombre Completo', $usuario['nombre_completo'], $fontname);
    $y = addCompactInfoRow($pdf, 'Correo', empty($usuario['correo']) ? 'No especificado' : $usuario['correo'], $fontname);
    $y = addCompactInfoRow($pdf, 'Documento', empty($usuario['dni']) ? 'No especificado' : $usuario['dni'], $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN INFORMACIÓN DE CONTACTO ===
    $y = addCompactSectionHeader($pdf, 'INFORMACIÓN DE CONTACTO', '📞', $fontname, [156, 39, 176]); // Púrpura vibrante

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 28, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 28, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Teléfono', empty($usuario['telefono']) ? 'No especificado' : $usuario['telefono'], $fontname);
    $y = addCompactInfoRow($pdf, 'Área de Trabajo', empty($usuario['area']) ? 'No especificada' : $usuario['area'], $fontname);
    $y = addCompactInfoRow($pdf, 'Dirección', empty($usuario['direccion']) ? 'No especificada' : $usuario['direccion'], $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN ROLES Y PERMISOS ===
    $y = addCompactSectionHeader($pdf, 'ROLES Y PERMISOS', '🔐', $fontname, [244, 67, 54]); // Rojo vibrante

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 14, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 14, 2, '1111', 'D');

    // Preparar texto de roles
    $rolesTexto = 'Sin roles asignados';
    if (!empty($roles)) {
        $rolesArray = array_column($roles, 'nombre');
        $rolesTexto = implode(', ', $rolesArray);
    }

    // Mostrar roles
    $y = addCompactInfoRow($pdf, 'Roles Asignados', $rolesTexto, $fontname, true);

    $pdf->Ln(5);

    // === SECCIÓN ESTADO DE LA CUENTA ===
    $y = addCompactSectionHeader($pdf, 'ESTADO DE LA CUENTA', '⚡', $fontname, [76, 175, 80]); // Verde vibrante

    // Contenedor compacto
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 21, 2, '1111', 'F');
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->SetLineWidth(0.1);
    $pdf->RoundedRect(12, $y, $pdf->getPageWidth() - 24, 21, 2, '1111', 'D');

    // Filas de información
    $y = addCompactInfoRow($pdf, 'Estado Actual', $usuario['esta_activo'] ? 'Activo' : 'Inactivo', $fontname);
    $y = addCompactInfoRow($pdf, 'Fecha de Registro', isset($usuario['fecha_creacion']) ? date('d/m/Y H:i:s', strtotime($usuario['fecha_creacion'])) : 'No disponible', $fontname, true);

    // Limpiar archivos temporales
    if ($circularImagePath) {
        @unlink($circularImagePath);
    }
    if (isset($fotografiaInfo['path']) && $fotografiaInfo['path'] && $fotografiaInfo['path'] !== $fotografiaPath) {
        @unlink($fotografiaInfo['path']);
    }
    if (isset($logoInfo['path']) && $logoInfo['path'] && $logoInfo['path'] !== $logoPath) {
        @unlink($logoInfo['path']);
    }

    // Generar y descargar el PDF
    $nombreArchivo = 'informe_usuario_' . strtolower(str_replace(' ', '_', $usuario['username'])) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($nombreArchivo, 'I');
} catch (Exception $e) {
    header("Location: ../../../dashboard.php?error=error_generar_informe&mensaje=" . urlencode($e->getMessage()));
    exit;
}
