<?php
namespace Model;

use Config\Database;

class SupportContact
{
    private $conn;
    private $table_name = "support_contact_form";

    public $fullname;
    public $email;
    public $phone;
    public $interest_complaints; 
    public $message;
    public $support_pin;
    public $user_id;
    public $is_verified;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Insert support contact form with pin verification
     */
    public function insertSupportContactForm()
    {
        // First verify the support pin
        $userModel = new User();
        $user = $userModel->getUserBySupportPin($this->support_pin);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid support pin. Please check your support pin and try again.'
            ];
        }

        // Insert query with support pin verification
        $query = "INSERT INTO " . $this->table_name . " 
                  (fullname, email, phone, interest_complaints, message, support_pin, user_id, is_verified, created_at) 
                  VALUES (:fullname, :email, :phone, :interest_complaints, :message, :support_pin, :user_id, 1, NOW())";
        
        $stmt = $this->conn->prepare($query);

        // Clean input data
        $this->fullname = htmlspecialchars(strip_tags($this->fullname));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->interest_complaints = htmlspecialchars(strip_tags($this->interest_complaints));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->support_pin = htmlspecialchars(strip_tags($this->support_pin));
        $this->user_id = $user['id'];

        // Bind values
        $stmt->bindParam(":fullname", $this->fullname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":interest_complaints", $this->interest_complaints);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":support_pin", $this->support_pin);
        $stmt->bindParam(":user_id", $this->user_id);

        // Execute query
        if ($stmt->execute()) {
            $this->sendConfirmationEmail($user);
            return [
                'success' => true,
                'message' => 'Support request submitted successfully. We will contact you soon.',
                'user_info' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ]
            ];
        }

        // Log SQL errors for debugging
        error_log("SQL Error: " . implode(", ", $stmt->errorInfo()));
        return [
            'success' => false,
            'message' => 'Failed to submit support request. Please try again.'
        ];
    }

    /**
     * Verify support pin without submitting form
     */
    public function verifySupportPin($supportPin, $userIdentifier = null)
    {
        $userModel = new User();
        $user = $userModel->getUserBySupportPin($supportPin);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid support pin. Please check your support pin and try again.'
            ];
        }

        // If user identifier is provided, verify it matches
        if ($userIdentifier) {
            $identifierMatch = false;
            if (strtolower(trim($userIdentifier)) === strtolower(trim($user['email']))) {
                $identifierMatch = true;
            } elseif (trim($userIdentifier) === trim($user['phone'])) {
                $identifierMatch = true;
            }

            if (!$identifierMatch) {
                return [
                    'success' => false,
                    'message' => 'Support pin does not match the provided user information.'
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Support pin verified successfully',
            'user_info' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'is_verified' => $user['is_verified'],
                'status' => $user['status']
            ]
        ];
    }

    /**
     * Send confirmation email with support pin info
     */
    private function sendConfirmationEmail($user)
    {
        $to = $this->email;
        $subject = "Support Request Received - Runnix Africa";
        $formattedName = ucwords(strtolower($this->fullname));

        $message = "
            <html>
            <head>
                <title>Support Request Confirmation</title>
            </head>
            <body>
                <h2>Hello, {$formattedName}!</h2>
                <p>Thank you for contacting Runnix Africa Support.</p>
                <p>We have received your support request and our team will get back to you as soon as possible.</p>
                <p><strong>Your Support Pin:</strong> {$this->support_pin}</p>
                <p><strong>Account Information:</strong></p>
                <ul>
                    <li>Email: {$user['email']}</li>
                    <li>Phone: {$user['phone']}</li>
                    <li>Role: " . ucfirst($user['role']) . "</li>
                </ul>
                <p>Please keep your support pin safe as it will be required for any follow-up communications.</p>
                <br>
                <p>Best Regards,</p>
                <p><strong>Runnix Africa Support Team</strong></p>
            </body>
            </html>
        ";

        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: support@runnix.africa" . "\r\n";
        $headers .= "Reply-To: support@runnix.africa" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send email
        mail($to, $subject, $message, $headers);
    }
}
