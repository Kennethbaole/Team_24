<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: ');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";


$conn = new mysqli($host, $username, $password, $db_name);

if ($conn -> connect_error) 
{
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed!"]);
    // exit();
}

$reqMethod = $_SERVER["REQUEST_METHOD"];

$data = json_decode(file_get_contents("php://input"), true);

switch ($reqMethod)
{
    case 'POST':
        $user_id = $data['user_id'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $phone = $data['phone'];
        $email = $data['email'];

        $stmt = $conn->prepare("SELECT * FROM contacts WHERE user_id = ? AND first_name = ? AND last_name = ?");
        $stmt -> bind_param("sss", $user_id, $first_name, $last_name);
        $stmt -> execute();
        $result = $stmt -> get_result();

        if ($result->num_rows > 0)
        {
            http_response_code(409);
            echo "User is already in contacts";
            $stmt->close();
            break;
        }

        $stmt = $conn->prepare("INSERT INTO contacts (user_id, first_name, last_name, phone, email) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $user_id, $first_name, $last_name, $phone, $email);
        $stmt->execute();

        http_response_code(200);
        echo "Contact added successfully";
        $stmt->close();
        break;

    case 'GET':
        //
        break;
    
    case 'PUT':
        $user_id = $data['user_id'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];    
        $phone = $data['phone'];
        $email = $data['email'];

        $stmt = $conn->prepare("UPDATE contacts SET phone = ?, email = ? WHERE user_id = ? AND first_name = ? AND last_name = ?");
        $stmt->bind_param("sssss", $user_id, $first_name, $last_name, $phone, $email);
        
        if (!$stmt->execute()) 
        {
            http_response_code(500);
            echo "Update failed";
            $stmt->close();
            break;
        }

        if ($stmt->affected_rows === 0) 
        {
            http_response_code(404);
            echo "No contact found";
            $stmt->close();
            break;
        }

        http_response_code(200);
        echo "Contact updated successfully";
        $stmt->close();

        break;
    
    case ('DELETE'):
        $user_id = $data['user_id'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];    

        $stmt = $conn->prepare("DELETE FROM contacts WHERE user_id = ? AND first_name = ? AND last_name = ?");
        $stmt->bind_param("sss", $user_id, $first_name, $last_name);

        if (!$stmt->execute()) 
        {
            http_response_code(500);
            echo "Delete failed";
            $stmt->close();
            break;
        }

        if ($stmt->affected_rows === 0) 
        {
            http_response_code(404);
            echo "No contact found";
            $stmt->close();
            break;
        }
        
        break;
    
    default:
        $data = [
            'status' => 405,
            'message' => $requestMethod. 'Method Not Allowed',
        ];
        header("HTTP/1.0 405 Method Not Allowed");
        echo json_encode($data);
        break;
}

$conn->close();

?>
