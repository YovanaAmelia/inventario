<?php
require_once "../library/conexion.php";

class AmbienteModel
{

    private $conexion;
    function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion = $this->conexion->connect();
    }
    public function registrarAmbiente($institucion, $encargado, $codigo, $detalle, $otros_detalle)
    {
        $sql = $this->conexion->query("INSERT INTO ambientes_institucion (id_ies,encargado,codigo, detalle, otros_detalle) VALUES ('$institucion','$encargado','$codigo','$detalle','$otros_detalle')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        return $sql;
    }
    public function actualizarAmbiente($id, $id_ies, $encargado, $codigo, $detalle, $otros_detalle)
    {
        $sql = $this->conexion->query("UPDATE ambientes_institucion SET id_ies='$id_ies', encargado='$encargado', codigo='$codigo',detalle='$detalle',otros_detalle='$otros_detalle' WHERE id='$id'");
        return $sql;
    }
    public function buscarAmbienteById($id)
    {
        $sql = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE id='$id'");
        $sql = $sql->fetch_object();
        return $sql;
    }

    public function buscarUsuarioByNom($nomap)
    {
        $sql = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE apellidos_nombres='$nomap'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarAmbienteByInstitucion($institucion)
    {
        $arrRespuesta = array();
        $sql = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE id_ies='$institucion'");
        while ($objeto = $sql->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarAmbienteByCpdigoInstitucion($codigo, $institucion)
    {
        $sql = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE codigo='$codigo' AND id_ies='$institucion'");
        $sql = $sql->fetch_object();
        return $sql;
    }

    public function buscarAmbientesOrderByApellidosNombres_tabla_filtro($busqueda_tabla_codigo, $busqueda_tabla_ambiente, $ies)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " codigo LIKE '$busqueda_tabla_codigo%' AND detalle LIKE '$busqueda_tabla_ambiente%' AND id_ies = '$ies'";
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE $condicion ORDER BY detalle");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarAmbientesOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_codigo, $busqueda_tabla_ambiente, $ies)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " codigo LIKE '$busqueda_tabla_codigo%' AND detalle LIKE '$busqueda_tabla_ambiente%' AND id_ies = '$ies'";
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE $condicion ORDER BY detalle LIMIT $iniciar, $cantidad_mostrar");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    // Método para el filtro completo (Excel y otros reportes)
public function buscarAmbientesConDetalles_tabla_filtro($busqueda_codigo, $busqueda_detalle, $busqueda_encargado, $ies)
{
    $condicion = " a.id_ies = $ies ";
    
    if (!empty($busqueda_codigo)) {
        $condicion .= " AND a.codigo LIKE '%$busqueda_codigo%'";
    }
    if (!empty($busqueda_detalle)) {
        $condicion .= " AND a.detalle LIKE '%$busqueda_detalle%'";
    }
    if (!empty($busqueda_encargado)) {
        $condicion .= " AND a.encargado LIKE '%$busqueda_encargado%'";
    }
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            a.*,
            COUNT(b.id) AS total_bienes,
            COALESCE(SUM(CASE WHEN b.estado = 1 THEN b.valor ELSE 0 END), 0) AS valor_total_bienes
        FROM ambientes_institucion a
        LEFT JOIN bienes b ON a.id = b.id_ambiente AND b.estado = 1
        WHERE $condicion
        GROUP BY a.id
        ORDER BY a.codigo ASC
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

// Método para paginación con detalles completos
public function buscarAmbientesConDetalles_tabla($pagina, $cantidad_mostrar, $busqueda_codigo, $busqueda_detalle, $busqueda_encargado, $ies)
{
    $condicion = " a.id_ies = $ies ";
    
    if (!empty($busqueda_codigo)) {
        $condicion .= " AND a.codigo LIKE '%$busqueda_codigo%'";
    }
    if (!empty($busqueda_detalle)) {
        $condicion .= " AND a.detalle LIKE '%$busqueda_detalle%'";
    }
    if (!empty($busqueda_encargado)) {
        $condicion .= " AND a.encargado LIKE '%$busqueda_encargado%'";
    }
    
    $inicio = ($pagina - 1) * $cantidad_mostrar;
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            a.*,
            COUNT(b.id) AS total_bienes,
            COALESCE(SUM(CASE WHEN b.estado = 1 THEN b.valor ELSE 0 END), 0) AS valor_total_bienes,
            COUNT(CASE WHEN b.estado = 1 THEN b.id END) AS bienes_activos,
            COUNT(CASE WHEN b.estado = 0 THEN b.id END) AS bienes_inactivos
        FROM ambientes_institucion a
        LEFT JOIN bienes b ON a.id = b.id_ambiente
        WHERE $condicion
        GROUP BY a.id
        ORDER BY a.codigo ASC
        LIMIT $inicio, $cantidad_mostrar
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

// Método para contar total de ambientes con filtros (para paginación)
public function contarAmbientesConFiltros($busqueda_codigo, $busqueda_detalle, $busqueda_encargado, $ies)
{
    $condicion = " id_ies = $ies ";
    
    if (!empty($busqueda_codigo)) {
        $condicion .= " AND codigo LIKE '%$busqueda_codigo%'";
    }
    if (!empty($busqueda_detalle)) {
        $condicion .= " AND detalle LIKE '%$busqueda_detalle%'";
    }
    if (!empty($busqueda_encargado)) {
        $condicion .= " AND encargado LIKE '%$busqueda_encargado%'";
    }
    
    $query = "SELECT COUNT(*) as total FROM ambientes_institucion WHERE $condicion";
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto->total;
}

// Método para obtener estadísticas de ambientes
public function obtenerEstadisticasAmbientes($ies)
{
    $arrRespuesta = array();
    
    $query = "
        SELECT 
            COUNT(DISTINCT a.id) as total_ambientes,
            COUNT(b.id) as total_bienes_en_ambientes,
            COALESCE(SUM(CASE WHEN b.estado = 1 THEN b.valor ELSE 0 END), 0) as valor_total_bienes,
            COUNT(DISTINCT CASE WHEN b.id IS NOT NULL THEN a.id END) as ambientes_con_bienes,
            COUNT(DISTINCT CASE WHEN b.id IS NULL THEN a.id END) as ambientes_sin_bienes
        FROM ambientes_institucion a
        LEFT JOIN bienes b ON a.id = b.id_ambiente
        WHERE a.id_ies = $ies
    ";
    
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto;
}

// Método para obtener ambientes con más bienes
public function obtenerAmbientesConMasBienes($ies, $limite = 5)
{
    $arrRespuesta = array();
    
    $query = "
        SELECT 
            a.id,
            a.codigo,
            a.detalle,
            a.encargado,
            COUNT(b.id) as total_bienes,
            COALESCE(SUM(CASE WHEN b.estado = 1 THEN b.valor ELSE 0 END), 0) as valor_total_bienes
        FROM ambientes_institucion a
        LEFT JOIN bienes b ON a.id = b.id_ambiente AND b.estado = 1
        WHERE a.id_ies = $ies
        GROUP BY a.id
        HAVING total_bienes > 0
        ORDER BY total_bienes DESC, valor_total_bienes DESC
        LIMIT $limite
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    
    return $arrRespuesta;
}

// Método para obtener bienes de un ambiente específico
public function obtenerBienesDeAmbiente($id_ambiente)
{
    $arrRespuesta = array();
    
    $query = "
        SELECT 
            b.*,
            i.detalle as detalle_ingreso,
            u.nombres_apellidos as usuario_registro_nombre
        FROM bienes b
        LEFT JOIN ingreso_bienes i ON b.id_ingreso_bienes = i.id
        LEFT JOIN usuarios u ON b.usuario_registro = u.id
        WHERE b.id_ambiente = $id_ambiente
        ORDER BY b.fecha_registro DESC
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    
    return $arrRespuesta;
}

// Método para validar si un ambiente puede ser eliminado
public function puedeEliminarAmbiente($id_ambiente)
{
    $query = "SELECT COUNT(*) as total_bienes FROM bienes WHERE id_ambiente = $id_ambiente";
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto->total_bienes == 0;
}

// Método para obtener resumen de un ambiente
public function obtenerResumenAmbiente($id_ambiente)
{
    $query = "
        SELECT 
            a.*,
            COUNT(b.id) as total_bienes,
            COUNT(CASE WHEN b.estado = 1 THEN b.id END) as bienes_activos,
            COUNT(CASE WHEN b.estado = 0 THEN b.id END) as bienes_inactivos,
            COALESCE(SUM(CASE WHEN b.estado = 1 THEN b.valor ELSE 0 END), 0) as valor_total_bienes,
            i.nombre as institucion_nombre
        FROM ambientes_institucion a
        LEFT JOIN bienes b ON a.id = b.id_ambiente
        LEFT JOIN institucion i ON a.id_ies = i.id
        WHERE a.id = $id_ambiente
        GROUP BY a.id
    ";
    
    $respuesta = $this->conexion->query($query);
    $objeto = $respuesta->fetch_object();
    
    return $objeto;
}
public function listarTodosLosAmbientes()
{
    $arrRespuesta = array();
    $query = "
        SELECT
            a.id,
            a.id_ies,
            a.encargado,
            a.codigo,
            a.detalle,
            a.otros_detalle,
            
            i.id AS institucion_id,
            i.nombre AS institucion_nombre,
            i.cod_modular AS institucion_cod_modular,
            i.ruc AS institucion_ruc
        FROM ambientes_institucion a
        LEFT JOIN institucion i ON a.id_ies = i.id
        ORDER BY i.nombre ASC, a.codigo ASC;
    ";
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}
}
