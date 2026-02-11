<?php

$host = "localhost";
$db_name = "root";
$username = "Team24POOSD";
$password = "contact_manager";

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
        $stmt = $conn->prepare("select first_name and last_name from contacts where first_name like ? or last_name like ?");
        // Search through first and last names, allowing partial matches
        $namePattern = "%" . $inData["search"] . "%";
        $stmt->blind_param("ss", $namePattern, $namePatern);
        $stmt->execute();

        // Get set of results
        $result = $stmt->get_result();

        // Loop through results
        while($row = $stmt->fetch_assoc());
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