<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["message" => "Invalid request method"]);
    exit();
}

/* LOGIN */
if (isset($data['username']) && isset($data['password']) && !isset($data['first_name'])) {

    $username = $data['username'];
    $password = $data['password'];

    $stmt = $conn->prepare("SELECT id, password_hash, first_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (password_verify($password, $row['password_hash'])) {
            echo json_encode([
                "success" => true,
                "user_id" => $row['id'],
                "first_name" => $row['first_name']
            ]);
        } else {
            echo json_encode(["message" => "Incorrect username or password"]);
        }

    } else {
        echo json_encode(["message" => "Incorrect username or password"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

/* REGISTER */
if (
    isset($data['username']) &&
    isset($data['password']) &&
    isset($data['first_name']) &&
    isset($data['last_name']) &&
    isset($data['email'])
) {

    $username = $data['username'];
    $password = $data['password'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $email = $data['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["message" => "Username is taken"]);
        $stmt->close();
        $conn->close();
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password_hash, email) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $username, $hashedPassword, $email);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "user_id" => $conn->insert_id
    ]);

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(["message" => "Invalid request"]);

$conn->close();
?>
