<?php
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$conn->query("CREATE TABLE IF NOT EXISTS opinions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    opinion TEXT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

ensureColumn($conn, 'opinions', 'user_id', 'INT NULL');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_opinions') {
    requireAdmin();

    $result = $conn->query("SELECT nombre, opinion, rating, DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha FROM opinions ORDER BY id DESC");
    $opiniones = [];

    while ($row = $result->fetch_assoc()) {
        $row['rating'] = (int) $row['rating'];
        $opiniones[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $opiniones]);
    exit;
}

if ($action === 'mis_opiniones') {
    requireLogin();

    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT opinion, rating, DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha FROM opinions WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $opiniones = [];
    while ($row = $result->fetch_assoc()) {
        $row['rating'] = (int) $row['rating'];
        $opiniones[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $opiniones]);
    exit;
}

if ($action === 'add_opinion_client') {
    requireLogin();

    $opinion = trim($_POST['opinion'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 5);

    if ($opinion === '') {
        echo json_encode(['success' => false, 'message' => 'Escribe tu opinión']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    $userId = $_SESSION['user_id'];
    $nombre = $_SESSION['nombre'] ?? $_SESSION['username'];
    $email = '';

    $stmt = $conn->prepare('INSERT INTO opinions (user_id, nombre, email, opinion, rating) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $userId, $nombre, $email, $opinion, $rating);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Opinión guardada correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
