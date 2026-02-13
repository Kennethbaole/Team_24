<?php

// cors headers 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = "localhost";
$username = "Team24";
$password = "databasekey";
$db_name = "contact_manager";

//Read incoming request data
$inData = getRequestInfo();

// Result variables
$searchResults = "";
$searchCount = 0;

$conn = new mysqli($host, $db_name, $username, $password);

    // If connection error (database failure)
if ($conn->connect_error)
{
    returnWithError($conn->connect_error);
}
else
{
    // SQL query statement 
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, phone, email, address, created_at 
        FROM contacts 
        WHERE user_id = ? 
        AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)" 
    );
    // Search through first and last names, allowing partial matches
    $searchPattern = "%" . $search . "%"
    // $namePattern = "%" . $inData["search"] . "%";

    $stmt->bind_param("ss", $user_id, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();

    // Get set of results
    $result = $stmt->get_result();

    // Loop through results
    while($row = $result->fetch_assoc())
    {
        if ($searchCount > 0)
        {
            $searchResults .= ",";
        }

        $searchCount++;
        $searchResults.= '"' . $row["first_name"] . $row["last_name"] . '"';
    }

    if ($searchCount == 0)
    {
        returnWithError("No Records Found");
    }
    else
    {
        returnWithInfo($searchResults);
    }

    $stmt->close();
    $conn->close();
}

function getRequestInfo()
{
    return json_decode(file_get_contents('php://input'),true);
}

function sendResultInfoAsJson($obj)
{
    header('Content-type: application/json');
    echo $obj;
}

function returnWithError($err)
{
    $retVal = '{"firstName":"","lastName":"","error":"' . $err . '"}';
    sendResultInfoAsJson($retVal);
}

function returnWithInfo($searchResults)
{
    $retVal = '{"results":[' . $searchResults . '],"error":""}';
    sendResultInfoAsJson($retVal);
}
?>