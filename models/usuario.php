<?php
// models/Usuario.php

class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todos los usuarios
    public function obtenerTodos() {
        $query = "SELECT id, nombre, apellido, email, rol, estado, fecha_registro FROM usuarios";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        // fetchAll(PDO::FETCH_ASSOC) es el equivalente a MYSQLI_ASSOC
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un usuario por ID
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, apellido, email, rol, estado, fecha_registro FROM usuarios WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        // En PDO se usa bindParam o se pasa el array directamente en el execute
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear un nuevo usuario
    public function crear($datos) {
        // Nota: En un caso real, la contraseña debería encriptarse (ej. password_hash)
        $query = "INSERT INTO usuarios (nombre, apellido, email, password, rol, estado) VALUES (:nombre, :apellido, :email, :password, :rol, :estado)";
        $stmt = $this->conn->prepare($query);
        
        // Ejecutamos pasando los parámetros con nombres
        $exito = $stmt->execute([
            ':nombre'   => $datos['nombre'], 
            ':apellido' => $datos['apellido'], 
            ':email'    => $datos['email'], 
            ':password' => $datos['password'], 
            ':rol'      => $datos['rol'], 
            ':estado'   => $datos['estado']
        ]);

        if ($exito) {
            // lastInsertId es el equivalente a insert_id en MySQLi
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>