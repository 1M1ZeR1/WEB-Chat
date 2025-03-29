<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$dbname = "web-chat";
$db_username = "root";
$db_password = "Paha91lot151010";

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Пустой или неверный JSON"]);
    exit();
}

if (!isset($data["login"], $data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Логин и пароль обязательны"]);
    exit();
}

$login = $data["login"];
$password = $data["password"];

try {
    $pdo = new PDO("mysql:host=$host;port=3308;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT `index` AS id, username, password, position FROM users WHERE username = :login LIMIT 1");
    $stmt->bindParam(":login", $login, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["user"] = [
                "id"       => $user["id"],
                "login"    => $user["username"],
                "position" => $user["position"]
            ];
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>
