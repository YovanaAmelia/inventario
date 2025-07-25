
<?php
session_start();
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

if ($tipo == "listar_ambientes_ordenados_tabla_e") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $ies = $_POST['ies'] ?? 1;
        $pagina = $_POST['pagina'] ?? 1;
        $cantidad_mostrar = $_POST['cantidad_mostrar'] ?? 10;
        $busqueda_codigo = $_POST['busqueda_codigo'] ?? '';
        $busqueda_detalle = $_POST['busqueda_detalle'] ?? '';
        $busqueda_encargado = $_POST['busqueda_encargado'] ?? '';
        
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        
        // Usar el método para obtener ambientes con filtros
        $arr_Ambientes = $objAmbiente->buscarAmbientesConDetalles_tabla_filtro(
            $busqueda_codigo, 
            $busqueda_detalle, 
            $busqueda_encargado,
            $ies
        );
        
        $arr_contenido = [];
        
        if (!empty($arr_Ambientes)) {
            for ($i = 0; $i < count($arr_Ambientes); $i++) {
                $arr_contenido[$i] = (object) [];
                $arr_contenido[$i]->id = $arr_Ambientes[$i]->id;
                $arr_contenido[$i]->codigo = $arr_Ambientes[$i]->codigo;
                $arr_contenido[$i]->detalle = $arr_Ambientes[$i]->detalle;
                $arr_contenido[$i]->encargado = $arr_Ambientes[$i]->encargado;
                $arr_contenido[$i]->otros_detalle = $arr_Ambientes[$i]->otros_detalle;
                
                // Incluir información de bienes obtenida del JOIN
                $arr_contenido[$i]->total_bienes = $arr_Ambientes[$i]->total_bienes ?? 0;
                $arr_contenido[$i]->valor_total_bienes = $arr_Ambientes[$i]->valor_total_bienes ?? 0;
                
                // Badge para mostrar cantidad de bienes
                $badgeClass = ($arr_Ambientes[$i]->total_bienes > 0) ? 'badge-success' : 'badge-secondary';
                $textoB = ($arr_Ambientes[$i]->total_bienes > 0) ? $arr_Ambientes[$i]->total_bienes . ' bienes' : 'Sin bienes';
                
                // Formatear valor total
                $valorFormateado = 'S/. ' . number_format($arr_Ambientes[$i]->valor_total_bienes ?? 0, 2);
                
                // Opciones para la tabla (botones de acción)
                $opciones = '<div class="btn-group" role="group">';
                $opciones .= '<button type="button" title="Ver Detalle" class="btn btn-info btn-sm waves-effect waves-light" data-toggle="modal" data-target=".modal_detalle' . $arr_Ambientes[$i]->id . '"><i class="fa fa-eye"></i></button>';
                $opciones .= '<button type="button" title="Editar" class="btn btn-warning btn-sm waves-effect waves-light" onclick="editarAmbiente(' . $arr_Ambientes[$i]->id . ')"><i class="fa fa-edit"></i></button>';
                $opciones .= '<button type="button" title="Ver Bienes" class="btn btn-success btn-sm waves-effect waves-light" onclick="verBienesAmbiente(' . $arr_Ambientes[$i]->id . ')"><i class="fa fa-list"></i></button>';
                
                // Solo mostrar botón de eliminar si no tiene bienes asignados
                if (($arr_Ambientes[$i]->total_bienes ?? 0) == 0) {
                    $opciones .= '<button type="button" title="Eliminar" class="btn btn-danger btn-sm waves-effect waves-light" onclick="eliminarAmbiente(' . $arr_Ambientes[$i]->id . ')"><i class="fa fa-trash"></i></button>';
                }
                $opciones .= '</div>';
                
                $arr_contenido[$i]->options = $opciones;
                $arr_contenido[$i]->bienes_badge = '<span class="badge ' . $badgeClass . '">' . $textoB . '</span>';
                $arr_contenido[$i]->valor_formateado = $valorFormateado;
            }
            $arr_Respuesta['total'] = count($arr_Ambientes);
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "listar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $id_ies = $_POST['ies'];
        //print_r($_POST);
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $arr_Ambiente = $objAmbiente->buscarAmbienteByInstitucion($id_ies);
        $arr_contenido = [];
        if (!empty($arr_Ambiente)) {
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Ambiente); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Ambiente[$i]->id;
                $arr_contenido[$i]->detalle = $arr_Ambiente[$i]->detalle;
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "listar_ambientes_ordenados_tabla") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $ies = $_POST['ies'];
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_codigo = $_POST['busqueda_tabla_codigo'];
        $busqueda_tabla_ambiente = $_POST['busqueda_tabla_ambiente'];
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $busqueda_filtro = $objAmbiente->buscarAmbientesOrderByApellidosNombres_tabla_filtro($busqueda_tabla_codigo, $busqueda_tabla_ambiente, $ies);
        $arr_Ambiente = $objAmbiente->buscarAmbientesOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_codigo, $busqueda_tabla_ambiente, $ies);
        $arr_contenido = [];
        if (!empty($arr_Ambiente)) {
            $arr_Institucion = $objInstitucion->buscarInstitucionOrdenado();
            $arr_Respuesta['instituciones'] = $arr_Institucion;
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Ambiente); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Ambiente[$i]->id;
                $arr_contenido[$i]->institucion = $arr_Ambiente[$i]->id_ies;
                $arr_contenido[$i]->encargado = $arr_Ambiente[$i]->encargado;
                $arr_contenido[$i]->codigo = $arr_Ambiente[$i]->codigo;
                $arr_contenido[$i]->detalle = $arr_Ambiente[$i]->detalle;
                $arr_contenido[$i]->otros_detalle = $arr_Ambiente[$i]->otros_detalle;
                $opciones = '<button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Ambiente[$i]->id . '"><i class="fa fa-edit"></i></button>';
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
            $institucion = $_POST['ies'];
            $encargado = $_POST['encargado'];
            $codigo = $_POST['codigo'];
            $detalle = $_POST['detalle'];
            $otros_detalle = $_POST['otros_detalle'];
            if ($institucion == "" || $codigo == "" || $detalle == "" || $otros_detalle == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objAmbiente->buscarAmbienteByCpdigoInstitucion($codigo, $institucion);
                if ($arr_Usuario) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Usuario ya se encuentra registrado');
                } else {
                    $id_usuario = $objAmbiente->registrarAmbiente($institucion, $encargado, $codigo, $detalle, $otros_detalle);
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
            $id_ies = $_POST['id_ies'];
            $encargado = $_POST['encargado'];
            $codigo = $_POST['codigo'];
            $detalle = $_POST['detalle'];
            $otros_detalle = $_POST['otros_detalle'];

            if ($id == "" || $id_ies == "" || $codigo == "" || $detalle == "" || $otros_detalle == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Ambiente = $objAmbiente->buscarAmbienteByCpdigoInstitucion($codigo, $id_ies);
                if ($arr_Ambiente) {
                    if ($arr_Ambiente->id == $id) {
                        $consulta = $objAmbiente->actualizarAmbiente($id, $id_ies, $encargado, $codigo, $detalle, $otros_detalle);
                        if ($consulta) {
                            $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                        } else {
                            $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                        }
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'dni ya esta registrado');
                    }
                } else {
                    $consulta = $objAmbiente->actualizarAmbiente($id, $id_ies, $encargado, $codigo, $detalle, $otros_detalle);
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
if ($tipo == "datos_registro") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //repuesta
        $arr_Instirucion = $objInstitucion->buscarInstitucionOrdenado();
        $arr_Respuesta['instituciones'] = $arr_Instirucion;
        $arr_Respuesta['status'] = true;
        $arr_Respuesta['msg'] = "Datos encontrados";
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "listar_todos_ambientes") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $arr_Respuesta = array('status' => false, 'contenido' => []);
        $arr_Ambientes = $objAmbiente->listarTodosLosAmbientes(); // Asegúrate de que este método exista en $objAmbiente
        
        $arr_contenido = [];
        if (!empty($arr_Ambientes)) {
            foreach ($arr_Ambientes as $ambiente) {
                // Obtener información de la institución asociada
                $institucion = isset($ambiente->id_ies) ? $objInstitucion->buscarInstitucionById($ambiente->id_ies) : null;
                
                $arr_contenido[] = [
                    'id' => $ambiente->id,
                    'codigo' => $ambiente->codigo,
                    'detalle' => $ambiente->detalle,
                    'encargado' => $ambiente->encargado,
                    'otros_detalle' => $ambiente->otros_detalle,
                    'institucion' => $institucion ? [
                        'id' => $institucion->id,
                        'nombre' => $institucion->nombre,
                        'cod_modular' => $institucion->cod_modular,
                        'ruc' => $institucion->ruc
                    ] : null
                ];
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    
    echo json_encode($arr_Respuesta);
}