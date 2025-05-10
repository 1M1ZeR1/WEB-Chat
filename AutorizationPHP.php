<?php
// Настройки сессии
session_set_cookie_params([
    'lifetime' => 86400, // 1 день
    'path' => '/',
    'domain' => '',      // Пусто для localhost
    'secure' => false,   // true для HTTPS в продакшене
    'httponly' => true,
    'samesite' => 'Lax'  // None требует secure=true
]);
session_start();

// Настройки CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Подключение к БД
$host = "localhost";
$dbname = "web-chat";
$db_username = "root";
$db_password = "Paha91lot151010";
$port = 3308;

// Получение данных
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON data"]);
    exit();
}

if (!isset($data["login"], $data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Login and password are required"]);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT `index` AS id, username, password, position FROM users WHERE username = :login LIMIT 1");
    $stmt->bindParam(":login", $data["login"], PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (password_verify($data["password"], $user["password"])) {
            $_SESSION["user"] = [
                "id" => $user["id"],
                "login" => $user["username"],
                "position" => $user["position"]
            ];
            echo json_encode(["success" => true, "message" => "Login successful"]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid password"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>