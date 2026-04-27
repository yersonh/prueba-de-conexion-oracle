<?php
// config/database.php

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        // Obtener variables de entorno
        $oracle_host = getenv('ORACLE_HOST') ?: 'adb.mx-queretaro-1.oraclecloud.com';
        $oracle_port = getenv('ORACLE_PORT') ?: '1522';
        $oracle_service = getenv('ORACLE_SERVICE') ?: 'g7d0a109709d57d_bc27bncudfcgiclb_high.adb.oraclecloud.com';
        $oracle_user = getenv('ORACLE_USER') ?: 'ADMIN';
        $oracle_password = getenv('ORACLE_PASSWORD') ?: '';
        
        // Crear TNS (connection string)
        $tns = "(DESCRIPTION=" .
               "(RETRY_COUNT=20)" .
               "(RETRY_DELAY=3)" .
               "(ADDRESS=" .
               "(PROTOCOL=TCPS)" .
               "(PORT=$oracle_port)" .
               "(HOST=$oracle_host)" .
               ")" .
               "(CONNECT_DATA=" .
               "(SERVICE_NAME=$oracle_service)" .
               ")" .
               "(SECURITY=" .
               "(SSL_SERVER_DN_MATCH=yes)" .
               ")" .
               ")";
        
        try {
            // Conectar a Oracle
            $this->connection = oci_connect($oracle_user, $oracle_password, $tns);
            
            if (!$this->connection) {
                $e = oci_error();
                throw new Exception("Error de conexión Oracle: " . $e['message']);
            }
            
            // Configurar el conjunto de caracteres
            oci_set_client_identifier($this->connection, "PHP_APP");
            
        } catch (Exception $e) {
            die("Error conectando a la base de datos: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = array()) {
        $stid = oci_parse($this->connection, $sql);
        
        if (!$stid) {
            $e = oci_error($this->connection);
            throw new Exception("Error en SQL: " . $e['message']);
        }
        
        foreach ($params as $key => $value) {
            oci_bind_by_name($stid, ':' . $key, $params[$key]);
        }
        
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            throw new Exception("Error ejecutando query: " . $e['message']);
        }
        
        return $stid;
    }
    
    public function fetchAll($statement) {
        $result = array();
        while ($row = oci_fetch_assoc($statement)) {
            $result[] = $row;
        }
        oci_free_statement($statement);
        return $result;
    }
    
    public function fetchOne($statement) {
        $row = oci_fetch_assoc($statement);
        oci_free_statement($statement);
        return $row;
    }
    
    public function execute($sql, $params = array()) {
        return $this->query($sql, $params);
    }
    
    public function close() {
        if ($this->connection) {
            oci_close($this->connection);
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}

// Instancia global
$db = new Database();
?>