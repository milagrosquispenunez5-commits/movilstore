<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function conectarBD(string $host, string $user, array $passwords): mysqli {
    foreach ($passwords as $pass) {
        try {
            return new mysqli($host, $user, $pass);
        } catch (Exception $e) {
        }
    }

    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la BD: revisa usuario y contraseña en config/db.php']);
    exit;
}

$database = 'movil_store';
$conn = conectarBD('localhost', 'root', ['root', '']);

try {
    $conn->query("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($database);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de BD: ' . $e->getMessage()]);
    exit;
}
