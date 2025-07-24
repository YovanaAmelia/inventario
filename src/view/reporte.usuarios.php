<?php
// =================== INICIA cURL ===================
$curl = curl_init();

// Preparar los datos POST para la API
$postData = array(
    'sesion' => $_SESSION['sesion_id'],
    'token' => $_SESSION['sesion_token'],
    'ies' => $_SESSION['ies'] ?? 1, // ID de la institución desde la sesión
    'pagina' => 1,
    'cantidad_mostrar' => 10000, // Gran cantidad para obtener todos los registros
    'busqueda_nombre' => '',
    'busqueda_dni' => '',
    'busqueda_estado' => ''
);

curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Usuario.php?tipo=listar_usuarios_ordenados_tabla_e",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/x-www-form-urlencoded",
        "x-rapidapi-host: " . BASE_URL_SERVER,
        "x-rapidapi-key: XXXX"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
// =================== FIN cURL ===================

// Debug: Mostrar información detallada del error
if ($err) {
    echo "cURL Error #:" . $err;
    exit;
}

// Debug: Mostrar código HTTP
if ($httpCode !== 200) {
    echo "HTTP Error Code: " . $httpCode . "\n";
    echo "Response: " . $response;
    exit;
}
// Decodificar la respuesta JSON
$responseData = json_decode($response, true);

// Debug: Verificar errores de JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Raw Response: " . $response;
    exit;
}

// Verificación mejorada de la respuesta
if (!$responseData) {
    echo "Error: Respuesta vacía del servidor.";
    exit;
}

if (!isset($responseData['status'])) {
    echo "Error: Formato de respuesta inválido - falta campo 'status'.";
    exit;
}

if (!$responseData['status']) {
    $errorMsg = isset($responseData['msg']) ? $responseData['msg'] : 'Error desconocido del servidor';
    echo "Error del servidor: " . $errorMsg;
    exit;
}

if (!isset($responseData['contenido'])) {
    echo "Error: Formato de respuesta inválido - falta campo 'contenido'.";
    exit;
}

if (empty($responseData['contenido'])) {
    echo "Advertencia: No hay usuarios registrados en el sistema.";
    // Continuar con archivo vacío en lugar de salir
}

require './vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Crear un nuevo documento
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator("Sistema de Gestión de Bienes")
    ->setLastModifiedBy("Sistema de Gestión de Bienes")
    ->setTitle("Reporte de Usuarios")
    ->setDescription("Listado completo de usuarios registrados en el sistema");

$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle('Reporte de Usuarios');

// Definir los encabezados de las columnas
$headers = [
    'A' => 'ID',
    'B' => 'DNI',
    'C' => 'Nombres y Apellidos',
    'D' => 'Correo Electrónico',
    'E' => 'Teléfono',
    'F' => 'Estado',
    'G' => 'Fecha de Registro',
    'H' => 'Último Acceso'
];

// Configurar encabezados con mejor formato
$fila = 1;
foreach ($headers as $columna => $titulo) {
    $activeWorksheet->setCellValue($columna . $fila, $titulo);
    
    // Aplicar estilo a los encabezados
    $activeWorksheet->getStyle($columna . $fila)->getFont()
        ->setBold(true)
        ->setSize(12)
        ->setName('Arial');
    
    $activeWorksheet->getStyle($columna . $fila)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);
    
    $activeWorksheet->getStyle($columna . $fila)->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_MEDIUM);
    
    // Color de fondo para encabezados
    $activeWorksheet->getStyle($columna . $fila)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E8E8E8');
}

// Llenar los datos de los usuarios con mejor formato
$usuarios = $responseData['contenido'] ?? [];
$fila = 2; // Comenzar desde la fila 2 (después de los encabezados)

