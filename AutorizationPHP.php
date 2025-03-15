<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
header("Content-Type: application/json");

$host = "localhost";
$dbname = "web-chat";
$username = "root";
$password = "Paha91lot151010";

$rawData = file_get_contents("php://input");
file_put_contents("debug.log", date('Y-m-d H:i:s') . " " . $rawData . PHP_EOL, FILE_APPEND);

$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Пустой или неверный JSON", "raw" => $rawData]);
    exit();
}

if (isset($data["login"]) && !empty($data["login"])) {
    $login = $data["login"];

    try {
        $pdo = new PDO("mysql:host=$host;port=3308;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :login");
        $stmt->bindParam(":login", $login, PDO::PARAM_STR);
        $stmt->execute();

        $exists = $stmt->fetchColumn() > 0;
        echo json_encode(["exists" => $exists]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "error" => "Ошибка подключения к базе данных или выполнения запроса",
            "pdo_error" => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Логин не указан"]);
}
?>
