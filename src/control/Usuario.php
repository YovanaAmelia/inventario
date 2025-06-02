<?php
session_start();
require_once('../model/admin-sesionModel.php');
require_once('../model/admin-usuarioModel.php');
require_once('../model/adminModel.php');

require '../../vendor/autoload.php';
require '../../vendor/phpmailer/phpmailer/src/Exception.php';
require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../vendor/phpmailer/phpmailer/src/SMTP.php';
$tipo = $_GET['tipo'];

//instanciar la clase categoria model
$objSesion = new SessionModel();
$objUsuario = new UsuarioModel();
$objAdmin = new AdminModel();


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//variables de sesion
$id_sesion = $_POST['sesion'];
$token = $_POST['token'];

if ($tipo == "listar_usuarios_ordenados_tabla") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_dni = $_POST['busqueda_tabla_dni'];
        $busqueda_tabla_nomap = $_POST['busqueda_tabla_nomap'];
        $busqueda_tabla_estado = $_POST['busqueda_tabla_estado'];
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $busqueda_filtro = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla_filtro($busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);
        $arr_Usuario = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);
        $arr_contenido = [];
        if (!empty($arr_Usuario)) {
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido[$i]->dni = $arr_Usuario[$i]->dni;
                $arr_contenido[$i]->nombres_apellidos = $arr_Usuario[$i]->nombres_apellidos;
                $arr_contenido[$i]->correo = $arr_Usuario[$i]->correo;
                $arr_contenido[$i]->telefono = $arr_Usuario[$i]->telefono;
                $arr_contenido[$i]->estado = $arr_Usuario[$i]->estado;
                $opciones = '<button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Usuario[$i]->id . '"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-info" title="Resetear Contraseña" onclick="reset_password(' . $arr_Usuario[$i]->id . ')"><i class="fa fa-key"></i></button>';
                $arr_contenido[$i]->options = $opciones;
            }
            $arr_Respuesta['total'] = count($busqueda_filtro);
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "registrar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $dni = $_POST['dni'];
            $apellidos_nombres = $_POST['apellidos_nombres'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $password =password_hash($dni,PASSWORD_DEFAULT);

            if ($dni == "" || $apellidos_nombres == "" || $correo == "" || $telefono == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Usuario ya se encuentra registrado');
                } else {
                    $id_usuario = $objUsuario->registrarUsuario($dni, $apellidos_nombres, $correo, $telefono,$password);
                    if ($id_usuario > 0) {
                        // array con los id de los sistemas al que tendra el acceso con su rol registrado
                        // caso de administrador y director
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Registro Exitoso');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al registrar producto');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "actualizar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $id = $_POST['data'];
            $dni = $_POST['dni'];
            $nombres_apellidos = $_POST['nombres_apellidos'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $estado = $_POST['estado'];

            if ($id == "" || $dni == "" || $nombres_apellidos == "" || $correo == "" || $telefono == "" || $estado == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    if ($arr_Usuario->id == $id) {
                        $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                        if ($consulta) {
                            $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                        } else {
                            $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                        }
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'dni ya esta registrado');
                    }
                } else {
                    $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                    if ($consulta) {
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "reiniciar_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $id_usuario = $_POST['id'];
        $password = $objAdmin->generar_llave(10);
        $pass_secure = password_hash($password, PASSWORD_DEFAULT);
        $actualizar = $objUsuario->actualizarPassword($id_usuario, $pass_secure);
        if ($actualizar) {
            $arr_Respuesta = array('status' => true, 'mensaje' => 'Contraseña actualizado correctamente a: ' . $password);
        } else {
            $arr_Respuesta = array('status' => false, 'mensaje' => 'Hubo un problema al actualizar la contraseña, intente nuevamente');
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "sent_email_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');

    if($objSesion->verificar_sesion_si_activa($id_sesion, $token)){
    $datos_sesion = $objSesion->buscarSesionLoginById($id_sesion);
    $datos_usuario = $objUsuario->buscarUsuarioById($datos_sesion->id_usuario);
    $llave = $objAdmin->generar_llave(30);
    $token = password_hash($llave, PASSWORD_DEFAULT);
    $update = $objUsuario->updateResetPassword($datos_sesion->id_usuario, $llave, 1);
    if ($update) {

       //Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function


//Load Composer's autoloader (created by composer, not included with PHPMailer)


//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'mail.limon-cito.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'inventario_amelia@limon-cito.com';                     //SMTP username
    $mail->Password   = 'amelia2025';                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('inventario_amelia@limon-cito.com', 'Cambio de contraseña');
    $mail->addAddress($datos_usuario->correo, $datos_usuario->nombres_apellidos);     //Add a recipient
    /* $mail->addAddress('ellen@example.com');               //Name is optional
    $mail->addReplyTo('info@example.com', 'Information');
    $mail->addCC('cc@example.com');
    $mail->addBCC('bcc@example.com');

    //Attachments
    $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
    $mail->addAttachment('/tmp/image.jpg', 'new.jpg');   */  //Optional name

    //Content
    $mail->isHTML(true); 
    $mail->CharSet='UTF-8';                                 //Set email format to HTML
    $mail->Subject = 'cambio de contraseña-Sistema de Inventario';
    $mail->Body    = '<!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SoleStep - Tu Tienda de Calzados</title>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
      <style>
        @keyframes slideIn {
          from { transform: translateX(-30px); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
          from { transform: translateY(20px); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }
        
        @keyframes shimmer {
          0% { background-position: -1000px 0; }
          100% { background-position: 1000px 0; }
        }
        
        body {
          margin: 0;
          padding: 0;
          background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
          font-family: Inter, Arial, sans-serif;
          color: #333;
          min-height: 100vh;
        }
        
        .container {
          max-width: 650px;
          margin: 30px auto;
          background: linear-gradient(145deg, #ffffff, #f8f9ff);
          border-radius: 20px;
          overflow: hidden;
          box-shadow: 0 20px 60px rgba(0,0,0,0.15);
          border: 2px solid transparent;
          background-clip: padding-box;
          position: relative;
        }
        
        .container::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: linear-gradient(45deg, #ff6b35, #f7931e, #ffd23f, #06ffa5, #1cb5e0, #6a82fb, #fc466b);
          background-size: 400% 400%;
          animation: shimmer 3s ease-in-out infinite;
          z-index: -1;
          border-radius: 20px;
          padding: 2px;
        }
        
        .header {
          background: linear-gradient(135deg, #2c3e50, #34495e, #2c3e50);
          color: white;
          padding: 40px 30px;
          text-align: center;
          position: relative;
          overflow: hidden;
        }
        
        .header::before {
          content: ;
          position: absolute;
          top: -50%;
          left: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
          animation: pulse 4s ease-in-out infinite;
        }
        
        .logo {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 15px;
          animation: slideIn 1s ease-out;
        }
        
        .logo-icon {
          width: 60px;
          height: 60px;
          background: linear-gradient(135deg, #ff6b35, #f7931e);
          border-radius: 15px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-right: 15px;
          box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
          position: relative;
          overflow: hidden;
        }
        
        .logo-icon::before {
          content: '👟';
          font-size: 28px;
          z-index: 2;
          position: relative;
        }
        
        .logo-icon::after {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
          animation: shimmer 2s infinite;
        }
        
        .logo-text {
          font-family: 'Playfair Display', serif;
          font-size: 32px;
          font-weight: 700;
          background: linear-gradient(135deg, #ff6b35, #f7931e, #ffd23f);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-subtitle {
          font-size: 16px;
          opacity: 0.9;
          margin-top: 10px;
          font-weight: 400;
        }
        
        .content {
          padding: 40px 35px;
          position: relative;
        }
        
        .welcome-badge {
          display: inline-block;
          background: linear-gradient(135deg, #06ffa5, #1cb5e0);
          color: white;
          padding: 8px 20px;
          border-radius: 25px;
          font-size: 14px;
          font-weight: 600;
          margin-bottom: 25px;
          animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        .content h1 {
          font-family: 'Playfair Display', serif;
          font-size: 28px;
          color: #2c3e50;
          margin-bottom: 20px;
          animation: fadeInUp 1s ease-out 0.5s both;
        }
        
        .content p {
          font-size: 16px;
          line-height: 1.7;
          color: #555;
          margin-bottom: 20px;
          animation: fadeInUp 1s ease-out 0.7s both;
        }
        
        .highlight-text {
          background: linear-gradient(135deg, #ff6b35, #f7931e);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          font-weight: 600;
        }
        
        .button-container {
          text-align: center;
          margin: 35px 0;
          animation: fadeInUp 1s ease-out 0.9s both;
        }
        
        .button {
          display: inline-block;
          background: linear-gradient(135deg, #ff6b35, #f7931e);
          color: #ffffff !important;
          padding: 16px 35px;
          text-decoration: none;
          border-radius: 50px;
          font-weight: 600;
          font-size: 16px;
          text-transform: uppercase;
          letter-spacing: 1px;
          box-shadow: 0 10px 30px rgba(255, 107, 53, 0.4);
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
        }
        
        .button:hover {
          transform: translateY(-3px);
          box-shadow: 0 15px 40px rgba(255, 107, 53, 0.6);
        }
        
        .button::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
          transition: left 0.5s;
        }
        
        .button:hover::before {
          left: 100%;
        }
        
        .products-section {
          background: linear-gradient(135deg, #f8f9ff, #ffffff);
          margin: 30px 0;
          padding: 30px;
          border-radius: 15px;
          border: 1px solid #e8ecf0;
          animation: fadeInUp 1s ease-out 1.1s both;
        }
        
        .products-title {
          font-family: 'Playfair Display', serif;
          font-size: 22px;
          color: #2c3e50;
          text-align: center;
          margin-bottom: 25px;
          position: relative;
        }
        
        .products-title::after {
          content: '';
          position: absolute;
          bottom: -8px;
          left: 50%;
          transform: translateX(-50%);
          width: 60px;
          height: 3px;
          background: linear-gradient(135deg, #ff6b35, #f7931e);
          border-radius: 2px;
        }
        
        .products-grid {
          display: flex;
          justify-content: space-between;
          gap: 20px;
          margin-top: 20px;
        }
        
        .product-item {
          flex: 1;
          text-align: center;
          padding: 20px 15px;
          background: white;
          border-radius: 12px;
          box-shadow: 0 5px 15px rgba(0,0,0,0.08);
          transition: transform 0.3s ease;
        }
        
        .product-item:hover {
          transform: translateY(-5px);
        }
        
        .product-emoji {
          font-size: 40px;
          margin-bottom: 10px;
          display: block;
        }
        
        .product-name {
          font-weight: 600;
          color: #2c3e50;
          margin-bottom: 5px;
        }
        
        .product-discount {
          color: #e74c3c;
          font-weight: 600;
          font-size: 14px;
        }
        
        .social-section {
          background: linear-gradient(135deg, #667eea, #764ba2);
          color: white;
          padding: 25px;
          text-align: center;
          margin: 20px 0;
          border-radius: 12px;
          animation: fadeInUp 1s ease-out 1.3s both;
        }
        
        .social-title {
          font-size: 18px;
          font-weight: 600;
          margin-bottom: 15px;
        }
        
        .social-icons {
          display: flex;
          justify-content: center;
          gap: 15px;
          margin-top: 15px;
        }
        
        .social-icon {
          display: inline-block;
          width: 45px;
          height: 45px;
          background: rgba(255,255,255,0.2);
          border-radius: 50%;
          line-height: 45px;
          text-align: center;
          color: white;
          text-decoration: none;
          font-size: 20px;
          transition: all 0.3s ease;
        }
        
        .social-icon:hover {
          background: rgba(255,255,255,0.3);
          transform: scale(1.1);
        }
        
        .footer {
          background: linear-gradient(135deg, #2c3e50, #34495e);
          color: #ecf0f1;
          text-align: center;
          padding: 30px;
          font-size: 14px;
        }
        
        .footer a {
          color: #3498db;
          text-decoration: none;
          font-weight: 600;
          transition: color 0.3s ease;
        }
        
        .footer a:hover {
          color: #e74c3c;
        }
        
        .footer-divider {
          width: 50px;
          height: 2px;
          background: linear-gradient(135deg, #ff6b35, #f7931e);
          margin: 15px auto;
          border-radius: 1px;
        }
        
        @media screen and (max-width: 600px) {
          .container {
            margin: 15px;
            border-radius: 15px;
          }
          
          .header, .content, .products-section, .social-section, .footer {
            padding: 25px 20px !important;
          }
          
          .logo-text {
            font-size: 24px !important;
          }
          
          .content h1 {
            font-size: 24px !important;
          }
          
          .products-grid {
            flex-direction: column;
            gap: 15px;
          }
          
          .social-icons {
            gap: 10px;
          }
          
          .button {
            padding: 14px 25px !important;
            font-size: 15px !important;
          }
        }
        
        @media screen and (max-width: 480px) {
          .logo {
            flex-direction: column;
          }
          
          .logo-icon {
            margin-right: 0;
            margin-bottom: 10px;
          }
          
          .products-title {
            font-size: 20px !important;
          }
          
          .product-emoji {
            font-size: 30px !important;
          }
        }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <div class="logo">
            <div class="logo-icon"></div>
            <div class="logo-text">SoleStep</div>
          </div>
          <div class="header-subtitle">Tu destino para el calzado perfecto</div>
        </div>
        
        <div class="content">
          <div class="welcome-badge">✨ Cliente Preferencial</div>
          
          <h1>¡Hola [Nombre del cliente]!</h1>
          
          <p>
            Te damos la bienvenida a nuestra nueva colección de <span class="highlight-text">calzados exclusivos</span>. 
            Hemos seleccionado especialmente para ti los mejores diseños de la temporada.
          </p>
          
          <p>
            ¡No te pierdas nuestras <span class="highlight-text">ofertas increíbles</span> con descuentos de hasta el 50% 
            en marcas premium por tiempo limitado!
          </p>
          
          <div class="button-container">
            <a href="https://www.solestep.com/ofertas" class="button">Explorar Ofertas</a>
          </div>
          
          <p>Gracias por elegir SoleStep, donde cada paso cuenta.</p>
        </div>
        
        <div class="products-section">
          <div class="products-title">🔥 Destacados de la Semana</div>
          <div class="products-grid">
            <div class="product-item">
              <span class="product-emoji">👟</span>
              <div class="product-name">Deportivos</div>
              <div class="product-discount">Hasta 40% OFF</div>
            </div>
            <div class="product-item">
              <span class="product-emoji">👠</span>
              <div class="product-name">Elegantes</div>
              <div class="product-discount">30% OFF</div>
            </div>
            <div class="product-item">
              <span class="product-emoji">🥾</span>
              <div class="product-name">Botas</div>
              <div class="product-discount">25% OFF</div>
            </div>
          </div>
        </div>
        
        <div class="social-section">
          <div class="social-title">¡Síguenos en redes sociales!</div>
          <p>Mantente al día con las últimas tendencias y ofertas exclusivas</p>
          <div class="social-icons">
            <a href="#" class="social-icon">📘</a>
            <a href="#" class="social-icon">📷</a>
            <a href="#" class="social-icon">🐦</a>
            <a href="#" class="social-icon">📱</a>
          </div>
        </div>
        
        <div class="footer">
          <div class="footer-divider"></div>
          © 2025 SoleStep - Tienda de Calzados. Todos los derechos reservados.<br><br>
          <a href="https://www.solestep.com/politicas">Políticas de Privacidad</a> | 
          <a href="https://www.solestep.com/terminos">Términos y Condiciones</a><br>
          <a href="https://www.solestep.com/desuscribirse">Cancelar suscripción</a>
        </div>
      </div>
    </body>
    </html>;
   

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
    }else{ 
        echo "fallo la actualizacion";
    }
   // print_r($token);

    }
    
}   