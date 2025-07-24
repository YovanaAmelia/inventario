<?php 
$ruta = explode("/", $_GET['views']);

if (!isset($ruta[1]) || $ruta[1] == "") {
    header("location: " . BASE_URL. "movimientos");

}


require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');

// 2. CREAR UNA CLASE PERSONALIZADA QUE EXTIENDE DE TCPDF


    // Método para el encabezado personalizado
    class MYPDF extends TCPDF {

      // Método para el encabezado personalizado
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
  


$curl = curl_init(); //inicia la sesión cURL
    curl_setopt_array($curl, array(
        CURLOPT_URL => BASE_URL_SERVER."src/control/Movimiento.php?tipo=buscar_movimiento_id&sesion=".$_SESSION['sesion_id']."&token=".$_SESSION['sesion_token']."&data=". $ruta[1], //url a la que se conecta
        CURLOPT_RETURNTRANSFER => true, //devuelve el resultado como una cadena del tipo curl_exec
        CURLOPT_FOLLOWLOCATION => true, //sigue el encabezado que le envíe el servidor
        CURLOPT_ENCODING => "", // permite decodificar la respuesta y puede ser"identity", "deflate", y "gzip", si está vacío recibe todos los disponibles.
        CURLOPT_MAXREDIRS => 10, // Si usamos CURLOPT_FOLLOWLOCATION le dice el máximo de encabezados a seguir
        CURLOPT_TIMEOUT => 30, // Tiempo máximo para ejecutar
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // usa la versión declarada
        CURLOPT_CUSTOMREQUEST => "GET", // el tipo de petición, puede ser PUT, POST, GET o Delete dependiendo del servicio
        CURLOPT_HTTPHEADER => array(
            "x-rapidapi-host: ".BASE_URL_SERVER,
            "x-rapidapi-key: XXXX"
        ), //configura las cabeceras enviadas al servicio
    )); //curl_setopt_array configura las opciones para una transferencia cURL

    $response = curl_exec($curl); // respuesta generada
    $err = curl_error($curl); // muestra errores en caso de existir

    curl_close($curl); // termina la sesión 

    if ($err) {
        echo "cURL Error #:" . $err; // mostramos el error
    } else {
       $respuesta = json_decode($response);

               // datos para la fechas
        $new_Date = new DateTime();
        $dia = $new_Date->format('d');
        $año = $new_Date->format('Y');
        $mesNumero = (int)$new_Date->format('n'); 

        $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];

       $contenido_pdf = '';

       $contenido_pdf .= '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Papeleta de Rotación de Bienes</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
    }
    h2 {
      text-align: center;
      text-transform: uppercase;
    }
    .info {
      margin-bottom: 20px;
      line-height: 1.8;
    }
    .info b {
      display: inline-block;
      width: 80px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      font-size:9px;
    }
    th, td {
      border: 1px solid black;
      text-align: center;
      padding: 6px;
    }
    .firma {
      margin-top: 80px;
      display: flex;
      padding: 0 50px;
    }
    .firma div {
      text-align: center;
    }
    .fecha {
      margin-top: 30px;
      text-align: right;
    }
  </style>
</head>
<body>

  <h2>PAPELETA DE ROTACIÓN DE BIENES</h2>


  <div class="info">
    <p><strong>ENTIDAD:</strong> DIRECCIÓN REGIONAL DE EDUCACIÓN - AYACUCHO</p>
    <p><strong>ÁREA:</strong> OFICINA DE ADMINISTRACIÓN</p>
    <p><strong>ORIGEN:</strong>'. $respuesta ->amb_origen->codigo.' ' .$respuesta -> amb_origen->codigo.'</p>
    <p><strong>DESTINO:</strong> '. $respuesta ->amb_destino->codigo. '' .$respuesta -> amb_destino->codigo.'</p>
    <p><strong>MOTIVO (*):</strong> '. $respuesta ->movimiento ->descripcion.'</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>ITEM</th>
        <th>CÓDIGO PATRIMONIAL</th>
        <th>NOMBRE DEL BIEN</th>
        <th>MARCA</th>
        <th>COLOR</th>
        <th>MODELO</th>
        <th>ESTADO</th>
      </tr>
    </thead>
    <body>';
        
  
    $contador = 1;
    foreach ($respuesta->detalle as $bien) {
    $contenido_pdf.='<tr>';
    $contenido_pdf .="<td>".$contador ."</td>";
    $contenido_pdf .="<td>".$bien->cod_patrimonial ."</td>";
    $contenido_pdf .="<td>".$bien->denominacion ."</td>";
    $contenido_pdf .="<td>".$bien->marca ."</td>";
    $contenido_pdf .="<td>".$bien->modelo ."</td>";
    $contenido_pdf .="<td>".$bien->color ."</td>";
    $contenido_pdf .="<td>".$bien->estado_conservacion ."</td>";
    $contenido_pdf .="</tr>";
    $contador+=1;
        }


 $contenido_pdf .='  </body>
  </table> 

  <div class="fecha">
    Ayacucho, '. $dia . " de " . $meses[$mesNumero] . " del " . $año.'
  </div>

  <div class="firma">
    <div>
      ------------------------------<br>
      ENTREGUÉ CONFORME
    </div>
    <div>
      ------------------------------<br>
      RECIBÍ CONFORME
    </div>
  </div>

</body>
</html>';

      
              
       

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('REPORTE DE MOVIMIENTOS');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 48, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //ASIGNAR SALTO DE PAGINA AUTO
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set font TIPO DE FUENTE Y TAMAÑO
       

        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($contenido_pdf, true, false,true,false,'');

        //Close and output PDF document
        $pdf->Output('REPORTE_MOVIMIENTO.pdf', 'I');

        exit;

    }

?>

