<?php
// Configuración de conexión a MySQL
// Funciona en Linux (clave 'root') y en XAMPP Windows (clave vacía) sin cambiar nada.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Se prueban las contraseñas típicas de cada entorno; si ninguna funciona, corta con error 500
function conectarBD(string $host, string $user, array $passwords): mysqli {
    foreach ($passwords as $pass) {
        try {
            return new mysqli($host, $user, $pass);
        } catch (Exception $e) {
            // Se intenta con la siguiente contraseña
        }
    }

    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la BD: revisa usuario y contraseña en config/db.php']);
    exit;
}

$database = 'movil_store';
$conn = conectarBD('localhost', 'root', ['root', '']);

try {
    // Crea la base de datos si no existe (primera vez en una máquina nueva)
    $conn->query("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($database);
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de BD: ' . $e->getMessage()]);
    exit;
}
