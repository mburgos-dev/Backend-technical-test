<?php
// Conexión a la base de datos SQLite
$database = new SQLite3('api.db');

// Crear tabla de clientes y simulaciones si no existe
$database->exec('CREATE TABLE IF NOT EXISTS clientes (dni TEXT PRIMARY KEY, nombre TEXT, email TEXT, capital REAL)');
$database->exec('CREATE TABLE IF NOT EXISTS simulaciones (dni TEXT PRIMARY KEY, cuota_mensual REAL, importe_total REAL)');

// Obtener el método y la ruta de la solicitud
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'];

// Función para enviar una respuesta JSON
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Función para calcular la cuota mensual de la hipoteca
function calcularCuotaMensual($capital, $tae, $plazo) {
    $i = $tae / 100 / 12;
    $n = $plazo * 12;
    return $capital * $i / (1 - pow(1 + $i, -$n));
}

// Manejar las solicitudes
switch ($method) {
    case 'GET':
        // Consultar los datos de un cliente existente por su DNI
        if ($path === '/clientes' && isset($_GET['dni'])) {
            $dni = $_GET['dni'];
            $stmt = $database->prepare('SELECT * FROM clientes WHERE dni= :dni');
            $stmt->bindValue(':dni', $dni, SQLITE3_TEXT);
            $result = $stmt->execute();
            $rows = $result->fetchArray(SQLITE3_ASSOC);

            if (!empty($rows)) {
                sendResponse($rows);
            } else {
                sendResponse(['error' => 'Cliente no encontrado'], 404);
            }   
        }
    break;

    case 'POST':
        // Crear un nuevo cliente
        if ($path === '/clientes') {
            $requestPayload = json_decode(file_get_contents('php://input'), true);
            $dni = $requestPayload['dni'];
            $nombre = $requestPayload['nombre'];
            $email = $requestPayload['email'];
            $capital = $requestPayload['capital'];

            // Validar el DNI usando el algoritmo oficial
            if (!preg_match('/^\d{8}[A-Z]$/', $dni)) {
                sendResponse(['error' => 'DNI inválido'], 400);
            }

            // Insertar los datos del cliente en la tabla
            $stmt = $database->prepare('INSERT INTO clientes (dni, nombre, email, capital) VALUES (:dni, :nombre, :email, :capital)');
            $stmt->bindValue(':dni', $dni, SQLITE3_TEXT);
            $stmt->bindValue(':nombre', $nombre, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':capital', $capital, SQLITE3_FLOAT);
            $stmt->execute();

            sendResponse(['message' => 'Cliente creado correctamente']);
            
        } elseif ($path === '/simulacion') {
            // Solicitar una simulación de hipoteca
            $requestPayload = json_decode(file_get_contents('php://input'), true);
            $dni = $requestPayload['dni'];
            $tae = $requestPayload['tae'];
            $plazo = $requestPayload['plazo'];

            // Validar el DNI usando el algoritmo oficial 
            if (!preg_match('/^\d{8}[A-Z]$/', $dni)) {
                sendResponse(['error' => 'DNI inválido'], 400);
            }

            // Obtener el capital del cliente de la tabla
            $stmt = $database->prepare('SELECT capital FROM clientes WHERE dni = :dni');
            $stmt->bindValue(':dni', $dni);
            $result = $stmt->execute();

            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $capital = $row['capital'];
                $cuotaMensual = calcularCuotaMensual($capital, $tae, $plazo);
                $importeTotal = $cuotaMensual * $plazo * 12;

                // Guardar los resultados en la tabla de simulaciones
                $stmt = $database->prepare('INSERT INTO simulaciones (dni, cuota_mensual, importe_total) VALUES (:dni, :cuota_mensual, :importe_total)');
                $stmt->bindValue(':dni', $dni);
                $stmt->bindValue(':cuota_mensual', $cuotaMensual);
                $stmt->bindValue(':importe_total', $importeTotal);
                $stmt->execute();

                sendResponse(['cuota_mensual' => $cuotaMensual, 'importe_total' => $importeTotal]);
            } else {
                sendResponse(['error' => 'Cliente no encontrado'], 404);
            }
        }
        break;

    case 'PUT':
        // Modificar los datos de un cliente existente
        if ($path === '/clientes' && isset($_GET['dni'])) {
            $dni = $_GET['dni'];
            $requestPayload = json_decode(file_get_contents('php://input'), true);
            $nombre = $requestPayload['nombre'];
            $email = $requestPayload['email'];
            $capital = $requestPayload['capital'];

            $stmt = $database->prepare('UPDATE clientes SET nombre = :nombre, email = :email, capital = :capital WHERE dni = :dni');
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':capital', $capital);
            $stmt->execute();

            sendResponse(['message' => 'Cliente actualizado correctamente']);
        }
        break;

    case 'DELETE':
        // Eliminar los datos de un cliente existente
        if ($path === '/clientes' && isset($_GET['dni'])) {
            $dni = $_GET['dni'];

            $stmt = $database->prepare('DELETE FROM clientes WHERE dni = :dni');
            $stmt->bindValue(':dni', $dni);
            $stmt->execute();

            sendResponse(['message' => 'Cliente eliminado correctamente']);
        }
        break;
}

// Si no se encontró una ruta o método válido
sendResponse(['error' => 'Ruta no encontrada'], 404);