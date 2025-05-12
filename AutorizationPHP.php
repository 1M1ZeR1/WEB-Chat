<?php
$allowedOrigins = ['https://web-chat-tca4.vercel.app','http://localhost:80'];
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

define('COOKIE_SECRET', '12345678');

function setSecureCookie($name, $data, $expire = 86400) {
    $data['timestamp'] = time();
    $data['signature'] = hash_hmac('sha256', json_encode($data), COOKIE_SECRET);
    
    setcookie(
        $name,
        json_encode($data),
        [
            'expires' => time() + $expire,
            'path' => '/',
            'domain' => '',
            'secure' => true, 
            'httponly' => true,
            'samesite' => 'None' 
        ]
    );
}

function verifyCookie($name) {
    if (!isset($_COOKIE[$name])) return false;
    
    $data = json_decode($_COOKIE[$name], true);
    if (!isset($data['signature'])) return false;
    
    $signature = $data['signature'];
    unset($data['signature']);
    
    return hash_hmac('sha256', json_encode($data), COOKIE_SECRET) === $signature;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data["login"], $data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Логин и пароль обязательны"]);
    exit();
}

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
            error_log($user['id']);
            error_log($user['username']);
            error_log($user['position']);
            setSecureCookie('UserAuth', [
                'id' => $user['id'],
                'login' => $user['username'],
                'position' => $user['position']
            ]);
            
            echo json_encode([
                "success" => true,
                "message" => "Авторизация успешна",
                "user" => [
                    "id" => $user['id'],
                    "login" => $user['username'],
                    "position" => $user['position']
                ]
            ]);
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