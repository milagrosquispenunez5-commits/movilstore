<?php
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$conn->query("CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    items TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'crear_pedido') {
    requireLogin();

    $items = json_decode($_POST['items'] ?? '', true);

    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }

    $total = 0;
    $modelos = [];
    foreach ($items as $item) {
        $nombre = trim((string) ($item['nombre'] ?? ''));
        $precio = (float) ($item['precio'] ?? 0);

        if ($nombre === '' || $precio <= 0) {
            echo json_encode(['success' => false, 'message' => 'El carrito tiene productos no válidos']);
            exit;
        }

        $total += $precio;
        $modelos[] = $nombre;
    }

    $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare('INSERT INTO pedidos (user_id, items, total) VALUES (?, ?, ?)');
    $stmt->bind_param('isd', $userId, $itemsJson, $total);
    $stmt->execute();

    $stmt = $conn->prepare('SELECT nombre, dni, telefono FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && preg_match('/^\d{8}$/', $user['dni'])) {
        $modelo = mb_substr(implode(', ', $modelos), 0, 100);
        $stmt = $conn->prepare('INSERT INTO clientes (dni, nombre, telefono, modelo) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE telefono = VALUES(telefono), modelo = VALUES(modelo)');
        $stmt->bind_param('ssss', $user['dni'], $user['nombre'], $user['telefono'], $modelo);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Pedido registrado correctamente. Nos contactaremos contigo 📱']);
    exit;
}

if ($action === 'listar_pedidos') {
    requireAdmin();

    $result = $conn->query("SELECT p.id, u.nombre, u.username, p.items, p.total,
            DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i') AS fecha
        FROM pedidos p
        LEFT JOIN users u ON u.id = p.user_id
        ORDER BY p.id DESC");

    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $row['items'] = json_decode($row['items'], true) ?: [];
        $row['total'] = (float) $row['total'];
        $pedidos[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $pedidos]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
