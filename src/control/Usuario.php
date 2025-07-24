<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once('../model/admin-sesionModel.php');
require_once('../model/admin-movimientoModel.php');
require_once('../model/admin-ambienteModel.php');
require_once('../model/admin-bienModel.php');
require_once('../model/admin-institucionModel.php');
require_once('../model/admin-usuarioModel.php');
require_once('../model/adminModel.php');
$tipo = $_GET['tipo'];

//instanciar la clase categoria model
$objSesion = new SessionModel();
$objMovimiento = new MovimientoModel();
$objAmbiente = new AmbienteModel();
$objBien = new BienModel();
$objAdmin = new AdminModel();
$objInstitucion = new InstitucionModel();
$objUsuario = new UsuarioModel();

//variables de sesion
$id_sesion = $_REQUEST['sesion'];
$token = $_REQUEST['token'];

if ($tipo == "listar_usuarios_ordenados_tabla_e") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    
    try {
        if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
            $ies = $_POST['ies'] ?? 1;
            $pagina = $_POST['pagina'] ?? 1;
            $cantidad_mostrar = $_POST['cantidad_mostrar'] ?? 10;
            $busqueda_nombre = $_POST['busqueda_nombre'] ?? '';
            $busqueda_dni = $_POST['busqueda_dni'] ?? '';
            $busqueda_estado = $_POST['busqueda_estado'] ?? '';
            
            $arr_Respuesta = array('status' => false, 'contenido' => '', 'msg' => '');
            
            // Usar el método para obtener usuarios con filtros
            $arr_Usuarios = $objUsuario->buscarUsuariosConDetalles_tabla_filtro(
                $busqueda_nombre, 
                $busqueda_dni, 
                $busqueda_estado
            );
            
            $arr_contenido = [];
            
            if (!empty($arr_Usuarios)) {
                for ($i = 0; $i < count($arr_Usuarios); $i++) {
                    // Crear array en lugar de objeto para mejor compatibilidad
                    $arr_contenido[$i] = array();
                    $arr_contenido[$i]['id'] = $arr_Usuarios[$i]->id;
                    $arr_contenido[$i]['dni'] = $arr_Usuarios[$i]->dni;
                    $arr_contenido[$i]['nombres_apellidos'] = $arr_Usuarios[$i]->nombres_apellidos;
                    $arr_contenido[$i]['correo'] = $arr_Usuarios[$i]->correo;
                    $arr_contenido[$i]['telefono'] = $arr_Usuarios[$i]->telefono;
                    $arr_contenido[$i]['estado'] = $arr_Usuarios[$i]->estado;
                    $arr_contenido[$i]['fecha_registro'] = $arr_Usuarios[$i]->fecha_registro;
                    
                    // Incluir información de último acceso obtenida del JOIN
                    $arr_contenido[$i]['ultimo_acceso'] = $arr_Usuarios[$i]->ultimo_acceso ?? 'Sin accesos';
                    
                    // Estado texto legible
                    $estadoTexto = ($arr_Usuarios[$i]->estado == 1) ? 'Activo' : 'Inactivo';
                    $badgeClass = ($arr_Usuarios[$i]->estado == 1) ? 'badge-success' : 'badge-danger';
                    
                    // Formatear fecha de registro para mostrar
                    $fechaFormateada = '';
                    if (!empty($arr_Usuarios[$i]->fecha_registro)) {
                        $fechaFormateada = date('d/m/Y H:i', strtotime($arr_Usuarios[$i]->fecha_registro));
                    }
                    
                    // Opciones para la tabla (botones de acción)
                    $opciones = '<div class="btn-group" role="group">';
                    $opciones .= '<button type="button" title="Ver Detalle" class="btn btn-info btn-sm waves-effect waves-light" data-toggle="modal" data-target=".modal_detalle' . $arr_Usuarios[$i]->id . '"><i class="fa fa-eye"></i></button>';
                    $opciones .= '<button type="button" title="Editar" class="btn btn-warning btn-sm waves-effect waves-light" onclick="editarUsuario(' . $arr_Usuarios[$i]->id . ')"><i class="fa fa-edit"></i></button>';
                    $opciones .= '</div>';
                    
                    $arr_contenido[$i]['options'] = $opciones;
                    $arr_contenido[$i]['estado_badge'] = '<span class="badge ' . $badgeClass . '">' . $estadoTexto . '</span>';
                    $arr_contenido[$i]['fecha_registro_formateada'] = $fechaFormateada;
                }
                
                $arr_Respuesta['total'] = count($arr_Usuarios);
                $arr_Respuesta['status'] = true;
                $arr_Respuesta['contenido'] = $arr_contenido;
                $arr_Respuesta['msg'] = 'Usuarios obtenidos correctamente';
            } else {
                // Caso cuando no hay usuarios
                $arr_Respuesta['status'] = true;
                $arr_Respuesta['contenido'] = [];
                $arr_Respuesta['total'] = 0;
                $arr_Respuesta['msg'] = 'No se encontraron usuarios';
            }
        } else {
            $arr_Respuesta['msg'] = 'Sesión inválida o expirada';
        }
    } catch (Exception $e) {
        $arr_Respuesta['status'] = false;
        $arr_Respuesta['msg'] = 'Error interno del servidor: ' . $e->getMessage();
    }
    
    // Enviar headers apropiados
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr_Respuesta, JSON_UNESCAPED_UNICODE);
}

