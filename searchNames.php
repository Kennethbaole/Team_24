<?php

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
        "SELECT id, first_name, last_name, phone, email, address, created_at /* returns these columns for each matching row */
        FROM contacts /* pull data from contacts table */
        WHERE user_id = ? /* only returns contacts that belongs to a specific user -> ? will be replaced with current user's ID */
        AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)" /* Second filter, match search term in at least one of these fields */
    );
    // Search through first and last names, allowing partial matches
    $searchPattern = "%" . $search . "%"
    // $namePattern = "%" . $inData["search"] . "%";

    $stmt->bind_param("ss", $namePattern, $namePattern);
    $stmt->execute();

    // Get set of results
    $result = $stmt->get_result();

    // Loop through results
    while($row = $stmt->fetch_assoc())
    {
        if ($searchCount > 0)
        {
            $searchResults .= ",";
        }

        $searchCount++;
        $searchResults.= '"' . $row["first_name"] . $row["last_name" . '"'];
    }

    if ($searchCount == 0)
    {
        returnWithError("No Records Found")
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
    $retVal = '{"results":[' . $searchResults . '],"errpr":""}';
    sendResultInfoAsJson($retVal);
}
?>