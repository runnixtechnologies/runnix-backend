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


  public function verifyOtp($identifier, $otp = null, $purpose = 'signup', $onlyVerified = false): bool
{
    $sql = "SELECT * FROM {$this->table} WHERE purpose = :purpose";

    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
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
    $stmt->bindParam(":identifier", $identifier);
    if ($otp) $stmt->bindParam(":otp", $otp);
    $stmt->execute();

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

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
