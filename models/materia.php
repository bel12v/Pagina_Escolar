<?php
// models/Materia.php

class Materia {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todas las materias
    public function obtenerTodas() {
        $query = "SELECT id, nombre, clave, creditos FROM materias";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una materia por ID
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, clave, creditos FROM materias WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear una nueva materia
    public function crear($datos) {
        $query = "INSERT INTO materias (nombre, clave, creditos) VALUES (:nombre, :clave, :creditos)";
        $stmt = $this->conn->prepare($query);
        
        $exito = $stmt->execute([
            ':nombre'   => $datos['nombre'], 
            ':clave'    => $datos['clave'], 
            ':creditos' => $datos['creditos']
        ]);

        if ($exito) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

        // Actualizar
    // Transacción local de MySQL: si algo falla, rollBack deja la materia como estaba.
    public function actualizar($id, $datos) {
        try {
            $this->conn->beginTransaction();
            $query = "UPDATE materias SET nombre = :nombre, clave = :clave, creditos = :creditos WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':clave' => $datos['clave'],
                ':creditos' => $datos['creditos'],
                ':id' => $id
            ]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Eliminar
    public function eliminar($id) {
        $query = "DELETE FROM materias WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
?>