<?php
// Configuración de conexión a MySQL
// Funciona en Linux (clave 'root') y en XAMPP Windows (clave vacía) sin cambiar nada.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$user = 'root';
$database = 'movil_store';

// Se prueban las contraseñas típicas de cada entorno
$passwords = ['root', ''];

$conn = null;
foreach ($passwords as $pass) {
    try {
        $conn = new mysqli($host, $user, $pass);
        break;
    } catch (Exception $e) {
        $conn = null;
    }
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la BD: revisa usuario y contraseña en config/db.php']);
    exit;
}

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
?>
