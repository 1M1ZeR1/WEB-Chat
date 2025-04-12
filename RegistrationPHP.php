<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


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
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Пустой или неверный JSON", "raw" => $rawData]);
    exit();
}

if (isset($data["login"]) && !empty($data["login"])) {
    $login = $data["login"];
    $userPassword = $data["password"];
    $position = $data["position"];
    $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
    try {
        $pdo = new PDO("mysql:host=$host;port=3308;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, position) VALUES (:login, :userPassword, :position)");
        $stmt->bindParam(":login", $login, PDO::PARAM_STR);
        $stmt->bindParam(":userPassword", $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(":position", $position, PDO::PARAM_STR);
        if ($stmt->execute()) {
            $userId = $pdo->lastInsertId();
            $_SESSION["user"] = [
                "id"       => $userId,
                "login"    => $login,
                "position" => $position
            ];
            echo json_encode(["success" => true, "message" => "Пользователь добавлен", "redirect" => "chat.php"]);
        } else {
            echo json_encode(["success" => false, "message" => "Ошибка при добавлении пользователя"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Ошибка подключения к базе данных или выполнения запроса", "pdo_error" => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Логин не указан"]);
}
?>
