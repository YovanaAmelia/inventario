<?php
// =================== VALIDACIONES INICIALES ===================
// Verificar que las variables de sesión existan
if (!isset($_SESSION['sesion_id']) || !isset($_SESSION['sesion_token'])) {
    echo "Error: Sesión no válida. Por favor, inicie sesión nuevamente.";
    exit;
}

// Verificar que BASE_URL_SERVER esté definida
if (!defined('BASE_URL_SERVER')) {
    echo "Error: URL del servidor no configurada.";
    exit;
}

// =================== INICIA cURL ===================
$curl = curl_init();

// Preparar los datos POST para la API
$postData = array(
    'sesion' => $_SESSION['sesion_id'],
    'token' => $_SESSION['sesion_token'],
    'ies' => $_SESSION['ies'] ?? 1, // ID de la institución desde la sesión
    'pagina' => 1,
    'cantidad_mostrar' => 10000, // Gran cantidad para obtener todos los registros
    'busqueda_codigo' => '',
    'busqueda_detalle' => '',
    'busqueda_encargado' => ''
);

// Configuración mejorada del cURL
curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "/src/control/Ambiente.php?tipo=listar_ambientes_ordenados_tabla_e",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json",
        "User-Agent: Sistema-Gestion-Bienes/1.0"
    ),
    // Configuración SSL (si es necesaria)
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    // Configuración adicional para depuración
    CURLOPT_VERBOSE => false
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);
curl_close($curl);

// =================== DEPURACIÓN MEJORADA ===================
if ($err) {
    echo "Error cURL: " . $err;
    echo "<br>URL utilizada: " . BASE_URL_SERVER . "/src/control/Ambiente.php?tipo=listar_ambientes_ordenados_tabla";
    echo "<br>Datos enviados: " . print_r($postData, true);
    exit;
}

if ($httpCode !== 200) {
    echo "Error HTTP: " . $httpCode;
    echo "<br>Respuesta del servidor: " . $response;
    exit;
}

if (empty($response)) {
    echo "Error: Respuesta vacía del servidor";
    echo "<br>Código HTTP: " . $httpCode;
    exit;
}

// =================== PROCESAMIENTO DE RESPUESTA ===================
// Decodificar la respuesta JSON
$responseData = json_decode($response, true);

// Verificar errores de JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error al decodificar JSON: " . json_last_error_msg();
    echo "<br>Respuesta recibida: " . htmlentities($response);
    exit;
}

// Verificar estructura de la respuesta
if (!$responseData) {
    echo "Error: Respuesta JSON inválida";
    echo "<br>Respuesta recibida: " . htmlentities($response);
    exit;
}

if (!isset($responseData['status'])) {
    echo "Error: Respuesta sin campo 'status'";
    echo "<br>Respuesta completa: " . print_r($responseData, true);
    exit;
}

if (!$responseData['status']) {
    $errorMsg = isset($responseData['msg']) ? $responseData['msg'] : 'Error desconocido';
    echo "Error del servidor: " . $errorMsg;
    echo "<br>Respuesta completa: " . print_r($responseData, true);
    exit;
}

if (!isset($responseData['contenido']) || !is_array($responseData['contenido'])) {
    echo "Error: No se encontró contenido válido en la respuesta";
    echo "<br>Respuesta completa: " . print_r($responseData, true);
    exit;
}

// =================== GENERAR EXCEL ===================
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
    ->setTitle("Reporte de Ambientes")
    ->setDescription("Listado completo de ambientes registrados en el sistema");

$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle('Reporte de Ambientes');

