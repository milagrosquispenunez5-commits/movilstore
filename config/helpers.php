<?php
// Helpers compartidos: sesión, control de roles y migraciones ligeras
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Respuesta de error JSON y corte de ejecución
function jsonError($codigo, $mensaje) {
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

// Exige sesión iniciada (cliente o admin) en endpoints JSON
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        jsonError(401, 'Debes iniciar sesión');
    }
}

// Exige rol administrador en endpoints JSON
function requireAdmin() {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        jsonError(403, 'Solo el administrador puede realizar esta acción');
    }
}

// Agrega una columna si no existe (para BDs creadas antes de este cambio)
function ensureColumn($conn, $tabla, $columna, $definicion) {
    $result = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
    }
}
