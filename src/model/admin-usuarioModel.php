<?php
require_once "../library/conexion.php";

class UsuarioModel
{

    private $conexion;
    function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion = $this->conexion->connect();
    }
    public function registrarUsuario($dni, $apellidos_nombres, $correo, $telefono, $password)
    {
        $password_secure = password_hash($password, PASSWORD_DEFAULT); // Hash de la contraseña
        $sql = $this->conexion->query("INSERT INTO usuarios (dni, nombres_apellidos, correo, telefono, password) VALUES ('$dni','$apellidos_nombres','$correo','$telefono', '$password_secure')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        return $sql;
    }
    public function actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado)
    {
        $sql = $this->conexion->query("UPDATE usuarios SET dni='$dni',nombres_apellidos='$nombres_apellidos',correo='$correo',telefono='$telefono',estado ='$estado' WHERE id='$id'");
        return $sql;
    }
    public function actualizarPassword($id, $password)
    {
        $sql = $this->conexion->query("UPDATE usuarios SET password ='$password' WHERE id='$id'");
        return $sql;
    }
    public function updateResetPassword($id,$token,$estado){
        $sql = $this->conexion->query("UPDATE usuarios SET token_password ='$token', reset_password='$estado' WHERE id='$id'");
        return $sql;
    }
    public function buscarUsuarioById($id)
    {
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE id='$id'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarUsuarioByDni($dni)
    {
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE dni='$dni'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarUsuarioByNomAp($nomap)
    {
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE nombres_apellidos='$nomap'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarUsuarioByApellidosNombres_like($dato)
    {
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE nombres_apellidos LIKE '%$dato%'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarUsuarioByDniCorreo($dni, $correo)
    {
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE dni='$dni' AND correo='$correo'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarUsuariosOrdenados()
    {
        $arrRespuesta = array();
        $sql = $this->conexion->query("SELECT * FROM usuarios WHERE estado='1' ORDER BY nombres_apellidos ASC ");
        while ($objeto = $sql->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
   
    public function buscarUsuariosOrderByApellidosNombres_tabla_filtro($busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " dni LIKE '$busqueda_tabla_dni%' AND nombres_apellidos LIKE '$busqueda_tabla_nomap%'";
        if ($busqueda_tabla_estado != '') {
            $condicion .= " AND estado = '$busqueda_tabla_estado'";
        }
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM usuarios WHERE $condicion ORDER BY nombres_apellidos");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarUsuariosOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " dni LIKE '$busqueda_tabla_dni%' AND nombres_apellidos LIKE '$busqueda_tabla_nomap%'";
        if ($busqueda_tabla_estado != '') {
            $condicion .= " AND estado = '$busqueda_tabla_estado'";
        }
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM usuarios WHERE $condicion ORDER BY nombres_apellidos LIMIT $iniciar, $cantidad_mostrar");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }

    // Método para el filtro completo (Excel y otros reportes)
public function buscarUsuariosConDetalles_tabla_filtro($busqueda_nombre, $busqueda_dni, $busqueda_estado)
{
    $condicion = " 1=1 ";
    
    if (!empty($busqueda_nombre)) {
        $condicion .= " AND u.nombres_apellidos LIKE '%$busqueda_nombre%'";
    }
    if (!empty($busqueda_dni)) {
        $condicion .= " AND u.dni LIKE '%$busqueda_dni%'";
    }
    if ($busqueda_estado !== '' && $busqueda_estado != 'todos') {
        $condicion .= " AND u.estado = '$busqueda_estado'";
    }
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            u.*,
            MAX(s.fecha_hora_inicio) AS ultimo_acceso
        FROM usuarios u
        LEFT JOIN sesiones s ON u.id = s.id_usuario
        WHERE $condicion
        GROUP BY u.id
        ORDER BY u.fecha_registro DESC
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

// Método para paginación con detalles completos
public function buscarUsuariosConDetalles_tabla($pagina, $cantidad_mostrar, $busqueda_nombre, $busqueda_dni, $busqueda_estado)
{
    $condicion = " 1=1 ";
    
    if (!empty($busqueda_nombre)) {
        $condicion .= " AND u.nombres_apellidos LIKE '%$busqueda_nombre%'";
    }
    if (!empty($busqueda_dni)) {
        $condicion .= " AND u.dni LIKE '%$busqueda_dni%'";
    }
    if ($busqueda_estado !== '' && $busqueda_estado != 'todos') {
        $condicion .= " AND u.estado = '$busqueda_estado'";
    }
    
    $inicio = ($pagina - 1) * $cantidad_mostrar;
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            u.*,
            MAX(s.fecha_hora_inicio) AS ultimo_acceso,
            COUNT(s.id) AS total_sesiones
        FROM usuarios u
        LEFT JOIN sesiones s ON u.id = s.id_usuario
        WHERE $condicion
        GROUP BY u.id
        ORDER BY u.fecha_registro DESC
        LIMIT $inicio, $cantidad_mostrar
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

// Método para contar total de usuarios con filtros (para paginación)
public function contarUsuariosConFiltros($busqueda_nombre, $busqueda_dni, $busqueda_estado)
{
    $condicion = " 1=1 ";
    
    if (!empty($busqueda_nombre)) {
        $condicion .= " AND nombres_apellidos LIKE '%$busqueda_nombre%'";
    }
    if (!empty($busqueda_dni)) {
        $condicion .= " AND dni LIKE '%$busqueda_dni%'";
    }
    if ($busqueda_estado !== '' && $busqueda_estado != 'todos') {
        $condicion .= " AND estado = '$busqueda_estado'";
    }
    
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE $condicion";
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto->total;
}

// Método para obtener estadísticas de usuarios
public function obtenerEstadisticasUsuarios()
{
    $arrRespuesta = array();
    
    $query = "
        SELECT 
            COUNT(*) as total_usuarios,
            SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as usuarios_activos,
            SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as usuarios_inactivos,
            COUNT(DISTINCT DATE(fecha_registro)) as dias_con_registros
        FROM usuarios
    ";
    
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto;
}

// Método para obtener usuarios más activos (con más sesiones)
public function obtenerUsuariosMasActivos($limite = 5)
{
    $arrRespuesta = array();
    
    $query = "
        SELECT 
            u.id,
            u.nombres_apellidos,
            u.dni,
            COUNT(s.id) as total_sesiones,
            MAX(s.fecha_hora_inicio) as ultimo_acceso
        FROM usuarios u
        LEFT JOIN sesiones s ON u.id = s.id_usuario
        WHERE u.estado = 1
        GROUP BY u.id
        ORDER BY total_sesiones DESC, ultimo_acceso DESC
        LIMIT $limite
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    
    return $arrRespuesta;
}
public function listarTodosLosUsuarios()
{
    $arrRespuesta = array();
    $query = "
        SELECT
            u.id,
            u.dni,
            u.nombres_apellidos,
            u.correo,
            u.telefono,
            u.estado,
            u.fecha_registro
        FROM usuarios u
        ORDER BY u.nombres_apellidos ASC;
    ";
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

}