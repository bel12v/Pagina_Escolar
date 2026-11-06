<?php
// api/usuarios.php

// Permitir CORS si lo necesitas para conectar un frontend separado
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la conexión a la base de datos (ajusta la ruta si es necesario)
require_once __DIR__ . '/../db.php';
// Incluir el controlador
require_once __DIR__ . '/../controllers/UsuarioController.php';

// Tu archivo db.php debe tener una variable $conn o similar. 
// Si usa POO (new Database()), ajústalo aquí.
global $conn; 

$controller = new UsuarioController($conn);

// Detectar el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Ejemplo: api/usuarios.php?id=1
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $controller->get($id);
        break;

    case 'POST':
        // Leer el JSON enviado en el body
        $input = json_decode(file_get_contents("php://input"), true);
        // Si no es JSON, intentar con formularios normales ($_POST)
        if (!$input) $input = $_POST; 
        
        $controller->post($input);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        break;
}
?>