<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Method: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: ');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";

include "contacts.php";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn -> connect_error) 
{
    http_response_code(500);    // 500 - Connection Failed
    echo json_encode(["message" => "Database connection failed!"]);
    // exit();
}

// Get response method
$reqMethod = $_SERVER["REQUEST_METHOD"];

// Retrieve user login input as JSON object
$data = json_decode(file_get_contents("php://input"), true);

switch ($reqMethod)
{
    // ================== //
    // GET - Read - Login
    // ================== //

    case 'GET':
        // Get data from JSON
        $username = $data['username'];
        $password = $data['password'];

        // Prepare query, execute & get result from query
        $stmt = $conn -> prepare("SELECT * from users WHERE username = ? AND password_hash = ?");
        $stmt -> bind_param("ss", $username, $password);
        $stmt -> execute();
        $result = $stmt -> get_result();

        // Retrieve ID from query result, if exists
        if ($row = $result -> fetch_assoc()) 
        {
            // Output the user ID
            $user_id = $row['id'];
            echo "The user ID is: " . $id;
        }
        else 
        {
            // Display message
            echo "Incorrect username or password.";
        }
        
        //echo "GET Successful";
        break;
        
    // ====================== //
    // POST - Create - Signup
    // ====================== //

    case 'POST':
        // Get data from JSON
        $username = $data['username'];
        $password = $data['password'];
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];

        // Check for existing entry with matching username
        $stmt = $conn -> prepare("SELECT * from users WHERE username = ?");
        $stmt -> bind_param("s", $username);
        $stmt -> execute();
        $result = $stmt -> get_result();

        // If no existing matching username found, perform insert into database
        if ($result->num_rows == 0)
        {
            $stmt = $conn -> prepare("INSERT INTO users (first_name, last_name, username, password_hash, email) VALUES (?,?,?,?,?)");
            $stmt -> bind_param("sssss", $first_name, $last_name, $username, $password, $email);
            $stmt -> execute();
            $stmt -> close();

            // Display message + new user ID to add information
            echo "New account added successfully! {User ID: " . $id => $conn->insert_id . "}";
        }
        else 
        {
            // Display message
            echo "Username is taken!";
        }
        break;
    
    $conn -> close();

}

?>
