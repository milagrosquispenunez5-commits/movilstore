<?php
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$conn->query("CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'agregar_cliente') {
    requireAdmin();

    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');

    if ($dni === '' || $nombre === '' || $telefono === '' || $modelo === '') {
        echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
        exit;
    }

    if (!preg_match('/^\d{8}$/', $dni)) {
        echo json_encode(['success' => false, 'message' => 'El DNI debe tener 8 dígitos']);
        exit;
    }

    if (!preg_match('/^\d{9}$/', $telefono)) {
        echo json_encode(['success' => false, 'message' => 'El teléfono debe tener 9 dígitos']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id FROM clientes WHERE dni = ?');
    $stmt->bind_param('s', $dni);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un cliente con ese DNI']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO clientes (dni, nombre, telefono, modelo) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $dni, $nombre, $telefono, $modelo);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Cliente registrado correctamente']);
    exit;
}

if ($action === 'listar_clientes') {
    requireAdmin();

    $result = $conn->query('SELECT dni, nombre, telefono, modelo FROM clientes ORDER BY id ASC');
    $clientes = [];

    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $clientes]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
