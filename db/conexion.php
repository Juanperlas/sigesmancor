<?php
class Conexion {
    private $host = 'localhost';
    private $db = 'sigesman';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $conn;

    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConexion() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return $this->conn->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
        
        return true;
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
        return true;
    }

    /**
     * Verifica si un usuario tiene un permiso específico
     * @param int $usuario_id
     * @param string $permiso
     * @return bool
     */
    public function hasPermission($usuario_id, $permiso) {
        $sql = "SELECT COUNT(*) as total
                FROM usuarios_roles ur
                JOIN roles_permisos rp ON ur.rol_id = rp.rol_id
                JOIN permisos p ON rp.permiso_id = p.id
                WHERE ur.usuario_id = ? AND p.nombre = ?";
        $result = $this->selectOne($sql, [$usuario_id, $permiso]);
        return $result['total'] > 0;
    }

    /**
     * Obtiene los roles de un usuario
     * @param int $usuario_id
     * @return array
     */
    public function getUserRoles($usuario_id) {
        $sql = "SELECT r.nombre
                FROM usuarios_roles ur
                JOIN roles r ON ur.rol_id = r.id
                WHERE ur.usuario_id = ?";
        return array_column($this->select($sql, [$usuario_id]), 'nombre');
    }
}
?>