foreach ($usuarios as $usuario) {
    // Verificar que $usuario sea un array o objeto
    if (is_object($usuario)) {
        $usuario = (array) $usuario; // Convertir objeto a array
    }
    
    if (!is_array($usuario)) {
        continue; // Saltar si no es un array válido
    }
    
    // Estado texto legible
    $estadoTexto = (isset($usuario['estado']) && $usuario['estado'] == 1) ? 'Activo' : 'Inactivo';
    
    // Formatear fecha de registro
    $fechaRegistro = '';
    if (!empty($usuario['fecha_registro'])) {
        try {
            $fechaRegistro = date('d/m/Y H:i:s', strtotime($usuario['fecha_registro']));
        } catch (Exception $e) {
            $fechaRegistro = $usuario['fecha_registro']; // Usar valor original si hay error
        }
    }
    
    // Manejar último acceso
    $ultimoAcceso = 'Sin accesos';
    if (!empty($usuario['ultimo_acceso']) && $usuario['ultimo_acceso'] !== 'Sin accesos') {
        try {
            $ultimoAcceso = date('d/m/Y H:i:s', strtotime($usuario['ultimo_acceso']));
        } catch (Exception $e) {
            $ultimoAcceso = $usuario['ultimo_acceso'];
        }
    }
    
    $activeWorksheet->setCellValue('A' . $fila, $usuario['id'] ?? '');
    $activeWorksheet->setCellValue('B' . $fila, $usuario['dni'] ?? '');
    $activeWorksheet->setCellValue('C' . $fila, $usuario['nombres_apellidos'] ?? '');
    $activeWorksheet->setCellValue('D' . $fila, $usuario['correo'] ?? '');
    $activeWorksheet->setCellValue('E' . $fila, $usuario['telefono'] ?? '');
    $activeWorksheet->setCellValue('F' . $fila, $estadoTexto);
    $activeWorksheet->setCellValue('G' . $fila, $fechaRegistro);
    $activeWorksheet->setCellValue('H' . $fila, $ultimoAcceso);
    
    // Aplicar formato a las celdas de datos
    foreach ($headers as $columna => $titulo) {
        $activeWorksheet->getStyle($columna . $fila)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        
        $activeWorksheet->getStyle($columna . $fila)->getFont()
            ->setName('Arial')
            ->setSize(10);
        
        // Alineación específica por columna
        if ($columna == 'A' || $columna == 'F') {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        
        $activeWorksheet->getStyle($columna . $fila)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Color de fondo para usuarios inactivos
        if (isset($usuario['estado']) && $usuario['estado'] == 0) {
            $activeWorksheet->getStyle($columna . $fila)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFEEEE');
        }
    }
    
    $fila++;
}

// Ajustar el ancho de las columnas de forma específica
$activeWorksheet->getColumnDimension('A')->setWidth(8);   // ID
$activeWorksheet->getColumnDimension('B')->setWidth(15);  // DNI
$activeWorksheet->getColumnDimension('C')->setWidth(30);  // Nombres y Apellidos
$activeWorksheet->getColumnDimension('D')->setWidth(35);  // Correo Electrónico
$activeWorksheet->getColumnDimension('E')->setWidth(15);  // Teléfono
$activeWorksheet->getColumnDimension('F')->setWidth(12);  // Estado
$activeWorksheet->getColumnDimension('G')->setWidth(20);  // Fecha de Registro
$activeWorksheet->getColumnDimension('H')->setWidth(20);  // Último Acceso

// Configurar altura de filas
$activeWorksheet->getDefaultRowDimension()->setRowHeight(20);
$activeWorksheet->getRowDimension(1)->setRowHeight(25); // Fila de encabezados más alta

// Agregar información adicional
$filaInfo = $fila + 2;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total de usuarios registrados:');
$activeWorksheet->setCellValue('B' . $filaInfo, count($usuarios));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$usuariosActivos = count(array_filter($usuarios, function($u) { 
    $usuario = is_object($u) ? (array) $u : $u;
    return isset($usuario['estado']) && $usuario['estado'] == 1; 
}));
$activeWorksheet->setCellValue('A' . $filaInfo, 'Usuarios activos:');
$activeWorksheet->setCellValue('B' . $filaInfo, $usuariosActivos);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$usuariosInactivos = count($usuarios) - $usuariosActivos;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Usuarios inactivos:');
$activeWorksheet->setCellValue('B' . $filaInfo, $usuariosInactivos);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Fecha de generación:');
$activeWorksheet->setCellValue('B' . $filaInfo, date('d/m/Y H:i:s'));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

// Configurar headers para descarga directa
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_usuarios.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Guardar directamente en la salida (descarga)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;