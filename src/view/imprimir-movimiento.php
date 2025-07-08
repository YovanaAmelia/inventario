<?php
$ruta = explode("/", $_GET['views']);
if (!isset($ruta[1]) || $ruta[1]=="") {
    header("location: " .BASE_URL."movimientos");
}

 $curl = curl_init(); //inicia la sesión cURL
 curl_setopt_array($curl, array(
     CURLOPT_URL => BASE_URL_SERVER."src/control/Movimiento.php?tipo=buscar_movimiento_id&sesion=".$_SESSION['sesion_id']."&token=".$_SESSION['sesion_token']."&data=".$ruta[1], //url a la que se conecta
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
    //print_r($respuesta);
    $contenido_pdf='';
    $contenido_pdf.=' 
   
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
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    td, th {
      border: 1px solid black;
      padding: 6px;
      text-align: center;
      font-size: 14px;
    }
    .info {
      margin-top: 20px;
    }
    .info p {
      margin: 5px 0;
    }
    .motivo {
      margin: 20px 0;
    }
    .firmas {
      margin-top: 50px;
      display: flex;
      justify-content: space-between;
    }
    .firmas div {
      text-align: center;
      width: 40%;
    }
    .footer {
      margin-top: 40px;
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
    <tbody>
    ';
 
    ?>
    
    <?php
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
   

    $contenido_pdf .="
    
    </tbody>
  </table>


  <div class='footer'>
    <p>Ayacucho, _ de __ del 2024</p>
  </div>

  <div class='firmas'>
    <div>
      <p>__________________</p>
      <p>ENTREGUE CONFORME</p>
    </div>
    <div>
      <p>________________</p>
      <p>RECIBÍ CONFORME</p>
    </div>
  </div>

</body>
</html>
    ";
    ?>
    
     
    <?php
    require_once('./vendor/tecnickcom/tcpdf/tcpdf.php');
    $pdf =new TCPDF();
    //set document informacion
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Anibal yucra');
    $pdf->SetTitle('Reporte de Movimientos');
    //
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    
    //asignar

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    //set font
$pdf->SetFont('helvetica', 'B', 12);
// add a page
$pdf->AddPage();
//the html cont
$pdf->writeHTML($contenido_pdf);
  //Close and output PDF document
  ob_clean();
$pdf->Output('example_006.pdf', 'I');

}
