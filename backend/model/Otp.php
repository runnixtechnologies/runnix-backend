<?php
namespace Model;

use Config\Database;
use PDO;

class Otp
{
    private $conn;
    private $table = "otp_requests";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function createOtp($user_id, $phone, $email, $otp, $purpose, $expires_at)
    {
        $sql = "INSERT INTO {$this->table} 
            (user_id, phone, email, otp_code, purpose, expires_at) 
            VALUES (:user_id, :phone, :email, :otp_code, :purpose, :expires_at)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":otp_code", $otp);
        $stmt->bindParam(":purpose", $purpose);
        $stmt->bindParam(":expires_at", $expires_at);

        return $stmt->execute();
    }


    /**
     * Convenience method to generate and store an OTP for a phone or email identifier.
     * Returns true on success, false on failure.
     */
    public function generateOtp($identifier, $purpose = 'signup', $userId = null)
    {
        try {
            $otpCode = (string)random_int(100000, 999999);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $phone = null;
            $email = null;
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $email = strtolower(trim($identifier));
            } else {
                // assume phone; normalize by stripping non-digits
                $digitsOnly = preg_replace('/\D+/', '', $identifier);
                $phone = $digitsOnly;
            }

            $created = $this->createOtp($userId, $phone, $email, $otpCode, $purpose, $expiresAt);

            if (!$created) {
                return false;
            }

            // Actual delivery (SMS/Email) should be handled by higher-level service.
            // We log for observability.
            $channel = $email ? 'email' : 'phone';
            error_log("OTP generated for {$channel} ({$identifier}) purpose={$purpose} expires={$expiresAt}");

            return true;
        } catch (\Throwable $e) {
            error_log('generateOtp error: ' . $e->getMessage());
            return false;
        }
    }


  public function verifyOtp($identifier, $otp = null, $purpose = 'signup', $onlyVerified = false): bool
{
    // Normalize identifier
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $normalized = strtolower(trim($identifier));
    } else {
        $normalized = preg_replace('/\D+/', '', (string)$identifier);
    }

    // Debug logging to specific file
    $logFile = __DIR__ . '/../php-error.log';
    error_log("OTP verify debug - Original: {$identifier}, Normalized: {$normalized}, OTP: {$otp}, Purpose: {$purpose}", 3, $logFile);

    $sql = "SELECT * FROM {$this->table} WHERE purpose = :purpose";

    if (filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        $sql .= " AND email = :identifier";
    } else {
        $sql .= " AND phone = :identifier";
    }

    if ($otp) {
        $sql .= " AND otp_code = :otp";
    }

    if ($onlyVerified) {
        $sql .= " AND is_verified = 1";
    } else {
        $sql .= " AND is_verified = 0 AND expires_at >= NOW()";
    }

    $sql .= " ORDER BY id DESC LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":purpose", $purpose);
    $stmt->bindParam(":identifier", $normalized);
    if ($otp) $stmt->bindParam(":otp", $otp);
    $stmt->execute();

    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug logging - show what was found
    $logFile = __DIR__ . '/../php-error.log';
    error_log("OTP query result: " . json_encode($record), 3, $logFile);
    error_log("SQL query: {$sql}", 3, $logFile);

    if ($record && !$onlyVerified) {
        // Mark OTP as verified if not already
        $updateSql = "UPDATE {$this->table} SET is_verified = 1, verified_at = NOW() WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->bindParam(":id", $record['id']);
        $updateStmt->execute();
        return true;
    }

    return $record ? true : false;
}


   
    public function markOtpAsVerified($id)
    {
        $sql = "UPDATE {$this->table} SET is_verified = 1, verified_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

   public function isOtpVerified($identifier, $purpose = 'signup')
{
    $record = $this->verifyOtp($identifier, null, $purpose, true);
    return $record ? true : false;
}

    public function getOtpRecord($identifier, $purpose = 'signup')
    {
        // Normalize identifier
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $normalized = strtolower(trim($identifier));
        } else {
            $normalized = preg_replace('/\D+/', '', (string)$identifier);
        }

        $sql = "SELECT * FROM {$this->table} WHERE purpose = :purpose";

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            $sql .= " AND email = :identifier";
        } else {
            $sql .= " AND phone = :identifier";
        }

        $sql .= " ORDER BY id DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":purpose", $purpose);
        $stmt->bindParam(":identifier", $normalized);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


/*public function OtpVerified($phone, $purpose)
{
    $stmt = $this->conn->prepare("
        SELECT id FROM otp_requests 
        WHERE phone = :phone AND purpose = :purpose AND is_verified = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':purpose', $purpose);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}*/

}
