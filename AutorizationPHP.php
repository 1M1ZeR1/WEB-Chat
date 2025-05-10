<?php
// Динамически разрешаем запросы от локального или продакшн-домена
$allowedOrigins = ['https://web-chat-tca4.vercel.app', 'http://localhost:3000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://web-chat-tca4.vercel.app");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Обработка preflight (OPTIONS) запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Настройка параметров куки для авторизации
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => parse_url($origin, PHP_URL_HOST) ?? 'web-chat-tca4.vercel.app',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

session_start();

// Получение данных запроса
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data["login"], $data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Логин и пароль обязательны"]);
    exit();
}

// Подключение к базе данных
$host = "localhost";
$dbname = "web-chat";
$db_username = "root";
$db_password = "Paha91lot151010";
$port = 3308;

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
            
            // Устанавливаем куки
            setcookie(
                session_name(), 
                session_id(), 
                [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'domain' => parse_url($origin, PHP_URL_HOST) ?? 'web-chat-tca4.vercel.app',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'None'
                ]
            );

            echo json_encode(["success" => true, "message" => "Авторизация успешна"]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Неверный пароль"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Пользователь не найден"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Ошибка базы данных: " . $e->getMessage()]);
}
?>
