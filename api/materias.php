<?php
// api/materias.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la conexión y el controlador
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../controllers/MateriaController.php';

$controller = new MateriaController($conn);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $controller->get($id);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) { $input = $_POST; }
        $controller->post($input);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        break;


        // ... dentro del switch($method)
    case 'PUT':
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $input = json_decode(file_get_contents("php://input"), true);
        $controller->put($id, $input);
        break;
    case 'DELETE':
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $controller->delete($id);
        break;
}
?>