<?php
namespace Model;

use Config\Database;

class Contact
{
    private $conn;
    private $table_name = "contact_form";

    public $fullname;
    public $email;
    public $phone;
    public $interest_complaints; 
    public $message; 

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    
   public function insertContactForm()
{
    
    // Insert query (ensure status is included)
    $query = "INSERT INTO " . $this->table_name . " (fullname, email, phone, interest_complaints, message) 
              VALUES (:fullname, :email, :phone, :interest_complaints, :message)";
    $stmt = $this->conn->prepare($query);

    // Clean input data
    $this->fullname = htmlspecialchars(strip_tags($this->fullname));
    $this->email = htmlspecialchars(strip_tags($this->email));
    $this->phone = htmlspecialchars(strip_tags($this->phone));
    $this->interest_complaints = htmlspecialchars(strip_tags($this->interest_complaints));
    $this->message = htmlspecialchars(strip_tags($this->message));
   
  
    // Bind values
    $stmt->bindParam(":fullname", $this->fullname);
    $stmt->bindParam(":email", $this->email);
    $stmt->bindParam(":phone", $this->phone);
    $stmt->bindParam(":interest_complaints", $this->interest_complaints);
    $stmt->bindParam(":message", $this->message);
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
    $subject = "Message Received - Runnix Africa";
    $formattedName = ucwords(strtolower($this->fullname)); // Capitalize first letters

    $message = "
        <html>
        <head>
            <title>Message Confirmation</title>
        </head>
        <body>
            <h2>Hello, {$formattedName}!</h2>
            <p>Thank you for contacting Runnix Africa.</p>
            <p>We have received your message and our team will get back to you as soon as possible.</p>
            <p>We appreciate your interest and look forward to assisting you.</p>
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
