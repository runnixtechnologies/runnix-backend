<?php
namespace Model;

use Config\Database;
use PDO;

class User
{
    private $conn;
    private $table = "users";
    private $profileTable = "user_profiles";  // Assuming this is your profile table

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    private function generateReferralCode($email) {
        return substr(md5($email . time()), 0, 8);
    }
    
    public function createUser($email, $phone, $password, $role, $referrer_id = null)
{
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $referral_code = $this->generateReferralCode($email ?? $phone);

    $sql = "INSERT INTO {$this->table} 
        (email, phone, password, role, referred_by, referral_code, is_verified, status, created_at)
        VALUES (:email, :phone, :password, :role, :referred_by, :referral_code, 1, 1, NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':referral_code', $referral_code);
    $stmt->bindValue(':referred_by', $referrer_id, is_null($referrer_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);

    if ($stmt->execute()) {
        return $this->conn->lastInsertId();
    }

    return false;
}

    
    public function getUserByGoogleId($googleId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createUserProfile($user_id, $first_name, $last_name)
{
    $sql = "INSERT INTO user_profiles (user_id, first_name, last_name)
            VALUES (:user_id, :first_name, :last_name)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);

    return $stmt->execute();
}
public function createMerchantProfile($user_id, $store_name, $business_address, $business_email, $biz_reg_number, $biz_type, $biz_logo = null)
{
    $sql = "INSERT INTO merchants (user_id, store_name, business_address, business_email, biz_reg_number, biz_type, biz_logo, verified)
            VALUES (:user_id, :store_name, :business_address, :business_email, :biz_reg_number, :biz_type, :biz_logo, 0)";  // Set default verified as 0

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':store_name', $store_name);
    $stmt->bindParam(':business_address', $business_address);
    $stmt->bindParam(':business_email', $business_email);
    $stmt->bindParam(':biz_reg_number', $biz_reg_number);
    $stmt->bindParam(':biz_type', $biz_type);
    $stmt->bindParam(':biz_logo', $biz_logo);

    return $stmt->execute();
}

public function verifyMerchant($merchant_id)
{
    $sql = "UPDATE merchants SET verified = 1 WHERE id = :merchant_id";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':merchant_id', $merchant_id);

    return $stmt->execute();
}

    public function getUserByPhone($phone)
{
    $sql = "SELECT * FROM {$this->table} WHERE phone = :phone LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getUserByEmail($email)
{
    $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



    public function getUserByReferralCode($referral_code)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE referral_code = :referral_code LIMIT 1");
        $stmt->bindParam(":referral_code", $referral_code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

   public function login($identifier, $password)
{
    // Determine if the identifier is an email or phone
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT * FROM {$this->table} WHERE email = :identifier";
    } else {
        $query = "SELECT * FROM {$this->table} WHERE phone = :identifier";
    }

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':identifier', $identifier);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }

    return false;
}

    /*public function getMerchantStore($userId)
    {
    $stmt = $this->conn->prepare("SELECT store_type FROM stores WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
*/
    public function getMerchantStore($userId)
{
    $sql = "SELECT 
                s.id AS store_id,
                s.store_name,
                s.store_type_id,
                s.biz_logo,
                st.name AS store_type_name,
                st.image_url AS store_type_image
            FROM stores s
            JOIN store_types st ON s.store_type_id = st.id
            WHERE s.user_id = :user_id
            LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


    public function resetPasswordByPhone($phone, $plainPassword)
    {
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE phone = :phone");
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":phone", $phone);
        return $stmt->execute();
    }
    
    public function findOrCreateGoogleUser($email, $first_name, $last_name)
{
    $email = strtolower(trim($email));

    $user = $this->getUserByEmail($email);
    if ($user) {
        return $user;
    }

    // Create empty user with no password or phone yet
    $sql = "INSERT INTO {$this->table} (email, is_verified, status, created_at)
            VALUES (:email, 1, 'pending', NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $userId = $this->conn->lastInsertId();

    // Save user profile
    $this->createUserProfile($userId, $first_name, $last_name);

    return $this->getUserByEmail($email);
}

public function updateUserReferral($user_id, $referrer_id) {
    $sql = "UPDATE {$this->table} SET referred_by = :referrer_id WHERE id = :user_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':referrer_id', $referrer_id);
    $stmt->bindParam(':user_id', $user_id);
    return $stmt->execute();
}

public function getUserById($userId)
{
    try {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null; // return null if user not found
    } catch (\PDOException $e) {
        // Log error or handle it as needed
        return null;
    }
}

/*
public function deleteUser($userId)
{
    $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
    return $stmt->execute([$userId]);
}

public function deleteUserProfile($userId)
{
    $stmt = $this->conn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
    return $stmt->execute([$userId]);
}*/

public function deleteUser($userId)
{
    try {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    } catch (\Exception $e) {
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}


    // Delete user profile
    public function deleteUserProfile($userId)
    {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Delete user profile data
            $stmt = $this->conn->prepare("DELETE FROM {$this->profileTable} WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            if ($stmt->rowCount() <= 0) {
                throw new \Exception("User profile not found or failed to delete.");
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            // Rollback transaction if anything fails
            $this->conn->rollBack();
            error_log("Error deleting user profile: " . $e->getMessage());
            return false;
        }
    }


    public function updateUserStatus($userId, $role, $isOnline) {
    try {
        $sql = "INSERT INTO user_status (user_id, role, is_online)
                VALUES (:user_id, :role, :is_online)
                ON DUPLICATE KEY UPDATE is_online = :is_online, updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':role' => $role,
            ':is_online' => $isOnline
        ]);

        http_response_code(200);
        return ["status" => "success", "message" => "User status updated successfully."];

    } catch (\PDOException $e) {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to update user status."];
    }
}

public function getUserStatus($userId) {
    try {
        $sql = "SELECT role, is_online FROM user_status WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($status) {
            return ["status" => "success", "data" => $status];
        } else {
            return ["status" => "error", "message" => "User status not found."];
        }

    } catch (\PDOException $e) {
        return ["status" => "error", "message" => "Failed to fetch user status."];
    }
}

}
