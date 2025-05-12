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
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

define('COOKIE_SECRET', '12345678');

function verifyCookie($name) {
    error_log("Все ключи куков:".print_r(array_keys($_COOKIE), true));

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
    
    $stmt = $pdo->prepare("
        SELECT m.message_id, m.message_text, m.message_time, m.user_id, u.username, u.position
        FROM messages m
        JOIN users u ON m.user_id = u.`index`
        ORDER BY m.message_time DESC
        LIMIT 50
    ");
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "messages" => $messages,
        "currentUserId" => $userData['id']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>