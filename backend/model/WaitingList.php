<?php
namespace Model;

use Config\Database;

class WaitingList
{
    private $conn;
    private $table_name = "waiting_list";

    public $name;
    public $email;
    public $role;
    public $status; // Added status property

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function emailExists()
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind value
        $stmt->bindParam(":email", $this->email);

        // Execute query
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

   public function insertWaitingList()
{
    if ($this->emailExists()) {
        return false;  // Don't insert if email exists
    }

    // Insert query (ensure status is included)
    $query = "INSERT INTO " . $this->table_name . " (name, email, role, status) 
              VALUES (:name, :email, :role, :status)";
    $stmt = $this->conn->prepare($query);

    // Clean input data
    $this->name = htmlspecialchars(strip_tags($this->name));
    $this->email = htmlspecialchars(strip_tags($this->email));
    $this->role = htmlspecialchars(strip_tags($this->role));

    // If status is not set, default it to 1
    if (!isset($this->status) || empty($this->status)) {
        $this->status = 1;
    }

    // Bind values
    $stmt->bindParam(":name", $this->name);
    $stmt->bindParam(":email", $this->email);
    $stmt->bindParam(":role", $this->role);
    $stmt->bindParam(":status", $this->status, \PDO::PARAM_INT);

    // Execute query
    if ($stmt->execute()) {
        $this->sendConfirmationEmail();
        return true;
    }

    // Log SQL errors for debugging
    error_log("SQL Error: " . implode(", ", $stmt->errorInfo()));
    return false;
}


    private function sendConfirmationEmail()
    {
        $to = $this->email;
        $subject = "Welcome to Runnix Africa Waiting List";
        $formattedName = ucwords(strtolower($this->name)); // Capitalize first letters

        $message = "
            <html>
            <head>
                <title>Waiting List Confirmation</title>
            </head>
            <body>
             
                <h2>Welcome, {$formattedName}!</h2>
                <p>Thank you for joining the Runnix Africa waiting list.</p>
                <p>We will keep you updated on our latest updates and opportunities.</p>
                <p>Stay tuned!</p>
                <br>
                <p>Best Regards,</p>
                <p><strong>Runnix Africa Team</strong></p>
            </body>
            </html>
        ";

        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@runnix.africa" . "\r\n";
        $headers .= "Reply-To: no-reply@runnix.africa" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send email
        mail($to, $subject, $message, $headers);
    }
}
