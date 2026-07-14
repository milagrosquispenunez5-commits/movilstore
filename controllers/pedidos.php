<?php
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$conn->query("CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    cliente_nombre VARCHAR(100) NULL,
    items TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

ensureColumn($conn, 'pedidos', 'cliente_nombre', 'VARCHAR(100) NULL');

$columna = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'user_id'")->fetch_assoc();
if ($columna && $columna['Null'] === 'NO') {
    $conn->query('ALTER TABLE pedidos MODIFY user_id INT NULL');
}

function leerItemsPedido($mensajeVacio) {
    $items = json_decode($_POST['items'] ?? '', true);

    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['success' => false, 'message' => $mensajeVacio]);
        exit;
    }

    $total = 0;
    $limpios = [];
    foreach ($items as $item) {
        $nombre = trim((string) ($item['nombre'] ?? ''));
        $precio = (float) ($item['precio'] ?? 0);

        if ($nombre === '' || $precio <= 0) {
            echo json_encode(['success' => false, 'message' => 'Hay productos con nombre o precio no válido']);
            exit;
        }

        $total += $precio;
        $limpios[] = ['nombre' => $nombre, 'precio' => $precio];
    }

    return [$limpios, $total];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'crear_pedido') {
    requireLogin();

    [$items, $total] = leerItemsPedido('El carrito está vacío');
    $modelos = array_column($items, 'nombre');

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

    $result = $conn->query("SELECT p.id, p.cliente_nombre, u.nombre, u.username, p.items, p.total,
            DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i') AS fecha
        FROM pedidos p
        LEFT JOIN users u ON u.id = p.user_id
        ORDER BY p.id DESC");

    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['items'] = json_decode($row['items'], true) ?: [];
        $row['total'] = (float) $row['total'];
        $pedidos[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $pedidos]);
    exit;
}

if ($action === 'agregar_pedido_admin') {
    requireAdmin();

    $cliente = trim($_POST['cliente'] ?? '');

    if ($cliente === '') {
        echo json_encode(['success' => false, 'message' => 'Ingresa el nombre del cliente']);
        exit;
    }

    [$items, $total] = leerItemsPedido('Agrega al menos un producto');
    $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare('INSERT INTO pedidos (user_id, cliente_nombre, items, total) VALUES (NULL, ?, ?, ?)');
    $stmt->bind_param('ssd', $cliente, $itemsJson, $total);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pedido registrado correctamente']);
    exit;
}

if ($action === 'editar_pedido') {
    requireAdmin();

    $id = (int) ($_POST['id'] ?? 0);
    $cliente = trim($_POST['cliente'] ?? '');

    if ($id <= 0 || $cliente === '') {
        echo json_encode(['success' => false, 'message' => 'Ingresa el nombre del cliente']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id FROM pedidos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'El pedido no existe']);
        exit;
    }

    [$items, $total] = leerItemsPedido('Agrega al menos un producto');
    $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare('UPDATE pedidos SET cliente_nombre = ?, items = ?, total = ? WHERE id = ?');
    $stmt->bind_param('ssdi', $cliente, $itemsJson, $total, $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    exit;
}

if ($action === 'eliminar_pedido') {
    requireAdmin();

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conn->prepare('SELECT id FROM pedidos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'El pedido no existe']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM pedidos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pedido eliminado correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
