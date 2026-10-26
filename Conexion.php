<?php
$host ="sql306.infinityfree.com"; // Poner el nombre del host de Mysql
$usuario ="if0_43176314"; // Reemplaza con tu nombre de usuario de mysql
$contrasena ="EFCXXzp3WDaS7Y"; // Reemplaza con tu contraseña de mysql
$base_de_datos = "if0_43176314_EmpleabilidadINAC"; // Reemplaza con el nombre de tu base de datos notas

// Crear conexión
$conn = mysqli_connect($host, $usuario, $contrasena, $base_de_datos);
$duro = $conn; // Alias temporal por compatibilidad con los archivos antiguos

// Verificar conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>