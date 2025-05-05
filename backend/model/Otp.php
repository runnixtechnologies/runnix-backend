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

   // Add inside Otp.php model
   public function verifyOtp($phone, $otp = null, $purpose = 'signup', $onlyVerified = false)
   {
       $sql = "SELECT * FROM {$this->table}
               WHERE phone = :phone 
               AND purpose = :purpose";
   
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
       $stmt->bindParam(":phone", $phone);
       if ($otp) $stmt->bindParam(":otp", $otp);
       $stmt->bindParam(":purpose", $purpose);
       $stmt->execute();
   
       $record = $stmt->fetch(PDO::FETCH_ASSOC);
   
       
       if ($record) {
           $updateSql = "UPDATE {$this->table}
                         SET is_verified = 1, verified_at = NOW()
                         WHERE id = :id";
           $updateStmt = $this->conn->prepare($updateSql);
           $updateStmt->bindParam(":id", $record['id']);
           $updateStmt->execute();
       }
   
       return $record;
   }
   
    public function markOtpAsVerified($id)
    {
        $sql = "UPDATE {$this->table} SET is_verified = 1, verified_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function isOtpVerified($phone, $purpose = 'signup')
{
    $record = $this->verifyOtp($phone, null, $purpose, true);
    return $record ? true : false;
}

}
