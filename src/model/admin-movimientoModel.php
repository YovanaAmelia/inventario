<?php
require_once "../library/conexion.php";

class MovimientoModel
{

    private $conexion;
    function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion = $this->conexion->connect();
    }
    public function registrarMovimiento($ambiente_origen, $ambiente_destino, $id_usuario, $descripcion, $institucion)
    {
        $sql = $this->conexion->query("INSERT INTO movimientos (id_ambiente_origen,id_ambiente_destino, id_usuario_registro, descripcion, id_ies) VALUES ('$ambiente_origen','$ambiente_destino','$id_usuario','$descripcion','$institucion')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        return $sql;
    }
    public function registrarDetalleMovimiento($id_movimiento, $id_bien)
    {
        $sql = $this->conexion->query("INSERT INTO detalle_movimiento (id_movimiento,id_bien) VALUES ('$id_movimiento','$id_bien')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        
        return $sql;
    }

    public function buscarMovimientoById($id)
    {
        $sql = $this->conexion->query("SELECT * FROM movimientos WHERE id='$id'");
        $sql = $sql->fetch_object();
        return $sql;
    }

    public function buscarUsuarioByNom($nomap)
    {
        $sql = $this->conexion->query("SELECT * FROM ambientes_institucion WHERE apellidos_nombres='$nomap'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarDetalle_MovimientoByMovimiento($movimiento)
    {
        $arrRespuesta = array();
        $sql = $this->conexion->query("SELECT * FROM detalle_movimiento WHERE id_movimiento='$movimiento'");
        while ($objeto = $sql->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }

    public function buscarMovimiento_tabla_filtro($busqueda_tabla_amb_origen, $busqueda_tabla_amb_destino, $busqueda_fecha_desde, $busqueda_fecha_hasta, $ies)
    {
        //condicionales para busqueda
        $condicion = " id_ies = '$ies' ";
        if ($busqueda_tabla_amb_origen > 0) {
            $condicion .= " AND id_ambiente_origen ='$busqueda_tabla_amb_origen'";
        }
        if ($busqueda_tabla_amb_destino > 0) {
            $condicion .= " AND id_ambiente_destino ='$busqueda_tabla_amb_destino'";
        }
        if ($busqueda_fecha_desde != '' && $busqueda_fecha_hasta != '') {
            $condicion .= " AND fecha_registro >= '$busqueda_fecha_desde' AND fecha_registro <= '$busqueda_fecha_hasta'";
        }
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM movimientos WHERE $condicion ORDER BY fecha_registro ASC");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarMovimiento_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_amb_origen, $busqueda_tabla_amb_destino, $busqueda_fecha_desde, $busqueda_fecha_hasta, $ies)
    {
        //condicionales para busqueda
        $condicion = " id_ies = '$ies' ";
        if ($busqueda_tabla_amb_origen > 0) {
            $condicion .= " AND id_ambiente_origen ='$busqueda_tabla_amb_origen'";
        }
        if ($busqueda_tabla_amb_destino > 0) {
            $condicion .= " AND id_ambiente_destino ='$busqueda_tabla_amb_destino'";
        }
        if ($busqueda_fecha_desde != '' && $busqueda_fecha_hasta != '') {
            $condicion .= " AND fecha_registro BETWEEN '$busqueda_fecha_desde' AND '$busqueda_fecha_hasta'";
        }
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM movimientos WHERE $condicion ORDER BY fecha_registro LIMIT $iniciar, $cantidad_mostrar");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    // Método existente corregido para el filtro completo
public function buscarMovimientoConDetalles_tabla_filtro($busqueda_tabla_amb_origen, $busqueda_tabla_amb_destino, $busqueda_fecha_desde, $busqueda_fecha_hasta, $ies)
{
    $condicion = " m.id_ies = '$ies' ";
    if ($busqueda_tabla_amb_origen > 0) {
        $condicion .= " AND m.id_ambiente_origen ='$busqueda_tabla_amb_origen'";
    }
    if ($busqueda_tabla_amb_destino > 0) {
        $condicion .= " AND m.id_ambiente_destino ='$busqueda_tabla_amb_destino'";
    }
    if ($busqueda_fecha_desde != '' && $busqueda_fecha_hasta != '') {
        $condicion .= " AND m.fecha_registro >= '$busqueda_fecha_desde' AND m.fecha_registro <= '$busqueda_fecha_hasta'";
    }
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            m.*, 
            ao.detalle AS ambiente_origen,
            ad.detalle AS ambiente_destino,
            u.nombres_apellidos AS usuario_registro,
            ins.nombre AS institucion,
            GROUP_CONCAT(DISTINCT b.denominacion SEPARATOR ', ') AS bienes_involucrados
        FROM movimientos m
        LEFT JOIN ambientes_institucion ao ON m.id_ambiente_origen = ao.id
        LEFT JOIN ambientes_institucion ad ON m.id_ambiente_destino = ad.id
        LEFT JOIN usuarios u ON m.id_usuario_registro = u.id
        LEFT JOIN institucion ins ON m.id_ies = ins.id
        LEFT JOIN detalle_movimiento dm ON m.id = dm.id_movimiento
        LEFT JOIN bienes b ON dm.id_bien = b.id
        WHERE $condicion
        GROUP BY m.id
        ORDER BY m.fecha_registro ASC
    ";
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}

// Nuevo método para paginación con detalles completos
public function buscarMovimientoConDetalles_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_amb_origen, $busqueda_tabla_amb_destino, $busqueda_fecha_desde, $busqueda_fecha_hasta, $ies)
{
    $condicion = " m.id_ies = '$ies' ";
    if ($busqueda_tabla_amb_origen > 0) {
        $condicion .= " AND m.id_ambiente_origen ='$busqueda_tabla_amb_origen'";
    }
    if ($busqueda_tabla_amb_destino > 0) {
        $condicion .= " AND m.id_ambiente_destino ='$busqueda_tabla_amb_destino'";
    }
    if ($busqueda_fecha_desde != '' && $busqueda_fecha_hasta != '') {
        $condicion .= " AND m.fecha_registro >= '$busqueda_fecha_desde' AND m.fecha_registro <= '$busqueda_fecha_hasta'";
    }
    
    $inicio = ($pagina - 1) * $cantidad_mostrar;
    
    $arrRespuesta = array();
    $query = "
        SELECT 
            m.*, 
            ao.detalle AS ambiente_origen,
            ad.detalle AS ambiente_destino,
            u.nombres_apellidos AS usuario_registro,
            ins.nombre AS institucion,
            GROUP_CONCAT(DISTINCT b.denominacion SEPARATOR ', ') AS bienes_involucrados
        FROM movimientos m
        LEFT JOIN ambientes_institucion ao ON m.id_ambiente_origen = ao.id
        LEFT JOIN ambientes_institucion ad ON m.id_ambiente_destino = ad.id
        LEFT JOIN usuarios u ON m.id_usuario_registro = u.id
        LEFT JOIN institucion ins ON m.id_ies = ins.id
        LEFT JOIN detalle_movimiento dm ON m.id = dm.id_movimiento
        LEFT JOIN bienes b ON dm.id_bien = b.id
        WHERE $condicion
        GROUP BY m.id
        ORDER BY m.fecha_registro ASC
        LIMIT $inicio, $cantidad_mostrar
    ";
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}
    public function listarTodosLosMovimientos()
    {
        $arrRespuesta = array();
        $query = "
        SELECT 
            m.*, 
            ins.nombre AS nombre_institucion,
            ins.cod_modular AS cod_modular_institucion,
            ins.ruc AS ruc_institucion
        FROM movimientos m
        LEFT JOIN institucion ins ON m.id_ies = ins.id
        ORDER BY m.fecha_registro ASC
    ";
        $respuesta = $this->conexion->query($query);
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
}