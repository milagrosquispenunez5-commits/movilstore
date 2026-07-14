<?php
// Controlador de autenticación: login, register (rol cliente), logout y check
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../config/db.php';

// Crear la tabla si no existe (instalación nueva)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(10) NOT NULL DEFAULT 'cliente',
    nombre VARCHAR(100) NOT NULL DEFAULT '',
    dni VARCHAR(15) NOT NULL DEFAULT '',
    telefono VARCHAR(20) NOT NULL DEFAULT ''
)");

// Migración para BDs creadas antes de los roles
ensureColumn($conn, 'users', 'rol', "VARCHAR(10) NOT NULL DEFAULT 'cliente'");
ensureColumn($conn, 'users', 'nombre', "VARCHAR(100) NOT NULL DEFAULT ''");
ensureColumn($conn, 'users', 'dni', "VARCHAR(15) NOT NULL DEFAULT ''");
ensureColumn($conn, 'users', 'telefono', "VARCHAR(20) NOT NULL DEFAULT ''");

// Semilla: si no existe ningún administrador, se promueve al usuario 'admin'
// (BDs creadas antes de los roles) o se crea admin / admin123
$result = $conn->query("SELECT id FROM users WHERE rol = 'admin' LIMIT 1");
if ($result->num_rows === 0) {
    $existente = $conn->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetch_assoc();

    if ($existente) {
        $conn->query("UPDATE users SET rol = 'admin', nombre = 'Administrador' WHERE id = " . (int) $existente['id']);
    } else {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, rol, nombre) VALUES ('admin', ?, 'admin', 'Administrador')");
        $stmt->bind_param('s', $hash);
        $stmt->execute();
    }
}

function volver($params) {
    header('Location: ../views/login.html?' . http_build_query($params));
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'check') {
    header('Content-Type: application/json');
    echo json_encode([
        'logged' => isset($_SESSION['user_id']),
        'username' => $_SESSION['username'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? null,
        'rol' => $_SESSION['rol'] ?? null,
    ]);
    exit;
}

if ($action === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        volver(['error' => 'Completa todos los campos']);
    }

    $stmt = $conn->prepare('SELECT id, username, password, rol, nombre FROM users WHERE username = ?');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // password_verify para contraseñas nuevas; hash_equals por si existen usuarios antiguos en texto plano
    if ($user && (password_verify($password, $user['password']) || hash_equals($user['password'], $password))) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre'] !== '' ? $user['nombre'] : $user['username'];

        if ($user['rol'] === 'admin') {
            header('Location: ../views/admin.html');
        } else {
            header('Location: ../index.html');
        }
        exit;
    }

    volver(['error' => 'Usuario o contraseña incorrecta']);
}

if ($action === 'register') {
    // Registro público: siempre crea cuentas con rol cliente
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($usuario === '' || $password === '' || $nombre === '' || $dni === '' || $telefono === '') {
        volver(['error' => 'Completa todos los campos', 'vista' => 'registro']);
    }

    if (strlen($password) < 4) {
        volver(['error' => 'La contraseña debe tener mínimo 4 caracteres', 'vista' => 'registro']);
    }

    if (!preg_match('/^\d{8}$/', $dni)) {
        volver(['error' => 'El DNI debe tener 8 dígitos', 'vista' => 'registro']);
    }

    if (!preg_match('/^\d{9}$/', $telefono)) {
        volver(['error' => 'El teléfono debe tener 9 dígitos', 'vista' => 'registro']);
    }

    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $usuario);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        volver(['error' => 'El usuario ya existe', 'vista' => 'registro']);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, rol, nombre, dni, telefono) VALUES (?, ?, 'cliente', ?, ?, ?)");
    $stmt->bind_param('sssss', $usuario, $hash, $nombre, $dni, $telefono);
    $stmt->execute();

    volver(['ok' => 'Cuenta creada correctamente, ahora inicia sesión']);
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ../index.html');
    exit;
}

header('Location: ../views/login.html');
exit;
