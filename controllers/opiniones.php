<?php
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Crear la tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS opinions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    opinion TEXT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_opinions') {
    $result = $conn->query("SELECT nombre, opinion, rating, DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha FROM opinions ORDER BY id DESC");
    $opiniones = [];

    while ($row = $result->fetch_assoc()) {
        $row['rating'] = (int) $row['rating'];
        $opiniones[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $opiniones]);
    exit;
}

if ($action === 'add_opinion_client') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $opinion = trim($_POST['opinion'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 5);

    if ($nombre === '' || $opinion === '') {
        echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    $stmt = $conn->prepare('INSERT INTO opinions (nombre, email, opinion, rating) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $nombre, $email, $opinion, $rating);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Opinión guardada correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
