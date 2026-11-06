<?php
// controllers/MateriaController.php

class MateriaController {
    private $materiaModel;

    public function __construct($db) {
        require_once __DIR__ . '/../models/Materia.php';
        $this->materiaModel = new Materia($db);
    }

    // Manejar peticiones GET
    public function get($id = null) {
        header("Content-Type: application/json; charset=UTF-8");
        
        if ($id) {
            $materia = $this->materiaModel->obtenerPorId($id);
            if ($materia) {
                echo json_encode(["status" => "success", "data" => $materia]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Materia no encontrada"]);
            }
        } else {
            $materias = $this->materiaModel->obtenerTodas();
            echo json_encode(["status" => "success", "data" => $materias]);
        }
    }

    // Manejar peticiones POST (Crear)
    public function post($datos) {
        header("Content-Type: application/json; charset=UTF-8");
        
        if (!isset($datos['nombre']) || !isset($datos['clave'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios (nombre, clave)"]);
            return;
        }

        // Valor por defecto para créditos si no viene en el JSON
        $datos['creditos'] = isset($datos['creditos']) ? $datos['creditos'] : 5;

        $nuevoId = $this->materiaModel->crear($datos);
        
        if ($nuevoId) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Materia creada correctamente", "id" => $nuevoId]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al crear la materia"]);
        }
    }

        // Actualizar una materia
    public function put($id, $datos) {
        header("Content-Type: application/json; charset=UTF-8");
        if ($this->materiaModel->actualizar($id, $datos)) {
            echo json_encode(["status" => "success", "message" => "Materia actualizada"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al actualizar"]);
        }
    }

    // Eliminar una materia
    public function delete($id) {
        header("Content-Type: application/json; charset=UTF-8");
        if ($this->materiaModel->eliminar($id)) {
            echo json_encode(["status" => "success", "message" => "Materia eliminada"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al eliminar"]);
        }
    }
}
?>