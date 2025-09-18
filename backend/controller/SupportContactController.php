<?php

namespace Controller;

use Model\SupportContact;
use Exception;

class SupportContactController
{
    private $supportContact;

    public function __construct()
    {
        $this->supportContact = new SupportContact();
    }

    /**
     * Handle support contact form submission with pin verification
     */
    public function handleSupportFormSubmission($fullname, $email, $phone, $interest_complaints, $message, $support_pin, $user_identifier = null)
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        // Validate support pin format (8 characters: 4 letters + 4 numbers)
        if (!$this->validateSupportPinFormat($support_pin)) {
            throw new Exception("Invalid support pin format. Support pin should be 8 characters (4 letters + 4 numbers).");
        }
        
        // Set properties for the support contact model
        $this->supportContact->fullname = $fullname;
        $this->supportContact->email = $email;
        $this->supportContact->phone = $phone;
        
        // Handle multiple interests/complaints as a string
        if (is_array($interest_complaints)) {
            $this->supportContact->interest_complaints = implode(', ', $interest_complaints);
        } else {
            $this->supportContact->interest_complaints = $interest_complaints;
        }
        
        $this->supportContact->message = $message;
        $this->supportContact->support_pin = $support_pin;
        
        try {
            // Attempt to insert the support contact form data
            $result = $this->supportContact->insertSupportContactForm();
            
            if ($result['success']) {
                return json_encode([
                    "status" => "success",
                    "message" => $result['message'],
                    "user_info" => $result['user_info']
                ]);
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            // Catch any exceptions and log or return the error message
            error_log("Support contact form error: " . $e->getMessage());
            return json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify support pin without submitting form
     */
    public function verifySupportPin($support_pin, $user_identifier = null)
    {
        // Validate support pin format
        if (!$this->validateSupportPinFormat($support_pin)) {
            return json_encode([
                "status" => "error",
                "message" => "Invalid support pin format. Support pin should be 8 characters (4 letters + 4 numbers)."
            ]);
        }

        try {
            $result = $this->supportContact->verifySupportPin($support_pin, $user_identifier);
            
            if ($result['success']) {
                return json_encode([
                    "status" => "success",
                    "message" => $result['message'],
                    "user_info" => $result['user_info']
                ]);
            } else {
                return json_encode([
                    "status" => "error",
                    "message" => $result['message']
                ]);
            }
        } catch (Exception $e) {
            error_log("Support pin verification error: " . $e->getMessage());
            return json_encode([
                "status" => "error",
                "message" => "An error occurred while verifying support pin. Please try again."
            ]);
        }
    }

    /**
     * Validate support pin format (8 characters: 4 letters + 4 numbers)
     */
    private function validateSupportPinFormat($supportPin)
    {
        // Check if pin is exactly 8 characters
        if (strlen($supportPin) !== 8) {
            return false;
        }

        // Check if first 4 characters are letters
        $letters = substr($supportPin, 0, 4);
        if (!ctype_alpha($letters)) {
            return false;
        }

        // Check if last 4 characters are numbers
        $numbers = substr($supportPin, 4, 4);
        if (!ctype_digit($numbers)) {
            return false;
        }

        return true;
    }
}
