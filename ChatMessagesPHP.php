<?php
header("Access-Control-Allow-Origin: https://web-chat-tca4.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Метод не разрешен"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['messageText'], $data['messageTime'])) {
    http_response_code(400);
    echo json_encode(["error" => "Неверные данные"]);
    exit();
}

define('COOKIE_SECRET', '12345678');
function verifyCookie($name) {
    if (!isset($_COOKIE[$name])) return false;
    $data = json_decode($_COOKIE[$name], true);
    if (!isset($data['signature'])) return false;
    $signature = $data['signature'];
    unset($data['signature']);
    return (hash_hmac('sha256', json_encode($data), COOKIE_SECRET) === $signature) ? $data : false;
}

$userData = verifyCookie('UserAuth');
if (!$userData || !isset($userData['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Не авторизован"]);
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
    
    $messageTime = date("Y-m-d H:i:s", strtotime($data['messageTime']));
    
    $stmt = $pdo->prepare("INSERT INTO messages (user_id, message_text, message_time) VALUES (:user_id, :message_text, :message_time)");
    $stmt->execute([
        ':user_id'      => $userData['id'],
        ':message_text' => $data['messageText'],
        ':message_time' => $messageTime
    ]);
    
    echo json_encode(["success" => true, "messageId" => $pdo->lastInsertId()]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Ошибка базы данных: " . $e->getMessage()]);
}
?>
