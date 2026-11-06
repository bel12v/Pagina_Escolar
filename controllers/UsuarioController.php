<?php
// controllers/UsuarioController.php

class UsuarioController {
    private $usuarioModel;

    public function __construct($db) {
        require_once __DIR__ . '/../models/Usuario.php';
        $this->usuarioModel = new Usuario($db);
    }

    // Manejar peticiones GET
    public function get($id = null) {
        header("Content-Type: application/json; charset=UTF-8");
        
        if ($id) {
            $usuario = $this->usuarioModel->obtenerPorId($id);
            if ($usuario) {
                echo json_encode(["status" => "success", "data" => $usuario]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
            }
        } else {
            $usuarios = $this->usuarioModel->obtenerTodos();
            echo json_encode(["status" => "success", "data" => $usuarios]);
        }
    }

    // Manejar peticiones POST (Crear)
    public function post($datos) {
        header("Content-Type: application/json; charset=UTF-8");
        
        // Validación básica
        if (!isset($datos['nombre']) || !isset($datos['email']) || !isset($datos['rol'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
            return;
        }

        $nuevoId = $this->usuarioModel->crear($datos);
        
        if ($nuevoId) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Usuario creado", "id" => $nuevoId]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al crear el usuario"]);
        }
    }
}
?>