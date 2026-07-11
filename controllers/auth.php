<?php
// Controlador de autenticación: login, register y logout
session_start();
require __DIR__ . '/../config/db.php';

// Crear la tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

function volver($params) {
    header('Location: ../views/login.html?' . http_build_query($params));
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'check') {
    header('Content-Type: application/json');
    echo json_encode(['logged' => isset($_SESSION['user_id']), 'username' => $_SESSION['username'] ?? null]);
    exit;
}

if ($action === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        volver(['error' => 'Completa todos los campos']);
    }

    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // password_verify para contraseñas nuevas; hash_equals por si existen usuarios antiguos en texto plano
    if ($user && (password_verify($password, $user['password']) || hash_equals($user['password'], $password))) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ../views/registro.html');
        exit;
    }

    volver(['error' => 'Usuario o contraseña incorrecta']);
}

if ($action === 'register') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        volver(['error' => 'Completa todos los campos', 'vista' => 'registro']);
    }

    if (strlen($password) < 4) {
        volver(['error' => 'La contraseña debe tener mínimo 4 caracteres', 'vista' => 'registro']);
    }

    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $usuario);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        volver(['error' => 'El usuario ya existe', 'vista' => 'registro']);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->bind_param('ss', $usuario, $hash);
    $stmt->execute();

    volver(['ok' => 'Usuario creado correctamente, ahora inicia sesión']);
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    volver(['ok' => 'Sesión cerrada']);
}

header('Location: ../views/login.html');
exit;
