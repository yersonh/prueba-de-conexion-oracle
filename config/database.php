<?php
// config/database.php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getConnection() {
        return self::getInstance()->connection;
    }
    
    private function connect() {
        // Obtener variables de entorno (requeridas en producción)
        $oracle_host = getenv('ORACLE_HOST');
        $oracle_port = getenv('ORACLE_PORT');
        $oracle_service = getenv('ORACLE_SERVICE');
        $oracle_user = getenv('ORACLE_USER');
        $oracle_password = getenv('ORACLE_PASSWORD');

        // Validar que todas las variables estén configuradas
        if (!$oracle_host || !$oracle_port || !$oracle_service || !$oracle_user || !$oracle_password) {
            throw new Exception("Error: Variables de entorno de Oracle no configuradas. " .
                "Configura ORACLE_HOST, ORACLE_PORT, ORACLE_SERVICE, ORACLE_USER, ORACLE_PASSWORD");
        }
        
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

?>