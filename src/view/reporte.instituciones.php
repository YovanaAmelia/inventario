<?php
// =================== INICIA cURL ===================
$curl = curl_init();

// Preparar los datos POST para la API
$postData = array(
    'sesion' => $_SESSION['sesion_id'],
    'token' => $_SESSION['sesion_token'],
    'pagina' => 1,
    'cantidad_mostrar' => 10000, // Gran cantidad para obtener todos los registros
    'busqueda_tabla_nombre' => '',
    'busqueda_tabla_codigo' => '',
    'busqueda_tabla_ruc' => ''
);

curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Institucion.php?tipo=listar_instituciones_ordenados_tabla",
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
    echo "Error: No se pudieron obtener los datos de instituciones.";
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
    ->setTitle("Reporte de Instituciones")
    ->setDescription("Listado completo de instituciones registradas en el sistema");

$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setTitle('Reporte de Instituciones');

// Definir los encabezados de las columnas
$headers = [
    'A' => 'ID',
    'B' => 'Código Modular',
    'C' => 'RUC',
    'D' => 'Nombre de la Institución',
    'E' => 'ID Beneficiario',
    'F' => 'Nombre Beneficiario',
    'G' => 'Correo Beneficiario',
    'H' => 'Teléfono Beneficiario',
    'I' => 'Total de Ambientes',
    'J' => 'Total de Bienes'
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

// Llenar los datos de las instituciones con mejor formato
$instituciones = $responseData['contenido'] ?? [];
$fila = 2; // Comenzar desde la fila 2 (después de los encabezados)

foreach ($instituciones as $institucion) {
    $activeWorksheet->setCellValue('A' . $fila, $institucion['id'] ?? '');
    $activeWorksheet->setCellValue('B' . $fila, $institucion['cod_modular'] ?? '');
    $activeWorksheet->setCellValue('C' . $fila, $institucion['ruc'] ?? '');
    $activeWorksheet->setCellValue('D' . $fila, $institucion['nombre'] ?? '');
    $activeWorksheet->setCellValue('E' . $fila, $institucion['beneficiario'] ?? '');
    $activeWorksheet->setCellValue('F' . $fila, $institucion['nombre_beneficiario'] ?? '');
    $activeWorksheet->setCellValue('G' . $fila, $institucion['correo_beneficiario'] ?? '');
    $activeWorksheet->setCellValue('H' . $fila, $institucion['telefono_beneficiario'] ?? '');
    
    // Formatear números
    $totalAmbientes = intval($institucion['total_ambientes'] ?? 0);
    $totalBienes = intval($institucion['total_bienes'] ?? 0);
    
    $activeWorksheet->setCellValue('I' . $fila, $totalAmbientes);
    $activeWorksheet->setCellValue('J' . $fila, $totalBienes);
    
    $activeWorksheet->getStyle('I' . $fila)->getNumberFormat()->setFormatCode('#,##0');
    $activeWorksheet->getStyle('J' . $fila)->getNumberFormat()->setFormatCode('#,##0');
    
    // Aplicar formato a las celdas de datos
    foreach ($headers as $columna => $titulo) {
        $activeWorksheet->getStyle($columna . $fila)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        
        $activeWorksheet->getStyle($columna . $fila)->getFont()
            ->setName('Arial')
            ->setSize(10);
        
        // Alineación específica por columna
        if ($columna == 'A' || $columna == 'E' || $columna == 'I' || $columna == 'J') {
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
$activeWorksheet->getColumnDimension('B')->setWidth(18);  // Código Modular
$activeWorksheet->getColumnDimension('C')->setWidth(15);  // RUC
$activeWorksheet->getColumnDimension('D')->setWidth(40);  // Nombre Institución
$activeWorksheet->getColumnDimension('E')->setWidth(12);  // ID Beneficiario
$activeWorksheet->getColumnDimension('F')->setWidth(30);  // Nombre Beneficiario
$activeWorksheet->getColumnDimension('G')->setWidth(35);  // Correo
$activeWorksheet->getColumnDimension('H')->setWidth(15);  // Teléfono
$activeWorksheet->getColumnDimension('I')->setWidth(12);  // Total Ambientes
$activeWorksheet->getColumnDimension('J')->setWidth(12);  // Total Bienes

// Configurar altura de filas
$activeWorksheet->getDefaultRowDimension()->setRowHeight(20);
$activeWorksheet->getRowDimension(1)->setRowHeight(25); // Fila de encabezados más alta

// Agregar información adicional
$filaInfo = $fila + 2;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total de instituciones registradas:');
$activeWorksheet->setCellValue('B' . $filaInfo, count($instituciones));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Fecha de generación:');
$activeWorksheet->setCellValue('B' . $filaInfo, date('d/m/Y H:i:s'));
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);

// Calcular totales generales
$totalAmbientesGeneral = 0;
$totalBienesGeneral = 0;
foreach ($instituciones as $institucion) {
    $totalAmbientesGeneral += intval($institucion['total_ambientes'] ?? 0);
    $totalBienesGeneral += intval($institucion['total_bienes'] ?? 0);
}

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total general de ambientes:');
$activeWorksheet->setCellValue('B' . $filaInfo, $totalAmbientesGeneral);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

$filaInfo++;
$activeWorksheet->setCellValue('A' . $filaInfo, 'Total general de bienes:');
$activeWorksheet->setCellValue('B' . $filaInfo, $totalBienesGeneral);
$activeWorksheet->getStyle('A' . $filaInfo)->getFont()->setBold(true);
$activeWorksheet->getStyle('B' . $filaInfo)->getFont()->setBold(true);

// Configurar headers para descarga directa
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_instituciones.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Guardar directamente en la salida (descarga)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>