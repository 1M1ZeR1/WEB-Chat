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

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Метод не разрешен"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['messageText'], $data['messageTime'])) {
    http_response_code(400);
    echo json_encode(["error" => "Неверные данные"]);
    exit;
}

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Не авторизован"]);
    exit;
}

$host = "localhost";
$dbname = "web-chat";
$username = "root";
$password = "Paha91lot151010";
$port = 3308;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $messageTime = date("Y-m-d H:i:s", strtotime($data['messageTime']));
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (user_id, message_text, message_time) 
        VALUES (:user_id, :message_text, :message_time)
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user']['id'],
        ':message_text' => $data['messageText'],
        ':message_time' => $messageTime
    ]);
    
    echo json_encode([
        "success" => true,
        "messageId" => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Ошибка базы данных: " . $e->getMessage()]);
}
?>