// Definir los encabezados de las columnas
$headers = [
    'A' => 'ID',
    'B' => 'Código',
    'C' => 'Detalle',
    'D' => 'Encargado',
    'E' => 'Otros Detalles',
    'F' => 'Total de Bienes',
    'G' => 'Valor Total de Bienes'
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

// Llenar los datos de los ambientes con mejor formato
$ambientes = $responseData['contenido'];
$fila = 2; // Comenzar desde la fila 2 (después de los encabezados)

foreach ($ambientes as $ambiente) {
    // Convertir objeto a array si es necesario
    if (is_object($ambiente)) {
        $ambiente = (array) $ambiente;
    }
    
    // Formatear valor total
    $valorTotal = number_format($ambiente['valor_total_bienes'] ?? 0, 2);
    
    $activeWorksheet->setCellValue('A' . $fila, $ambiente['id'] ?? '');
    $activeWorksheet->setCellValue('B' . $fila, $ambiente['codigo'] ?? '');
    $activeWorksheet->setCellValue('C' . $fila, $ambiente['detalle'] ?? '');
    $activeWorksheet->setCellValue('D' . $fila, $ambiente['encargado'] ?? '');
    $activeWorksheet->setCellValue('E' . $fila, $ambiente['otros_detalle'] ?? '');
    $activeWorksheet->setCellValue('F' . $fila, $ambiente['total_bienes'] ?? 0);
    $activeWorksheet->setCellValue('G' . $fila, 'S/. ' . $valorTotal);
    
    // Aplicar formato a las celdas de datos
    foreach ($headers as $columna => $titulo) {
        $activeWorksheet->getStyle($columna . $fila)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        
        $activeWorksheet->getStyle($columna . $fila)->getFont()
            ->setName('Arial')
            ->setSize(10);
        
        // Alineación específica por columna
        if ($columna == 'A' || $columna == 'F' || $columna == 'G') {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        
        $activeWorksheet->getStyle($columna . $fila)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Color de fondo para ambientes sin bienes
        if (($ambiente['total_bienes'] ?? 0) == 0) {
            $activeWorksheet->getStyle($columna . $fila)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFF8DC');
        }
    }
    
    $fila++;
}

// Ajustar el ancho de las columnas de forma específica
$activeWorksheet->getColumnDimension('A')->setWidth(8);   // ID
$activeWorksheet->getColumnDimension('B')->setWidth(12);  // Código
$activeWorksheet->getColumnDimension('C')->setWidth(35);  // Detalle
$activeWorksheet->getColumnDimension('D')->setWidth(25);  // Encargado
$activeWorksheet->getColumnDimension('E')->setWidth(40);  // Otros Detalles
$activeWorksheet->getColumnDimension('F')->setWidth(15);  // Total de Bienes
$activeWorksheet->getColumnDimension('G')->setWidth(20);  // Valor Total de Bienes

// Configurar altura de filas
$activeWorksheet->getDefaultRowDimension()->setRowHeight(20);
$activeWorksheet->getRowDimension(1)->setRowHeight(25); // Fila de encabezados más alta

// Agregar información adicional
$filaInfo = $fila + 2;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total de ambientes registrados:');
$activeWorksheet->setCellValue('B' . $filaInfo, count($ambientes));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$ambientesConBienes = count(array_filter($ambientes, function($a) { 
    return (is_array($a) ? ($a['total_bienes'] ?? 0) : ($a->total_bienes ?? 0)) > 0; 
}));
$activeWorksheet->setCellValue('A' . $filaInfo, 'Ambientes con bienes:');
$activeWorksheet->setCellValue('B' . $filaInfo, $ambientesConBienes);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$ambientesSinBienes = count($ambientes) - $ambientesConBienes;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Ambientes sin bienes:');
$activeWorksheet->setCellValue('B' . $filaInfo, $ambientesSinBienes);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$totalBienes = array_sum(array_map(function($a) {
    return is_array($a) ? ($a['total_bienes'] ?? 0) : ($a->total_bienes ?? 0);
}, $ambientes));
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total de bienes en todos los ambientes:');
$activeWorksheet->setCellValue('B' . $filaInfo, $totalBienes);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$valorTotalGeneral = array_sum(array_map(function($a) {
    return is_array($a) ? ($a['valor_total_bienes'] ?? 0) : ($a->valor_total_bienes ?? 0);
}, $ambientes));
$activeWorksheet->setCellValue('A' . $filaInfo, 'Valor total de todos los bienes:');
$activeWorksheet->setCellValue('B' . $filaInfo, 'S/. ' . number_format($valorTotalGeneral, 2));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Fecha de generación:');
$activeWorksheet->setCellValue('B' . $filaInfo, date('d/m/Y H:i:s'));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

// Configurar headers para descarga directa
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_ambientes_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Guardar directamente en la salida (descarga)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>