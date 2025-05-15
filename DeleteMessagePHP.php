<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '.web-chat-tca4.vercel.app',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

$allowedOrigins = ['https://web-chat-tca4.vercel.app','http://localhost:80'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://web-chat-tca4.vercel.app");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

define('COOKIE_SECRET', '12345678');

function verifyCookie($name) {
    if (!isset($_COOKIE[$name])) return false;
    $data = json_decode($_COOKIE['UserAuth'], true);
    if (!isset($data['signature'])) return false;
    
    $signature = $data['signature'];
    unset($data['signature']);
    
    return hash_hmac('sha256', json_encode($data), COOKIE_SECRET) === $signature;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!verifyCookie('UserAuth')) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$userData = json_decode($_COOKIE['UserAuth'], true);
error_log("Данные пользователя из куки: " . print_r($userData, true));

$host = "localhost";
$dbname = "web-chat";
$username = "root";
$password = "Paha91lot151010";
$port = 3308;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = json_decode(file_get_contents("php://input"), true);
    $message_id = $input['message_id'] ?? null;

    if (!$message_id) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid request"]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT user_id FROM messages WHERE message_id = ?");
    $stmt->execute([$message_id]);
    $messageOwner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$messageOwner || $messageOwner['user_id'] !== $userData['id']) {
        http_response_code(403);
        echo json_encode(["error" => "Permission denied"]);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM messages WHERE message_id = ?");
    $stmt->execute([$message_id]);

    echo json_encode(["success" => true, "message" => "Message deleted successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
