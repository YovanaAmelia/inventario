<?php
require_once "../library/conexion.php";

class InstitucionModel
{

    private $conexion;
    function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion = $this->conexion->connect();
    }
    public function buscarInstituciones_tabla_filtro($busqueda_tabla_nombre, $busqueda_tabla_codigo, $busqueda_tabla_ruc)
    {
        // Condicionales para búsqueda
        $condicion = " 1=1 ";
        if ($busqueda_tabla_nombre != '') {
            $condicion .= " AND i.nombre LIKE '%$busqueda_tabla_nombre%'";
        }
        if ($busqueda_tabla_codigo != '') {
            $condicion .= " AND i.cod_modular LIKE '%$busqueda_tabla_codigo%'";
        }
        if ($busqueda_tabla_ruc != '') {
            $condicion .= " AND i.ruc LIKE '%$busqueda_tabla_ruc%'";
        }
        
        $arrRespuesta = array();
        $query = "
            SELECT 
                i.*, 
                u.nombres_apellidos AS nombre_beneficiario,
                u.correo AS correo_beneficiario,
                u.telefono AS telefono_beneficiario,
                (SELECT COUNT(*) FROM ambientes_institucion ai WHERE ai.id_ies = i.id) AS total_ambientes,
                (SELECT COUNT(*) FROM bienes b 
                 JOIN ambientes_institucion ai ON b.id_ambiente = ai.id 
                 WHERE ai.id_ies = i.id AND b.estado = 1) AS total_bienes
            FROM institucion i
            LEFT JOIN usuarios u ON i.beneficiario = u.id
            WHERE $condicion 
            ORDER BY i.nombre ASC
        ";
        
        $respuesta = $this->conexion->query($query);
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }

    public function buscarInstituciones_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_nombre, $busqueda_tabla_codigo, $busqueda_tabla_ruc)
    {
        // Condicionales para búsqueda
        $condicion = " 1=1 ";
        if ($busqueda_tabla_nombre != '') {
            $condicion .= " AND i.nombre LIKE '%$busqueda_tabla_nombre%'";
        }
        if ($busqueda_tabla_codigo != '') {
            $condicion .= " AND i.cod_modular LIKE '%$busqueda_tabla_codigo%'";
        }
        if ($busqueda_tabla_ruc != '') {
            $condicion .= " AND i.ruc LIKE '%$busqueda_tabla_ruc%'";
        }
        
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        
        $query = "
            SELECT 
                i.*, 
                u.nombres_apellidos AS nombre_beneficiario,
                u.correo AS correo_beneficiario,
                u.telefono AS telefono_beneficiario,
                (SELECT COUNT(*) FROM ambientes_institucion ai WHERE ai.id_ies = i.id) AS total_ambientes,
                (SELECT COUNT(*) FROM bienes b 
                 JOIN ambientes_institucion ai ON b.id_ambiente = ai.id 
                 WHERE ai.id_ies = i.id AND b.estado = 1) AS total_bienes
            FROM institucion i
            LEFT JOIN usuarios u ON i.beneficiario = u.id
            WHERE $condicion 
            ORDER BY i.nombre ASC 
            LIMIT $iniciar, $cantidad_mostrar
        ";
        
        $respuesta = $this->conexion->query($query);
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function registrarInstitucion($beneficiario,$cod_modular, $ruc, $nombre)
    {
        $sql = $this->conexion->query("INSERT INTO institucion (beneficiario, cod_modular, ruc, nombre) VALUES ('$beneficiario','$cod_modular','$ruc','$nombre')");
        if ($sql) {
            $sql = $this->conexion->insert_id;
        } else {
            $sql = 0;
        }
        return $sql;
    }
    public function actualizarInstitucion($id, $beneficiario, $cod_modular, $ruc, $nombre)
    {
        $sql = $this->conexion->query("UPDATE institucion SET beneficiario= '$beneficiario', cod_modular='$cod_modular',ruc='$ruc',nombre='$nombre' WHERE id='$id'");
        return $sql;
    }
    public function buscarInstitucionOrdenado()
    {
        $sql = $this->conexion->query("SELECT * FROM institucion order by nombre ASC");
        $arrRespuesta = array();
        while ($objeto = $sql->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarInstitucionById($id)
    {
        $sql = $this->conexion->query("SELECT * FROM institucion WHERE id='$id'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarPrimerIe()
    {
        $sql = $this->conexion->query("SELECT * FROM institucion ORDER BY id ASC LIMIT 1");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarInstitucionByCodigo($codigo)
    {
        $sql = $this->conexion->query("SELECT * FROM institucion WHERE cod_modular='$codigo'");
        $sql = $sql->fetch_object();
        return $sql;
    }
    public function buscarInstitucionOrderByApellidosNombres_tabla_filtro($busqueda_tabla_codigo, $busqueda_tabla_ruc, $busqueda_tabla_insti)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " cod_modular LIKE '$busqueda_tabla_codigo%' AND ruc LIKE '$busqueda_tabla_ruc%' AND nombre LIKE '$busqueda_tabla_insti%'";
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM institucion WHERE $condicion ORDER BY nombre");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function buscarInstitucionOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_codigo, $busqueda_tabla_ruc, $busqueda_tabla_insti)
    {
        //condicionales para busqueda
        $condicion = "";
        $condicion .= " cod_modular LIKE '$busqueda_tabla_codigo%' AND ruc LIKE '$busqueda_tabla_ruc%' AND nombre LIKE '$busqueda_tabla_insti%'";
        $iniciar = ($pagina - 1) * $cantidad_mostrar;
        $arrRespuesta = array();
        $respuesta = $this->conexion->query("SELECT * FROM institucion WHERE $condicion ORDER BY nombre LIMIT $iniciar, $cantidad_mostrar");
        while ($objeto = $respuesta->fetch_object()) {
            array_push($arrRespuesta, $objeto);
        }
        return $arrRespuesta;
    }
    public function listarTodasLasInstituciones()
{
    $arrRespuesta = array();
    $query = "
    SELECT
    i.id,
    i.beneficiario,
    i.cod_modular,
    i.ruc,
    i.nombre,
    
    u.id AS usuario_id,
    u.nombres_apellidos AS nombre_beneficiario,
    u.dni AS usuario_dni,
    u.correo AS usuario_correo,
    u.telefono AS usuario_telefono
FROM institucion i
LEFT JOIN usuarios u ON i.beneficiario = u.id
ORDER BY i.nombre ASC;
    ";
    $respuesta = $this->conexion->query($query);
    while ($objeto = $respuesta->fetch_object()) {
        array_push($arrRespuesta, $objeto);
    }
    return $arrRespuesta;
}
}
