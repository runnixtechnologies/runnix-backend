<?php

namespace Controller;

use Model\Contact;
use Exception;

class ContactController
{
    private $contact;

    public function __construct()
    {
        $this->contact = new Contact();
    }

    public function handleFormSubmission($fullname, $email, $phone, $interest_complaints, $message)
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }
        
        // Set properties for the contact model
        $this->contact->fullname = $fullname;
        $this->contact->email = $email;
        $this->contact->phone = $phone;
        
        // Handle multiple interests/complaints as a string
        if (is_array($interest_complaints)) {
            $this->contact->interest_complaints = implode(', ', $interest_complaints);
        } else {
            $this->contact->interest_complaints = $interest_complaints;
        }
        
        $this->contact->message = $message;
        
        try {
            // Attempt to insert the contact form data
            if ($this->contact->insertContactForm()) {
                return json_encode(["message" => "Message sent successfully."]);
            } else {
                throw new Exception("Failed to insert data into the database.");
            }
        } catch (Exception $e) {
            // Catch any exceptions and log or return the error message
            error_log("Error: " . $e->getMessage());
            return json_encode(["message" => "An error occurred. Please try again later."]);
        }
    }
}

?>