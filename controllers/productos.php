<?php
// Controlador de productos: la tienda los lista para todos, solo el admin los gestiona
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Crear la tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Semilla: los productos que antes estaban fijos en el HTML
$result = $conn->query('SELECT id FROM productos LIMIT 1');
if ($result->num_rows === 0) {
    $conn->query("INSERT INTO productos (nombre, precio, imagen) VALUES
        ('HONOR X8C', 900.00, 'img/x8c.jpg'),
        ('SAMSUNG A56', 1800.00, 'img/samsung a56.jpg'),
        ('IPHONE 16', 5500.00, 'img/iphone16.jpg')");
}

// Guarda la imagen subida en img/productos/ y devuelve su ruta relativa (o null si no se envió)
function guardarImagen() {
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $archivo = $_FILES['imagen'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        jsonError(400, 'Error al subir la imagen');
    }

    if ($archivo['size'] > 3 * 1024 * 1024) {
        jsonError(400, 'La imagen no debe pesar más de 3 MB');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($extensiones[$mime])) {
        jsonError(400, 'Solo se permiten imágenes JPG, PNG o WEBP');
    }

    $carpeta = __DIR__ . '/../img/productos';
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0775, true);
    }

    $nombreArchivo = uniqid('prod_') . '.' . $extensiones[$mime];
    move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombreArchivo);

    return 'img/productos/' . $nombreArchivo;
}

// Borra del disco solo las imágenes subidas desde el panel (no toca las originales de img/)
function borrarImagen($ruta) {
    if (strpos($ruta, 'img/productos/') === 0) {
        $archivo = __DIR__ . '/../' . $ruta;
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'listar_productos') {
    $result = $conn->query('SELECT id, nombre, precio, imagen FROM productos ORDER BY id ASC');
    $productos = [];

    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['precio'] = (float) $row['precio'];
        $productos[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $productos]);
    exit;
}

if ($action === 'agregar_producto') {
    requireAdmin();

    $nombre = trim($_POST['nombre'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);

    if ($nombre === '' || $precio <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ingresa un nombre y un precio válido']);
        exit;
    }

    $imagen = guardarImagen() ?? '';

    $stmt = $conn->prepare('INSERT INTO productos (nombre, precio, imagen) VALUES (?, ?, ?)');
    $stmt->bind_param('sds', $nombre, $precio, $imagen);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Producto agregado correctamente']);
    exit;
}

if ($action === 'editar_producto') {
    requireAdmin();

    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);

    if ($id <= 0 || $nombre === '' || $precio <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ingresa un nombre y un precio válido']);
        exit;
    }

    $stmt = $conn->prepare('SELECT imagen FROM productos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $actual = $stmt->get_result()->fetch_assoc();

    if (!$actual) {
        echo json_encode(['success' => false, 'message' => 'El producto no existe']);
        exit;
    }

    $imagen = guardarImagen();
    if ($imagen !== null) {
        borrarImagen($actual['imagen']);
    } else {
        $imagen = $actual['imagen'];
    }

    $stmt = $conn->prepare('UPDATE productos SET nombre = ?, precio = ?, imagen = ? WHERE id = ?');
    $stmt->bind_param('sdsi', $nombre, $precio, $imagen, $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
    exit;
}

if ($action === 'eliminar_producto') {
    requireAdmin();

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conn->prepare('SELECT imagen FROM productos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $actual = $stmt->get_result()->fetch_assoc();

    if (!$actual) {
        echo json_encode(['success' => false, 'message' => 'El producto no existe']);
        exit;
    }

    borrarImagen($actual['imagen']);

    $stmt = $conn->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
