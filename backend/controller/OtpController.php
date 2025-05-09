<?php
namespace Controller;

use Model\Otp;

class OtpController
{
    private $termiiApiKey = "TLKCVBYRZyFFYYInnjIdPWOgfForjjZbEYgjIigNANxWYDaUJMyEFtuQpNPsNE"; // Replace with your Termii API Key
    private $smsSenderName = "Runnix"; // Registered Termii sender ID

    public function sendOtp($phone, $purpose = 'signup', $email = null, $user_id = null)
    {
        $otp = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
        $otpModel = new Otp();
        $otpModel->createOtp($user_id, $phone, $email, $otp, $purpose, $expires_at);
    
        $message = "Your Runnix Authentication PIN is $otp. It expires in 10 minutes.";
    
        $response = $this->sendViaTermii($phone, $message);
    
        if (!isset($response['code']) || $response['code'] != 'ok') {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to send OTP. Please try again later."];
        }
    
        return ["status" => "success", "message" => "OTP sent to $phone"];
    }
    

    private function sendViaTermii($phone, $message)
{
    $url = "https://v3.api.termii.com/api/sms/send";
    $payload = [
        //"to" => $phone,
        "to" => $phone,
        "from" => $this->smsSenderName,
        "sms" => $message,
        "type" => "plain",
        "channel" => "generic", // Try "dnd" for Nigerian numbers
        "api_key" => $this->termiiApiKey
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Enhanced logging
    error_log("Termii Request: " . json_encode($payload));
    error_log("Termii Response ($httpCode): " . $response);

    if ($response === false) {
        error_log('CURL Error: ' . curl_error($ch));
    } elseif ($httpCode !== 200) {
        error_log("Termii API Error: HTTP $httpCode");
    }

    curl_close($ch);
    return json_decode($response, true);
}

    public function verifyOtp($phone, $otp, $purpose = 'signup')
    {
        $otpModel = new Otp();
        $otpData = $otpModel->verifyOtp($phone, $otp, $purpose);

        if (!$otpData) {
            http_response_code(401); // Unauthorized
            return ["status" => "error", "message" => "Invalid or expired OTP"];
        }

        $otpModel->markOtpAsVerified($otpData['id']);
        return ["status" => "success", "message" => "OTP verified successfully"];
    }
}


