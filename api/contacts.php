<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";


$conn = new mysqli($host, $username, $password, $db_name);

$reqMethod = $_SERVER["REQUEST_METHOD"];

// Handle pre-flight
if ($reqMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// POST, GET, PUT, or DELETE for CRUD operations
switch ($reqMethod)
{
    // ========================= //
    // POST - Create - Add Entry
    // ========================= //

    case 'POST':

        $searchCount = 0;       // # of results found
        $searchResults = [];    // result array

        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0; 
        // Missing or empty strings get set to null
        $first_name = trim($data['first_name'] ?? '') ?: null;
        $last_name = trim($data['last_name'] ?? '') ?: null;
        $phone = trim($data['phone'] ?? '') ?: null;
        $email = trim($data['email'] ?? '') ?: null;
        $address = trim($data['address'] ?? '') ?: null;

        // Prepare contact insertion if full name is provided
        if ($first_name && $last_name)
        {
            // Look for any contact with the same full name (No duplicates)
            $stmt = $conn->prepare("SELECT * FROM contacts WHERE user_id = ? AND first_name = ? AND last_name = ?");
            if (!$stmt) 
            {
                http_response_code(500);    // Server error
                echo json_encode(["success"=>false, "error"=>"Prepare failed", "details"=>$conn->error]);
                break;
            }
            
            $stmt->bind_param("iss", $user_id, $first_name, $last_name);
            $stmt->execute();
            
            // Find any matches
            $stmt->store_result();
            if ($stmt->num_rows > 0) 
            {
                http_response_code(409);    // Duplicate entry
                echo json_encode(["success"=>false, "message"=>"User is already in contacts"]);
                $stmt->close();
                break; 
            }

            // No matching entries found! Prepare insertion
            else
            {
                if ($phone || $email || $address)
                {
                    // A phone number, email, or address present and validated = good to add!
                    $stmt = $conn->prepare("INSERT INTO contacts (user_id, first_name, last_name, phone, email, address) VALUES (?,?,?,?,?, ?)");
                    if (!$stmt) {
                        http_response_code(500);
                        echo json_encode([
                            "success" => false,
                            "error" => "Prepare failed",
                            "details" => $conn->error
                        ]);
                        break;
                    }
                    
                    $stmt->bind_param("isssss", $user_id, $first_name, $last_name, $phone, $email, $address);
                    $stmt->execute();
                
                    http_response_code(201);    // 201 - Creation successful
                    echo json_encode(["success" => true, "message" => "Contact added successfully"]);
                    $stmt->close();
                }

                // No phone number, email, or address added!
                else
                {
                    http_response_code(422);    // 422 - Missing info
                    echo json_encode(["success" => false, "errors" => "Phone number, email, or address required!"]);
                }

                break;
            }
        }

        // No name added!
        else
        {
            http_response_code(422);    // 422 - Missing info
            echo json_encode(["success" => false, "errors" => "Full name required!"]);
        }

        break;


    // =========================== //
    // GET - Read - Search Entries
    // =========================== //

    case 'GET':
        $searchCount = 0;       // # of results found
        $searchResults = [];    // result array

        // If user_id not found, set it to 0 (false)
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id']
                : (isset($data['user_id']) ? (int)$data['user_id'] : 0);

        if (!$user_id) 
        {
            http_response_code(400);    // Bad request
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
            $stmt = $conn -> prepare("SELECT id, first_name, last_name, phone, email, address
                                    FROM contacts 
                                    WHERE user_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR phone like ? OR email like ? or address like ?)");
            
            if (!$stmt)
            {
                http_response_code(500);    // Server error
                echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
                break;
            }

            // Search through first name, last name, phone, email, and address
            $pattern = '%' . $search . '%';
            $stmt -> bind_param("isssss", $user_id, $pattern, $pattern, $pattern, $pattern, $pattern);
            if (!$stmt->execute()) 
            {
                http_response_code(500);    // Server error
                echo json_encode(["success"=>false, "error"=>"Query failed", "details"=>$stmt->error]);
                $stmt->close();
                break;
            }

            $stmt->bind_result($id, $fn, $ln, $ph, $em, $ad);

            // Display and count matches
            while ($stmt->fetch())
            {
                $searchResults[] = [
                        "id" => $id,
                        "first_name" => $fn,
                        "last_name" => $ln,
                        "phone" => $ph,
                        "email" => $em,
                        "address" => $ad
                ];
                $searchCount++;
            }

            // Close prepared statement
            $stmt->close();
        } 
        // Get all contact infos if pattern is empty
        else
        {
            $stmt = $conn -> prepare("SELECT id, first_name, last_name, phone, email, address 
                                    from contacts 
                                    WHERE user_id = ?");
            
            if (!$stmt)
            {
                http_response_code(500);    // Server error
                echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
                break;
            }

            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) 
            {
                http_response_code(500);    // Server error
                echo json_encode(["success"=>false, "error"=>"Query failed", "details"=>$stmt->error]);
                $stmt->close();
                break;
            }

            $stmt->bind_result($id, $fn, $ln, $ph, $em, $ad);

            // Display and count every contact
            while ($stmt->fetch())
            {
                $searchResults[] = [
                        "id" => $id,
                        "first_name" => $fn,
                        "last_name" => $ln,
                        "phone" => $ph,
                        "email" => $em,
                        "address" => $ad
                ];
                $searchCount++;
            }

            // Close prepared statement
            $stmt->close();
        }
        
        http_response_code(200);    // Query success
        echo json_encode(["success" => true, "count" => $searchCount, "results" => $searchResults]);
        
        break;
    
    // ========================= //
    // PUT - Update - Edit Entry
    // ========================= //

    case 'PUT':
        // If ids not found, set them to 0 (false)
        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0; 
        $id = isset($data['id']) ? (int)$data['id'] : 0; // Index of the contact to edit
        // Missing or empty strings get set to null
        $first_name = trim($data['first_name'] ?? '') ?: null;
        $last_name = trim($data['last_name'] ?? '') ?: null;
        $phone = trim($data['phone'] ?? '') ?: null;
        $email = trim($data['email'] ?? '') ?: null;
        $address = trim($data['address'] ?? '') ?: null;

        // If either a user id or contact id is missing
        if (!$user_id || !$id)
        {
            http_response_code(400);    // Bad request
            echo json_encode(["success" => false, "error" => "User ID and Contact ID are required"]);
            break;
        }

        // Check if the contact actually exists
        // Looks for a single contact
        $exists = $conn->prepare("SELECT 1
                                FROM contacts
                                WHERE user_id = ? AND id = ?
                                LIMIT 1");
        
        if (!$exists)
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" =>"Prepare failed", "details" => $conn->error]);
            break;
        }

        $exists->bind_param("ii", $user_id, $id);
        if (!$exists->execute())
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Existence check failed", "details" => $exists->error]);
            $exists->close();
            break;
        }
        
        $exists->store_result();

        // If contact does not exist, theres nothing to update
        if ($exists->num_rows === 0) 
        {
            http_response_code(404);    // Resource not found
            echo json_encode(["success" => false, "error" => "Contact not found"]);
            $exists->close();
            break;
        }

        // Close statement
        $exists->close();

        // If both first and last name are not inputted
        if ($first_name === null || $last_name === null)
        {
            http_response_code(400);    // Bad request
            echo json_encode(["success" => false, "error" => "Full name required!"]);
            break;
        }

        // If neither a phone number, email, nor address is provided
        if ($phone === null && $email === null && $address === null)
        {
            http_response_code(422);    // Missing info
            echo json_encode(["success" => false, "errors" => "Phone number, email, or address required!"]);
            break;
        }

        // Check if first name and last name is already taken
        // Excludes the contact being updated 
        $stmt = $conn -> prepare("SELECT 1
                                FROM contacts
                                WHERE user_id = ? AND first_name = ? AND last_name = ? AND id <> ?
                                LIMIT 1");

        if (!$stmt) 
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
            break;
        }

        $stmt->bind_param("issi", $user_id, $first_name, $last_name, $id);
        $stmt->execute();
        $stmt->store_result();

        // The new name is already taken by another contact, prevent update
        if ($stmt->num_rows > 0) 
        {
            http_response_code(409);    // Duplicate name
            echo json_encode(["success" => false, "message" => "A contact with this first and last name already exists"]);
            $stmt->close();
            break;
        }
        
        // Close prepared statement
        $stmt->close();

        // Update the contact
        $stmt  = $conn->prepare("UPDATE contacts
                                SET first_name = ?, last_name = ?, phone = ?, email = ?, address = ?
                                WHERE user_id = ? AND id = ?");

        if (!$stmt) 
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
            break;
        }

        $stmt->bind_param("sssssii", $first_name, $last_name, $phone, $email, $address, $user_id, $id);

        if (!$stmt->execute()) 
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Update failed", "details" => $stmt->error]);
            $stmt->close();
            break;
        }

        // affected_rows can be 0 if values are unchanged; treat as success
        http_response_code(200);
        echo json_encode(["success" => true,
                        "message" => ($stmt->affected_rows > 0) ? "Contact updated successfully" : "No changes (already up to date)"]);

        // Close statement
        $stmt->close();
        break;
    
    // ============================== //
    // DELETE - Delete - Delete Entry
    // ============================== //

    case 'DELETE':
        // If ids not found, set them to 0 (false)
        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0; 
        $id = isset($data['id']) ? (int)$data['id'] : 0; // Index of the contact to delete   

        // If either a user id or contact id is missing
        if (!$user_id || !$id)
        {
            http_response_code(400);    // Bad request
            echo json_encode(["success" => false, "error" => "User ID and Contact ID are required"]);
            break;
        }

        // Check if the contact actually exists
        $exists = $conn->prepare("SELECT 1
                                FROM contacts
                                WHERE user_id = ? AND id = ?
                                LIMIT 1");
        
        if (!$exists)
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
            break;
        }

        $exists->bind_param("ii", $user_id, $id);
        if (!$exists->execute())
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Existence check failed", "details" => $exists->error]);
            $exists->close();
            break;
        }

        $exists->store_result();

        // If contact does not exist, theres nothing to delete
        if ($exists->num_rows === 0) 
        {
            http_response_code(404);    // Resource not found
            echo json_encode(["success" => false, "message" => "Contact not found"]);
            $exists->close();
            break;
        }

        // Close statement
        $exists->close();

        // Delete contact
        $stmt = $conn->prepare("DELETE FROM contacts
                                WHERE user_id = ? AND id = ?");
        
        if (!$stmt) 
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Prepare failed", "details" => $conn->error]);
            break;
        }

        $stmt->bind_param("ii", $user_id, $id);

        if (!$stmt->execute())
        {
            http_response_code(500);    // Server error
            echo json_encode(["success" => false, "error" => "Delete failed", "details" => $stmt->error]);
            $stmt->close();
            break;
        }

        $stmt->close();

        http_response_code(200);    // Delete success
        echo json_encode(["success" => true, "message" => "Contact deleted successfully"]);

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

// Close connection
$conn->close();

?>
