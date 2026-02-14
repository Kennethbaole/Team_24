<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";

// Test require_once statement to avoid establishing another connection
// Allow API to fetch the right contacts database from the user_id var in users.php
// Discard if test fails
//require_once 'users.php';

$conn = new mysqli($host, $username, $password, $db_name);

$reqMethod = $_SERVER["REQUEST_METHOD"];

// Handle pre-flight
if ($reqMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
// $user_id = $id; // user_id in contacts table = id in users table

switch ($reqMethod)
{
    // ========================= //
    // POST - Create - Add Entry
    // ========================= //

    case 'POST':

        $searchCount = 0;       // # of results found
        $searchResults = [];    // result array

        // Trim any leading/trailing whitespaces
        $user_id = $data["user_id"];
        $first_name = isset($data['first_name']) ? trim($data['first_name']) : null;
        $last_name = isset($data['last_name']) ? trim($data['last_name']) : null;
        $phone = isset($data['phone']) ? trim($data['phone']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;

        // Search for existing entries that matches an info
        if ($first_name && $last_name)
        {
            $stmt = $conn->prepare("SELECT * FROM contacts WHERE user_id = ? AND first_name = ? AND last_name = ?");
            
            if (!$stmt) 
            {
                http_response_code(500);
                echo json_encode(["success"=>false, "error"=>"Prepare failed", "details"=>$conn->error]);
                break;
            }
            
            $stmt -> bind_param("iss", $user_id, $first_name, $last_name);
            $stmt -> execute();
            
            // Find any matches
            $stmt -> store_result();
            if ($stmt->num_rows > 0) 
            {
                http_response_code(409);
                echo json_encode(["success"=>false, "message"=>"User is already in contacts"]);
                $stmt->close();
                break; 
            }

            // No matching entries found!
            else
            {
                $errors = [];
                if ($phone || $email)
                {
                    /*
                    // Validate phone number & email first!
                    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) // Email must have @"domain"."abc" tail format
                    {
                        $errors[] = "Invalid email format!";
                    }
                    if ($phone && !preg_match("/^\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$/", $phone))    // Allow multiple phone number formats, i.e. (xxx) xxx-xxxx, xxx-xxx-xxxx,xxxxxxxxxx, etc.
                    {       
                            $errors[] = "Phone number must be 10 digits!";
                    }

                    // Display format error messages until user fixes them
                    if (!empty($errors))
                    {
                        http_response_code(422);    // 422 - Unprocessable Entity
                        echo json_encode(["Success" => false, "Errors" => $errors]);
                        exit();
                    }
                    */

                    // Phone number and/or email present and validated = good to add!
                    $stmt = $conn->prepare("INSERT INTO contacts (user_id, first_name, last_name, phone, email) VALUES (?,?,?,?,?)");
                    if (!$stmt) {
                        http_response_code(500);
                        echo json_encode([
                            "success" => false,
                            "error" => "Prepare failed",
                            "details" => $conn->error
                        ]);
                        break;
                    }
                    
                    $stmt->bind_param("issss", $user_id, $first_name, $last_name, $phone, $email);
                    $stmt->execute();
                
                    http_response_code(201);    // 201 - Success
                    echo "Contact added successfully";
                    $stmt->close();
                }

                // No phone number and email added!
                else
                {
                    $errors[] = "Phone number or email required!";
                    http_response_code(422);
                    echo json_encode(["success" => false, "errors" => $errors]);
                    //exit(); 
                }

                break;
            }
        }

        // No name added!
        else
        {
            http_response_code(400);    // 400 - Bad Request
            echo "Full name required!";
            //exit();
        }

        break;


    // =========================== //
    // GET - Read - Search Entries
    // =========================== //

    case 'GET':
        $searchCount = 0;       // # of results found
        $searchResults = [];    // result array

        // Trim any leading/trailing whitespaces
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id']
                : (isset($data['user_id']) ? (int)$data['user_id'] : 0);

        if (!$user_id) 
        {
            http_response_code(400);
            echo json_encode(["success"=>false, "error"=>"user_id is required"]);
            break;
        }

        // Get search pattern (one string)
        $search = isset($_GET['search']) ? trim($_GET['search'])
                : (isset($data['search']) ? trim($data['search']) : '');

        // If search is a nonempty string
        if ($search !== '')
        {
            // Search w/ partial matching
            $stmt = $conn -> prepare("SELECT id, first_name, last_name, phone, email 
                                    FROM contacts 
                                    WHERE user_id = ? AND (first_name LIKE ? OR last_name LIKE ?)");
            
            if (!$stmt)
            {
                http_response_code(500);
                echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
                break;
            }

            // Search through both first and last names
            $pattern = '%' . $search . '%';
            $stmt -> bind_param("iss", $user_id, $pattern, $pattern);
            $stmt -> execute();

            $stmt->bind_result($id, $fn, $ln, $ph, $em);

            // Display matches
            while ($stmt->fetch())
            {
                $searchResults[] = [
                        "id" => $id,
                        "first_name" => $fn,
                        "last_name" => $ln,
                        "phone" => $ph,
                        "email" => $em
                ];
                $searchCount++;
            }
        } 
        // Get all contact infos if pattern is empty
        else
        {
            $stmt = $conn -> prepare("SELECT id, first_name, last_name, phone, email 
                                    from contacts 
                                    WHERE user_id = ?");
            
            if (!$stmt)
            {
                http_response_code(500);
                echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
                break;
            }

            $stmt->bind_param("i", $user_id);
            $stmt -> execute();
            $stmt->bind_result($id, $fn, $ln, $ph, $em);

            while ($stmt->fetch())
            {
                $searchResults[] = [
                        "id" => $id,
                        "first_name" => $fn,
                        "last_name" => $ln,
                        "phone" => $ph,
                        "email" => $em
                ];
                $searchCount++;
            }
        }

        // Close connection
        $stmt->close();
        
        http_response_code(200);
        echo json_encode(["success" => true, "count" => $searchCount, "results" => $searchResults]);
        
        break;
    
    // ========================= //
    // PUT - Update - Edit Entry
    // ========================= //

    case 'PUT':
        $id = $data["id"];      // Index of edited entry in contacts table
        $first_name = trim($data['first_name']);
        $last_name = trim($data['last_name']);    
        $phone = trim($data['phone']);
        $email = trim($data['email']);

        $stmt = $conn->prepare("UPDATE contacts SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE user_id = ? AND id = ?");
        $stmt->bind_param("ssssii",  $first_name, $last_name, $phone, $email, $user_id, $id);
        
        if (!$stmt->execute()) 
        {
            http_response_code(500);
            echo "Update failed";
            // break;
        }
        else if ($stmt->affected_rows === 0) 
        {
            http_response_code(404);
            echo "No contact found";
            // break;
        }
        else
        {
            http_response_code(200);
            echo "Contact updated successfully";
        }

        // Close connection
        $stmt->close();

        break;
    
    // ============================== //
    // DELETE - Delete - Delete Entry
    // ============================== //

    case 'DELETE':
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
