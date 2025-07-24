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

if ($tipo == "listar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $arr_Institucion = $objInstitucion->buscarInstitucionOrdenado();
        $arr_contenido = [];
        if (!empty($arr_Institucion)) {
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Institucion); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Institucion[$i]->id;
                $arr_contenido[$i]->nombre = $arr_Institucion[$i]->nombre;
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "listar_instituciones") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_codigo = $_POST['busqueda_tabla_codigo'];
        $busqueda_tabla_ruc = $_POST['busqueda_tabla_ruc'];
        $busqueda_tabla_insti = $_POST['busqueda_tabla_insti'];
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $busqueda_filtro = $objInstitucion->buscarInstitucionOrderByApellidosNombres_tabla_filtro($busqueda_tabla_codigo, $busqueda_tabla_ruc, $busqueda_tabla_insti);
        $arr_Institucion = $objInstitucion->buscarInstitucionOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_codigo, $busqueda_tabla_ruc, $busqueda_tabla_insti);
        
        $arr_contenido = [];
        if (!empty($arr_Institucion)) {
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Institucion); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Institucion[$i]->id;
                $arr_contenido[$i]->beneficiario = $arr_Institucion[$i]->beneficiario;
                $arr_contenido[$i]->cod_modular = $arr_Institucion[$i]->cod_modular;
                $arr_contenido[$i]->ruc = $arr_Institucion[$i]->ruc;
                $arr_contenido[$i]->nombre = $arr_Institucion[$i]->nombre;
                $opciones = '<button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Institucion[$i]->id . '"><i class="fa fa-edit"></i></button>';
                $arr_contenido[$i]->options = $opciones;
            }
            $arr_Respuesta['total'] = count($busqueda_filtro);
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
        $arr_Usuario = $objUsuario->buscarUsuariosOrdenados();
        $arr_contenido_usuarios = [];
        if (!empty($arr_Usuario)) {
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                // definimos el elemento como objeto
                $arr_contenido_usuarios[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido_usuarios[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido_usuarios[$i]->nombre = $arr_Usuario[$i]->nombres_apellidos;
            }
            $arr_Respuesta['usuarios'] = $arr_contenido_usuarios;
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "listar_instituciones_ordenados_tabla") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_nombre = $_POST['busqueda_tabla_nombre'];
        $busqueda_tabla_codigo = $_POST['busqueda_tabla_codigo'];
        $busqueda_tabla_ruc = $_POST['busqueda_tabla_ruc'];
        
        // respuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        
        $busqueda_filtro = $objInstitucion->buscarInstituciones_tabla_filtro($busqueda_tabla_nombre, $busqueda_tabla_codigo, $busqueda_tabla_ruc);
        $arr_Instituciones = $objInstitucion->buscarInstituciones_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_nombre, $busqueda_tabla_codigo, $busqueda_tabla_ruc);
        
        $arr_contenido = [];
        
        if (!empty($arr_Instituciones)) {
            // recorremos el array para agregar la información completa
            for ($i = 0; $i < count($arr_Instituciones); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                
                // agregamos solo la información que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Instituciones[$i]->id;
                $arr_contenido[$i]->cod_modular = $arr_Instituciones[$i]->cod_modular;
                $arr_contenido[$i]->ruc = $arr_Instituciones[$i]->ruc;
                $arr_contenido[$i]->nombre = $arr_Instituciones[$i]->nombre;
                $arr_contenido[$i]->beneficiario = $arr_Instituciones[$i]->beneficiario;
                $arr_contenido[$i]->nombre_beneficiario = $arr_Instituciones[$i]->nombre_beneficiario ?? '';
                $arr_contenido[$i]->correo_beneficiario = $arr_Instituciones[$i]->correo_beneficiario ?? '';
                $arr_contenido[$i]->telefono_beneficiario = $arr_Instituciones[$i]->telefono_beneficiario ?? '';
                $arr_contenido[$i]->total_ambientes = $arr_Instituciones[$i]->total_ambientes ?? 0;
                $arr_contenido[$i]->total_bienes = $arr_Instituciones[$i]->total_bienes ?? 0;
                
                $opciones = '<button type="button" title="Ver Detalles" class="btn btn-info waves-effect waves-light" data-toggle="modal" data-target=".modal_ver' . $arr_Instituciones[$i]->id . '"><i class="fa fa-eye"></i></button>';
                $opciones .= ' <button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Instituciones[$i]->id . '"><i class="fa fa-edit"></i></button>';
                $opciones .= ' <button type="button" title="Reporte de Bienes" class="btn btn-success waves-effect waves-light" onclick="generarReporteBienes(' . $arr_Instituciones[$i]->id . ')"><i class="fa fa-file-excel"></i></button>';
                
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
            $beneficiario = $_POST['beneficiario'];
            $cod_modular = $_POST['cod_modular'];
            $ruc = $_POST['ruc'];
            $nombre = $_POST['nombre'];
            if ($cod_modular == "" || $ruc == "" || $nombre == "" || $beneficiario == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Institucion = $objInstitucion->buscarInstitucionByCodigo($ruc);
                if ($arr_Institucion) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Institución ya se encuentra registrado');
                } else {
                    $id_institucion = $objInstitucion->registrarInstitucion($beneficiario,$cod_modular, $ruc, $nombre);
                    if ($id_institucion > 0) {
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Registro Exitoso');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al registrar Institución');
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
            $beneficiario = $_POST['beneficiario'];
            $cod_modular = $_POST['cod_modular'];
            $ruc = $_POST['ruc'];
            $nombre = $_POST['nombre'];

            if ($id == "" || $cod_modular == "" || $ruc == "" || $nombre == "" || $beneficiario == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Institucion = $objInstitucion->buscarInstitucionByCodigo($cod_modular);
                if ($arr_Institucion) {
                    if ($arr_Institucion->id == $id) {
                        $consulta = $objInstitucion->actualizarInstitucion($id, $beneficiario, $cod_modular, $ruc, $nombre);
                        if ($consulta) {
                            $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                        } else {
                            $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                        }
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'código modular ya esta registrado');
                    }
                } else {
                    $consulta = $objInstitucion->actualizarInstitucion($id,$beneficiario, $cod_modular, $ruc, $nombre);
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
        $arr_Usuario = $objUsuario->buscarUsuariosOrdenados();
        $arr_contenido = [];
        if (!empty($arr_Usuario)) {
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido[$i]->nombre = $arr_Usuario[$i]->nombres_apellidos;
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
        $arr_Respuesta['msg'] = "Datos encontrados";
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "listar_todas_instituciones") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        $arr_Respuesta = array('status' => false, 'contenido' => []);
        $arr_Instituciones = $objInstitucion->listarTodasLasInstituciones(); // Asegúrate de que este método exista en $objInstitucion
        
        $arr_contenido = [];
        if (!empty($arr_Instituciones)) {
            foreach ($arr_Instituciones as $institucion) {
                // Asegúrate de que el objeto $objUsuario esté disponible y tenga los métodos necesarios
                $usuario = isset($institucion->beneficiario) ? $objUsuario->buscarUsuarioById($institucion->beneficiario) : null;
                
                $arr_contenido[] = [
                    'id' => $institucion->id,
                    'cod_modular' => $institucion->cod_modular,
                    'ruc' => $institucion->ruc,
                    'nombre' => $institucion->nombre,
                    'usuario' => $usuario ? [
                        'id' => $usuario->id,
                        'nombres_apellidos' => $usuario->nombres_apellidos,
                        'dni' => $usuario->dni,
                        'correo' => $usuario->correo,
                        'telefono' => $usuario->telefono
                    ] : null
                ];
            }
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    
    echo json_encode($arr_Respuesta);
}