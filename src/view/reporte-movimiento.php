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
    'busqueda_tabla_amb_origen' => '',
    'busqueda_tabla_amb_destino' => '',
    'busqueda_fecha_desde' => '',
    'busqueda_fecha_hasta' => ''
);

curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Movimiento.php?tipo=listar_movimientos_ordenados_tabla_e",
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
curl_close($curl);
// =================== FIN cURL ===================

if ($err) {
    echo "cURL Error #:" . $err;
    exit;
}

// Decodificar la respuesta JSON
$responseData = json_decode($response, true);

if (!$responseData || !$responseData['status']) {
    echo "Error: No se pudieron obtener los datos de movimientos.";
    exit;
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
    ->setTitle("Reporte de Movimientos")
    ->setDescription("Listado completo de movimientos registrados en el sistema");

$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle('Reporte de Movimientos');

// Definir los encabezados de las columnas
$headers = [
    'A' => 'ID',
    'B' => 'Ambiente Origen',
    'C' => 'Ambiente Destino',
    'D' => 'Usuario Registro',
    'E' => 'Fecha Registro',
    'F' => 'Descripción',
    'G' => 'Institución',
    'H' => 'Bienes Involucrados'
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

// Llenar los datos de los movimientos con mejor formato
$movimientos = $responseData['contenido'] ?? [];
$fila = 2; // Comenzar desde la fila 2 (después de los encabezados)

foreach ($movimientos as $movimiento) {
    // Usar los nombres obtenidos del controlador corregido
    $activeWorksheet->setCellValue('A' . $fila, $movimiento['id'] ?? '');
    $activeWorksheet->setCellValue('B' . $fila, $movimiento['ambiente_origen'] ?? 'N/A');
    $activeWorksheet->setCellValue('C' . $fila, $movimiento['ambiente_destino'] ?? 'N/A');
    $activeWorksheet->setCellValue('D' . $fila, $movimiento['usuario_registro'] ?? 'N/A');
    $activeWorksheet->setCellValue('E' . $fila, $movimiento['fecha_registro'] ?? '');
    $activeWorksheet->setCellValue('F' . $fila, $movimiento['descripcion'] ?? '');
    $activeWorksheet->setCellValue('G' . $fila, $movimiento['institucion'] ?? 'N/A');
    $activeWorksheet->setCellValue('H' . $fila, $movimiento['bienes_involucrados'] ?? 'Sin bienes');
    
    // Aplicar formato a las celdas de datos
    foreach ($headers as $columna => $titulo) {
        $activeWorksheet->getStyle($columna . $fila)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        
        $activeWorksheet->getStyle($columna . $fila)->getFont()
            ->setName('Arial')
            ->setSize(10);
        
        // Alineación específica por columna
        if ($columna == 'A') {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $activeWorksheet->getStyle($columna . $fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        
        $activeWorksheet->getStyle($columna . $fila)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
    
    $fila++;
}

// Ajustar el ancho de las columnas de forma específica
$activeWorksheet->getColumnDimension('A')->setWidth(8);   // ID
$activeWorksheet->getColumnDimension('B')->setWidth(25);  // Ambiente Origen
$activeWorksheet->getColumnDimension('C')->setWidth(25);  // Ambiente Destino
$activeWorksheet->getColumnDimension('D')->setWidth(20);  // Usuario Registro
$activeWorksheet->getColumnDimension('E')->setWidth(18);  // Fecha Registro
$activeWorksheet->getColumnDimension('F')->setWidth(40);  // Descripción
$activeWorksheet->getColumnDimension('G')->setWidth(25);  // Institución
$activeWorksheet->getColumnDimension('H')->setWidth(40);  // Bienes Involucrados (aumenté el ancho)

// Configurar altura de filas
$activeWorksheet->getDefaultRowDimension()->setRowHeight(20);
$activeWorksheet->getRowDimension(1)->setRowHeight(25); // Fila de encabezados más alta

// Agregar información adicional
$filaInfo = $fila + 2;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total de movimientos registrados:');
$activeWorksheet->setCellValue('B' . $filaInfo, count($movimientos));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Fecha de generación:');
$activeWorksheet->setCellValue('B' . $filaInfo, date('d/m/Y H:i:s'));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

// Configurar headers para descarga directa
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_movimientos.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Guardar directamente en la salida (descarga)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;