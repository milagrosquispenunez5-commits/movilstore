<?php
session_start();
require 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true, 'message' => 'Login correcto']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrecta']);
    }
    exit;
}

if ($action === 'register') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
        exit;
    }

    if (strlen($password) < 4) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener mínimo 4 caracteres']);
        exit;
    }

    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $usuario);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El usuario ya existe']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->bind_param('ss', $usuario, $password);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
