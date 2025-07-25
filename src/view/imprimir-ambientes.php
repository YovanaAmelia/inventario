<?php
// Asegura la zona horaria correcta
date_default_timezone_set('America/Lima');

// Obtener los datos desde el backend por cURL
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => BASE_URL_SERVER . "src/control/Ambiente.php?tipo=listar_todos_ambientes&sesion=" . $_SESSION['sesion_id'] . "&token=" . $_SESSION['sesion_token'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => array(
        "x-rapidapi-host: " . BASE_URL_SERVER,
        "x-rapidapi-key: XXXX"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("Error en cURL: " . $err);
} else {
    $respuesta = json_decode($response, true);
    // Verificar errores en la decodificación JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error al decodificar JSON: " . json_last_error_msg() . ". Respuesta: " . $response);
    }
}

// ----------------------------
// GENERAR EL PDF CON TCPDF
// ----------------------------
require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// Clase personalizada para el PDF
class MYPDF extends TCPDF {
    // Header
    public function Header() {
        // Rutas de logos
        $logo_dre = 'https://dreayacucho.gob.pe/storage/directory/ZOOEA2msQPiXYkJFx4JLjpoREncLFn-metabG9nby5wbmc=-.webp';
        $logo_goba = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT72gURRvO9EMLPg4EM7_0Ttl2u52Xigbe6IA&s';

        // Logo izquierdo
        $this->Image($logo_dre, 15, 10, 22, 22, '', '', 'T', false, 300, '', false, false, 0, false, false, false);

        // Texto del encabezado
        $this->SetFont('helvetica', 'B', 11);
        $this->SetY(12);
        $this->Cell(0, 6, 'GOBIERNO REGIONAL DE AYACUCHO', 0, 1, 'C');

        $this->SetFont('helvetica', 'B', 13);
        $this->Cell(0, 6, 'DIRECCIÓN REGIONAL DE EDUCACIÓN DE AYACUCHO', 0, 1, 'C');

        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 6, 'Direccion de Administración', 0, 1, 'C');

        // Línea decorativa inferior
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(41, 91, 162);
        $this->Line(15, 35, $this->getPageWidth() - 15, 35);

        // Logo derecho
        $this->Image($logo_goba, 175, 10, 22, 22, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    // Método para el pie de página personalizado
    public function Footer() {
        $this->SetY(-20);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);

        // Línea divisoria
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());

        // Texto de contacto
        $this->SetY(-18);
        $this->Cell(0, 10, 'DRE Ayacucho - Jr. 28 de Julio N° 383 - Huamanga | www.dreaya.gob.pe | ☎ (066) 31-2364', 0, 0, 'C');
    }
}

// Crear nueva instancia del PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuración básica
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Alexis Valdivia');
$pdf->SetTitle('Reporte de Ambientes');
$pdf->SetMargins(15, 40, 15); // Ajustado el margen superior
$pdf->SetAutoPageBreak(TRUE, 30); // Espacio desde el pie
$pdf->SetFont('helvetica', '', 10);

// Añadir una página
$pdf->AddPage();

// Obtener fecha actual para el reporte (usando el formato que funciona en tu hosting)
$fecha_actual = date('Y-m-d H:i:s');
$fecha_obj_actual = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_actual);
$meses = [
    1 => "enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre", "diciembre"
];

if($fecha_obj_actual instanceof DateTime){
    $dia_actual = $fecha_obj_actual->format('j');
    $mes_actual = (int)$fecha_obj_actual->format('n');
    $anio_actual = $fecha_obj_actual->format('Y');
}else{
    $dia_actual = date('j');
    $mes_actual = (int)date('n');
    $anio_actual = date('Y');
}

// Contenido HTML para el PDF
$contenido_pdf = '
<h2 style="text-align:center; text-transform:uppercase; color:#2c3e50;">REPORTE DE AMBIENTES INSTITUCIONALES</h2>

<div style="margin-bottom: 15px;">
    <p style="margin:6px 0;"><b>ENTIDAD</b>: DIRECCIÓN REGIONAL DE EDUCACIÓN - AYACUCHO</p>
    <p style="margin:6px 0;"><b>ÁREA</b>: OFICINA DE ADMINISTRACIÓN</p>
</div>

<table style="width:100%; border-collapse:collapse; margin-top:15px;" border="1" cellpadding="6">
    <thead>
        <tr style="background-color:#eaeaea; color:#2c3e50;">
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">ITEM</th>
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">INSTITUCIÓN</th>
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">CÓDIGO</th>
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">DETALLE</th>
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">ENCARGADO</th>
            <th style="border:1px solid #ccc; font-size:8px; text-align: center;">OTROS DETALLES</th>
        </tr>
    </thead>
    <tbody>';

// Verificar si hay contenido en la respuesta
if (isset($respuesta['contenido']) && !empty($respuesta['contenido'])) {
    $i = 1;
    foreach ($respuesta['contenido'] as $ambiente) {
        $contenido_pdf .= '
        <tr style="background-color:' . ($i % 2 == 0 ? '#f9f9f9' : '#ffffff') . ';">
            <td style="border:1px solid #ccc; font-size:8px;">' . $i . '</td>
            <td style="border:1px solid #ccc; font-size:8px;">' . (isset($ambiente['institucion']['nombre']) ? $ambiente['institucion']['nombre'] : 'N/A') . '</td>
            <td style="border:1px solid #ccc; font-size:8px;">' . $ambiente['codigo'] . '</td>
            <td style="border:1px solid #ccc; font-size:8px;">' . $ambiente['detalle'] . '</td>
            <td style="border:1px solid #ccc; font-size:8px;">' . $ambiente['encargado'] . '</td>
            <td style="border:1px solid #ccc; font-size:8px;">' . $ambiente['otros_detalle'] . '</td>
        </tr>';
        $i++;
    }
} else {
    $contenido_pdf .= '
    <tr>
        <td colspan="6" style="text-align:center; border:1px solid #ccc; font-size:12.5px;">
            No se encontraron ambientes registrados.
        </td>
    </tr>';
}

$contenido_pdf .= '
    </tbody>
</table>

<p style="text-align:right; margin-top:35px; font-size:10px;">Ayacucho, ' . $dia_actual . ' de ' . $meses[$mes_actual] . ' de ' . $anio_actual . '</p>

<table style="width:100%; padding: 30px 10px 10px 10px">
    <tr>
        <td style="text-align:center;">__________________________<br>ELABORADO POR</td>
        <td style="text-align:center;">__________________________<br>REVISADO POR</td>
    </tr>
</table>';

// Escribir HTML
$pdf->writeHTML($contenido_pdf, true, false, true, false, '');

// Salida del PDF
$pdf->Output('reporte_ambientes.pdf', 'I');
//============================================================+
// END OF FILE
//============================================================+
?>

