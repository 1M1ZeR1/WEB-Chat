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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
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
        "currentUserId" => $_SESSION['user']['id']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>