<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '.web-chat-tca4.vercel.app',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

header("Access-Control-Allow-Origin: https://web-chat-tca4.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Пустой или неверный JSON"]);
    exit();
}

if (!isset($data["login"], $data["password"], $data["position"])) {
    http_response_code(400);
    echo json_encode(["error" => "Не все данные указаны"]);
    exit();
}

$host = "localhost";
$dbname = "web-chat";
$username = "root";
$password = "Paha91lot151010";
$port = 3308;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, position) VALUES (:login, :password, :position)");
    $stmt->execute([
        ':login' => $data["login"],
        ':password' => $hashedPassword,
        ':position' => $data["position"]
    ]);
    
    $_SESSION["user"] = [
        "id" => $pdo->lastInsertId(),
        "login" => $data["login"],
        "position" => $data["position"]
    ];
    
    echo json_encode(["success" => true, "message" => "Пользователь зарегистрирован"]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Ошибка базы данных: " . $e->getMessage()]);
}
?>