if ($tipo == 'actualizar_password_reset') {
    $id = $_POST['id'];
    $token_email = $_POST['token'];
    $password = $_POST['password'];

    $arrRespuesta = array('status' => false, 'message' => 'Token inválido o expirado');
     // Buscar usuario y validar token
    $datos_usuario = $objUsuario->buscarUsuarioById($id);
    if ($datos_usuario->reset_password == 1 && password_verify($datos_usuario->token_password, $token_email)) {
        // Encriptar nueva contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Actualizar contraseña en base de datos
        $actualizar = $objUsuario->actualizarPassword($id, $passwordHash);
        if ($actualizar) {
             // Limpiar campos de reset después de actualizar exitosamente
            $limpiar_reset = $objUsuario->updateResetPassword($id, '', 0);
            if ($limpiar_reset) {
                $arrRespuesta = array('status' => true, 'message' => 'Contraseña actualizada correctamente');
            } else {
                $arrRespuesta = array('status' => true, 'mensaje' => 'Contraseña actualizada correctamente');
            }
        } else {
            $arrRespuesta = array('status' => false, 'mensaje' => 'Error al actualizar la contraseña');
        }

    }
    echo json_encode($arrRespuesta);
}


if ($tipo == 'validar_datos_reset_password') {
    $id_email = $_POST['id'];
    $token_email = $_POST['token'];

    $arrRespuesta = array('status' => false, 'message' => 'Link Caducado');
    $datos_usuario = $objUsuario->buscarUsuarioById($id_email);
    if ($datos_usuario->reset_password == 1 && password_verify($datos_usuario->token_password, $token_email)) {
        $arrRespuesta = array('status' => true, 'message' => 'Ok');
    }
    echo json_encode($arrRespuesta);
}

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
            $password = $_POST['password'];

            if ($dni == "" || $apellidos_nombres == "" || $correo == "" || $telefono == "" || $password == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Usuario ya se encuentra registrado');
                } else {
                    $id_usuario = $objUsuario->registrarUsuario($dni, $apellidos_nombres, $correo, $telefono, $password);
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
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $datos_sesion = $objSesion->buscarSesionLoginById($id_sesion);
        $datos_usuario = $objUsuario->buscarUsuarioById($datos_sesion->id_usuario);
        $llave = $objAdmin->generar_llave(30);

        $token = password_hash($llave, PASSWORD_DEFAULT);
        $update = $objUsuario->updateResetPassword($datos_sesion->id_usuario, $llave, 1);

        if ($update) {
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = 0; // Cambié de SMTP::DEBUG_SERVER a 0 para producción
                $mail->isSMTP();
                $mail->Host = 'mail.importecsolutions.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'alexisgvaldivia@importecsolutions.com';
                $mail->Password = 'Agvt2006@';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // CONFIGURACIÓN CRÍTICA PARA CARACTERES ESPECIALES
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                //Recipients
                $mail->setFrom('alexisgvaldivia@importecsolutions.com', 'Cambio de Contraseña - Xtreme AI');
                $mail->addAddress($datos_usuario->correo, $datos_usuario->nombres_apellidos);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Restablece tu contraseña - XTREME AI';

                // Generar URL para el reset (asegúrate de definir esta variable)
                $url_reset = "https://tu-dominio.com/reset-password.php?token=" . urlencode($llave);

                // CORREO
                $mail->Body = '
                        <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña - XTREME AI</title>
    <style>
        body {
            background-color: #0f1117;
            font-family: "Segoe UI", "Helvetica Neue", sans-serif;
            color: #e4e4e4;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #1a1d26;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0, 150, 255, 0.15);
        }
        .header {
            background: linear-gradient(135deg, #262626, #041226);
            padding: 30px;
            text-align: center;
        }
        .logo-image {
            display: block;
            margin: 0 auto;
            height: 60px;
            max-width: 100%;
            width: auto;
        }
        .tagline {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin: 10px 0 20px 0;
            text-align: center;
            font-weight: 500;
        }
        .security-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 12px;
            color: white;
            display: inline-block;
            font-weight: 500;
        }
        .content {
            padding: 40px;
        }
        .title {
            font-size: 24px;
            color: #00b4ff;
            margin-bottom: 20px;
            text-align: center;
        }
        .subtitle {
            font-size: 18px;
            color: #cfcfcf;
            margin-bottom: 25px;
            text-align: center;
        }
        .description {
            font-size: 16px;
            color: #cfcfcf;
            margin-bottom: 30px;
            text-align: center;
        }
        .features {
            display: table;
            width: 100%;
            margin: 30px 0;
            table-layout: fixed;
        }
        .feature {
            display: table-cell;
            text-align: center;
            padding: 20px 15px;
            background: rgba(0, 180, 255, 0.05);
            border-radius: 10px;
            margin: 0 5px;
            vertical-align: top;
        }
        .feature-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .feature-title {
            font-size: 14px;
            font-weight: bold;
            color: #00b4ff;
            margin-bottom: 5px;
        }
        .feature-desc {
            font-size: 12px;
            color: #999;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #00b4ff, #006aff);
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin: 30px auto;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 180, 255, 0.3);
        }
        .button:hover {
            background: linear-gradient(135deg, #0090dd, #0055cc);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 180, 255, 0.4);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .security-info {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .security-title {
            font-size: 16px;
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 10px;
        }
        .security-text {
            font-size: 14px;
            color: #cfcfcf;
            margin-bottom: 15px;
        }
        .backup-link {
            background: rgba(0, 180, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            color: #cfcfcf;
        }
        .backup-url {
            color: #00b4ff;
            word-break: break-all;
            font-family: monospace;
        }
        .footer {
            background: #161922;
            padding: 30px;
            text-align: center;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #00b4ff;
            margin-bottom: 5px;
        }
        .company-slogan {
            font-style: italic;
            color: #999;
            margin-bottom: 15px;
        }
        .contact-info {
            font-size: 12px;
            color: #777;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .footer-links {
            margin: 20px 0;
        }
        .footer-links a {
            color: #00b4ff;
            text-decoration: none;
            margin: 0 10px;
            font-size: 12px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .copyright {
            font-size: 11px;
            color: #ffffff;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        @media screen and (max-width: 600px) {
            .container {
                margin: 20px 10px;
            }
            .content {
                padding: 20px;
            }
            .features {
                display: block;
            }
            .feature {
                display: block;
                margin: 10px 0;
            }
            .header {
                padding: 20px;
            }
            .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://drive.google.com/uc?export=dowload&id=1O6sQeUi1EUBq4AE3LJx3JrQmdrYlYigu" alt="XTREME AI - Cortes de Precisión Láser" style="display: block; margin: 0 auto; height: 100px; max-width: 280px; width: auto;" />
            <div class="tagline">Cortes Láser de Precisión • MDF • Acrílico • Madera & Soluciones de Impresión </div>
            <div class="security-badge">🔒 Verificación de Seguridad</div>
        </div>
        
        <div class="content">
            <div class="title">Solicitud de Cambio de Contraseña</div>
            <div class="subtitle">Hola, <strong>' . $datos_usuario->nombres_apellidos . '</strong></div>
            
            <div class="description">
                Hemos recibido una solicitud para cambiar la contraseña de tu cuenta en XTREME AI. Para garantizar la máxima seguridad de tu información y proyectos de corte láser e impresión, necesitamos verificar que realmente fuiste tú quien realizó esta solicitud.
            </div>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-title">Proceso Rápido</div>
                    <div class="feature-desc">Solo tomará unos segundos completar el cambio de contraseña</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔐</div>
                    <div class="feature-title">100% Seguro</div>
                    <div class="feature-desc">Encriptación de nivel empresarial para proteger tu información</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">⏰</div>
                    <div class="feature-title">Válido 2h</div>
                    <div class="feature-desc">El enlace expira automáticamente por tu seguridad</div>
                </div>
            </div>
            
            <div class="button-container">
                <a href="' . BASE_URL . 'reset-password/?data=' . $datos_usuario->id . '&data2=' . urlencode($token) . '" class="button" style="color: white">Cambiar Mi Contraseña</a>
            </div>
            
            <div class="security-info">
                <div class="security-title">Importante: Medidas de Seguridad</div>
                <div class="security-text">
                    Este enlace de verificación expirará automáticamente en <strong>24 horas</strong> por razones de seguridad. Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu cuenta permanecerá protegida y no se realizarán cambios.
                </div>
            </div>
            
            <div class="backup-link">
                <strong>¿Problemas con el botón?</strong> Copia y pega el siguiente enlace en tu navegador:<br>
                <span class="backup-url">' . BASE_URL . 'reset-password/?data=' . $datos_usuario->id . '&data2=' . urlencode($token) . '</span>
            </div>
        </div>
        
        <div class="footer">
            <div class="company-info">
                <div class="company-name">XTREME AI</div>
                <div class="company-slogan">"Precisión láser e innovación en cada proyecto"</div>
                <div class="contact-info">
                    Especialistas en Corte Láser & Impresión Digital<br>
                    Av. San Martín 427,<br>
                    a unos pasos de la comisaria<br>
                    +51 934 717 131  | soporte@xtremeai.com
                </div>
            </div>
            
            <div class="footer-links">
                <a href="#">Inicio</a>
                <a href="#">Soporte 24/7</a>
                <a href="#">Privacidad</a>
                <a href="#">Términos</a>
                <a href="#">Contacto</a>
            </div>
            
            <div class="copyright">
                ©XTREME AI. Todos los derechos reservados.<br>
                Esta es una comunicación automática, no responder a este email.
            </div>
        </div>
    </div>
</body>
</html>
';

                $result = $mail->send();

                if ($result) {
                    $arr_Respuesta = array('status' => true, 'msg' => 'Email enviado correctamente');
                } else {
                    $arr_Respuesta = array('status' => false, 'msg' => 'Error al enviar el email');
                }

            } catch (Exception $e) {
                $arr_Respuesta = array('status' => false, 'msg' => 'Error: ' . $mail->ErrorInfo);
            }
        } else {
            $arr_Respuesta = array('status' => false, 'msg' => 'Fallo al actualizar la base de datos');
        }
    }

    // Devolver respuesta JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr_Respuesta, JSON_UNESCAPED_UNICODE);
}
if ($tipo == "listar_todos_usuarios") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $arr_Respuesta = array('status' => false, 'contenido' => []);
        $arr_Usuarios = $objUsuario->listarTodosLosUsuarios(); // Asegúrate de que este método exista en $objUsuario
        
        $arr_contenido = [];
        if (!empty($arr_Usuarios)) {
            foreach ($arr_Usuarios as $usuario) {
                $arr_contenido[] = [
                    'id' => $usuario->id,
                    'dni' => $usuario->dni,
                    'nombres_apellidos' => $usuario->nombres_apellidos,
                    'correo' => $usuario->correo,
                    'telefono' => $usuario->telefono,
                    'estado' => $usuario->estado,
                    'fecha_registro' => $usuario->fecha_registro
                ];
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    
    echo json_encode($arr_Respuesta);
}