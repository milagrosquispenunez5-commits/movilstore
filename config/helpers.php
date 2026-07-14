<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonError($codigo, $mensaje) {
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        jsonError(401, 'Debes iniciar sesión');
    }
}

function requireAdmin() {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        jsonError(403, 'Solo el administrador puede realizar esta acción');
    }
}

function ensureColumn($conn, $tabla, $columna, $definicion) {
    $result = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
    }
}
