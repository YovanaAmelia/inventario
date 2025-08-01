<?php
require_once "../library/conexion.php";

class BienModel
{

    private $conexion;
    function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion = $this->conexion->connect();
    }

    public function obtenerBienes()
    {
        $query = "SELECT b.*, ai.detalle as ambiente_detalle FROM bienes b JOIN ambientes_institucion ai ON b.id_ambiente = ai.id";
        $result = $this->conexion->query($query);

        $bienes = [];
        while ($row = $result->fetch_assoc()) {
            $bienes[] = $row;
        }

        return $bienes;
    }
    public function registrarBien($ambiente, $cod_patrimonial, $denominacion, $marca, $modelo, $tipo, $color, $serie, $dimensiones, $valor, $situacion, $estado_conservacion, $observaciones, $id_usuario, $id_ingreso)
    {
        $sql = $this->conexion->query("INSERT INTO bienes (id_ingreso_bienes ,id_ambiente,cod_patrimonial, denominacion, marca,modelo,tipo,color,serie,dimensiones,valor,situacion,estado_conservacion,observaciones,usuario_registro ) VALUES ('$id_ingreso','$ambiente', '$cod_patrimonial','$denominacion', '$marca', '$modelo', '$tipo', '$color', '$serie', '$dimensiones', '$valor', '$situacion', '$estado_conservacion', '$observaciones', '$id_usuario')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        return $sql;
    }
    public function actualizarBien($id, $cod_patrimonial, $denominacion, $marca, $modelo, $tipo, $color, $serie, $dimensiones, $valor, $situacion, $estado_conservacion, $observaciones)
    {
        $sql = $this->conexion->query("UPDATE bienes SET cod_patrimonial='$cod_patrimonial',denominacion='$denominacion',marca='$marca',modelo='$modelo',tipo='$tipo',color='$color',serie='$serie',dimensiones='$dimensiones',valor='$valor',situacion='$situacion',estado_conservacion='$estado_conservacion',observaciones='$observaciones' WHERE id='$id'");
        return $sql;
    }
    public function actualizarBien_Ambiente($id, $nuevo_ambiente)
    {
        $sql = $this->conexion->query("UPDATE bienes SET id_ambiente='$nuevo_ambiente'WHERE id='$id'");
        return $sql;
    }
    public function buscarBienById($id)
    {
        $sql = $this->conexion->query("SELECT * FROM bienes WHERE id='$id'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarBienes_filtro($filtro, $ambiente)
    {
        $arrRespuesta = array();
        $sql = $this->conexion->query("SELECT * FROM bienes WHERE (cod_patrimonial LIKE '$filtro%' OR denominacion LIKE '%$filtro%') AND id_ambiente='$ambiente'");
        while ($objeto = $sql->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarBienByCodigoPatrimonial($codigo)
    {
        $sql = $this->conexion->query("SELECT * FROM bienes WHERE cod_patrimonial ='$codigo'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarBienByCpdigoInstitucion($codigo, $institucion)
    {
        $sql = $this->conexion->query("SELECT * FROM bienes WHERE codigo='$codigo' AND id_ies='$institucion'");
        $sql = $sql->fetch_object();
        return $sql;
    }

    public function buscarBienesOrderByDenominacion_tabla_filtro($busqueda_tabla_codigo, $busqueda_tabla_ambiente, $busqueda_tabla_denominacion, $ies)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " cod_patrimonial LIKE '$busqueda_tabla_codigo%' AND denominacion LIKE '$busqueda_tabla_denominacion%'";
        if ($busqueda_tabla_ambiente > 0) {
            $condicion .= " AND id_ambiente='$busqueda_tabla_ambiente'";
        }
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT bienes.id FROM bienes
                INNER JOIN ambientes_institucion ON bienes.id_ambiente = ambientes_institucion.id AND (ambientes_institucion.id_ies = '$ies') WHERE $condicion ORDER BY detalle");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarBienesOrderByDenominacion_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_codigo, $busqueda_tabla_ambiente, $busqueda_tabla_denominacion, $ies)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " cod_patrimonial LIKE '$busqueda_tabla_codigo%' AND denominacion LIKE '$busqueda_tabla_denominacion%'";
        if ($busqueda_tabla_ambiente > 0) {
            $condicion .= " AND id_ambiente='$busqueda_tabla_ambiente'";
        }
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT bienes.id, bienes.id_ambiente,bienes.cod_patrimonial ,bienes.denominacion,bienes.marca,bienes.modelo,bienes.tipo,bienes.color,bienes.serie, bienes.dimensiones, bienes.valor, bienes.situacion, bienes.estado_conservacion,bienes.observaciones FROM bienes
            INNER JOIN ambientes_institucion ON bienes.id_ambiente = ambientes_institucion.id AND (ambientes_institucion.id_ies = '$ies') WHERE $condicion  ORDER BY detalle LIMIT $iniciar, $cantidad_mostrar");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function listarTodosLosBienes()
{
    $arrRespuesta = array();
    $query = "
        SELECT 
            b.id AS bien_id,
            b.cod_patrimonial,
            b.denominacion,
            b.marca,
            b.modelo,
            b.tipo,
            b.color,
            b.serie,
            b.dimensiones,
            b.valor,
            b.situacion,
            b.estado_conservacion,
            b.observaciones,
            b.fecha_registro,
            b.estado AS estado_bien,
            
            ai.id AS ambiente_id,
            ai.codigo AS ambiente_codigo,
            ai.detalle AS ambiente_detalle,
            ai.otros_detalle,
            ai.encargado AS ambiente_encargado,
            
            -- AGREGAR INFORMACIÓN DE LA INSTITUCIÓN
            i.id AS institucion_id,
            i.nombre AS institucion_nombre,
            i.cod_modular AS institucion_cod_modular,
            i.ruc AS institucion_ruc,
            
            u.id AS usuario_id,
            u.nombres_apellidos AS nombre_usuario,
            u.dni AS usuario_dni 
        FROM bienes b 
        LEFT JOIN ambientes_institucion ai ON b.id_ambiente = ai.id 
        LEFT JOIN institucion i ON ai.id_ies = i.id  -- JOIN CON INSTITUCIÓN
        LEFT JOIN usuarios u ON b.usuario_registro = u.id 
        WHERE b.estado = 1  -- Solo bienes activos
        ORDER BY b.fecha_registro ASC;
    ";
    
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}
}
?>