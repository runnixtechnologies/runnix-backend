<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';      // Your custom CORS config
// Define the log file path
define('ERROR_LOG_PATH', __DIR__ . '/../config/errors.log'); // Update the path as necessary

// Function to log errors to the errors.log file
function logError($message) {
    error_log($message . "\n", 3, ERROR_LOG_PATH); // Log to the errors.log file
}

// Simulating an error for demonstration
try {
    // Database connection setup
    $servername = "localhost";
    $username = "u232647434_user";
    $password = "#Uti*odpl4B8";
    $dbname = "u232647434_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"));

        // Check if required fields are set
        if (!isset($data->fullname) || !isset($data->email) || !isset($data->phone) || !isset($data->interest_complaints) || !isset($data->message)) {
            throw new Exception("Missing required fields.");
        }

        // Process the data
        $fullname = $data->fullname;
        $email = $data->email;
        $phone = $data->phone;
        $interest_complaints = implode(", ", $data->interest_complaints); // Convert array to a string
        $message = $data->message;

        // Prepare the SQL query to insert the data
        $sql = "INSERT INTO contact_form (fullname, email, phone, interest_complaints, message) 
                VALUES ('$fullname', '$email', '$phone', '$interest_complaints', '$message')";

        // Execute the query
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["message" => "Form submitted successfully."]);
        } else {
            throw new Exception("Error: " . $sql . "<br>" . $conn->error);
        }

        // Close the connection
        $conn->close();
    } else {
        throw new Exception("Invalid request method. Only POST is allowed.");
    }
} catch (Exception $e) {
    // Log error message to errors.log
    logError("Error occurred: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
