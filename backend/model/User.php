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
    
    public function getConnection()
    {
        return $this->conn;
    }

    private function generateReferralCode($email) {
        return substr(md5($email . time()), 0, 8);
    }

    /**
     * Generate a unique 8-character support pin
     * Format: 4 letters + 4 numbers (e.g., ABCD1234)
     */
    private function generateSupportPin() {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        
        $pin = '';
        
        // Generate 4 random letters
        for ($i = 0; $i < 4; $i++) {
            $pin .= $letters[rand(0, strlen($letters) - 1)];
        }
        
        // Generate 4 random numbers
        for ($i = 0; $i < 4; $i++) {
            $pin .= $numbers[rand(0, strlen($numbers) - 1)];
        }
        
        return $pin;
    }

    /**
     * Generate a unique support pin that doesn't already exist
     */
    private function generateUniqueSupportPin() {
        do {
            $pin = $this->generateSupportPin();
        } while ($this->isSupportPinExists($pin));
        
        return $pin;
    }

    /**
     * Check if a support pin already exists
     */
    public function isSupportPinExists($supportPin) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE support_pin = :support_pin LIMIT 1");
            $stmt->bindParam(':support_pin', $supportPin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (\PDOException $e) {
            error_log("Error checking support pin existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by support pin
     */
    public function getUserBySupportPin($supportPin) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE support_pin = :support_pin AND deleted_at IS NULL LIMIT 1");
            $stmt->bindParam(':support_pin', $supportPin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting user by support pin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify support pin for a user
     */
    public function verifySupportPin($userId, $supportPin) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE id = :user_id AND support_pin = :support_pin AND deleted_at IS NULL LIMIT 1");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':support_pin', $supportPin);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (\PDOException $e) {
            error_log("Error verifying support pin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Regenerate support pin for a user
     */
    public function regenerateSupportPin($userId) {
        try {
            $newPin = $this->generateUniqueSupportPin();
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET support_pin = :support_pin, updated_at = NOW() WHERE id = :user_id");
            $stmt->bindParam(':support_pin', $newPin);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                return $newPin;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error regenerating support pin: " . $e->getMessage());
            return false;
        }
    }
    
    public function createUser($email, $phone, $password, $role, $referrer_id = null)
{
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $referral_code = $this->generateReferralCode($email ?? $phone);
    $support_pin = $this->generateUniqueSupportPin();

    $sql = "INSERT INTO {$this->table} 
        (email, phone, password, role, referred_by, referral_code, support_pin, is_verified, status, created_at)
        VALUES (:email, :phone, :password, :role, :referred_by, :referral_code, :support_pin, 1, 1, NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':referral_code', $referral_code);
    $stmt->bindParam(':support_pin', $support_pin);
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
    $sql = "SELECT * FROM {$this->table} WHERE phone = :phone AND deleted_at IS NULL LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getUserByEmail($email)
{
    $sql = "SELECT * FROM {$this->table} WHERE email = :email AND deleted_at IS NULL LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



    public function getUserByReferralCode($referral_code)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE referral_code = :referral_code AND deleted_at IS NULL LIMIT 1");
        $stmt->bindParam(":referral_code", $referral_code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

   public function login($identifier, $password)
{
    // Determine if the identifier is an email or phone
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT * FROM {$this->table} WHERE email = :identifier AND deleted_at IS NULL";
    } else {
        $query = "SELECT * FROM {$this->table} WHERE phone = :identifier AND deleted_at IS NULL";
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

    // Generate support pin for Google user
    $support_pin = $this->generateUniqueSupportPin();

    // Create empty user with no password or phone yet
    $sql = "INSERT INTO {$this->table} (email, support_pin, is_verified, status, created_at)
            VALUES (:email, :support_pin, 1, 'pending', NOW())";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":support_pin", $support_pin);
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
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
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

// Soft delete user account (new method)
public function softDeleteUser($userId, $deletedBy = null, $reason = null, $method = 'self')
{
    try {
        $this->conn->beginTransaction();
        
        // Set reactivation deadline (60 days from now)
        $reactivationDeadline = date('Y-m-d H:i:s', strtotime('+60 days'));
        
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NOW(), 
                    deleted_by = :deleted_by, 
                    deletion_reason = :reason, 
                    deletion_method = :method,
                    can_reactivate = TRUE,
                    reactivation_deadline = :deadline,
                    status = 'deleted'
                WHERE id = :user_id AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':deleted_by', $deletedBy);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':method', $method);
        $stmt->bindParam(':deadline', $reactivationDeadline);
        
        $result = $stmt->execute();
        
        if ($result && $stmt->rowCount() > 0) {
            $this->conn->commit();
            return true;
        } else {
            $this->conn->rollBack();
            return false;
        }
    } catch (\Exception $e) {
        $this->conn->rollBack();
        error_log("Error soft deleting user: " . $e->getMessage());
        return false;
    }
}

// Reactivate user account
public function reactivateUser($userId)
{
    try {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NULL, 
                    deleted_by = NULL, 
                    deletion_reason = NULL, 
                    deletion_method = NULL,
                    can_reactivate = TRUE,
                    reactivation_deadline = NULL,
                    status = 'active'
                WHERE id = :user_id AND deleted_at IS NOT NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    } catch (\Exception $e) {
        error_log("Error reactivating user: " . $e->getMessage());
        return false;
    }
}

// Check if user is soft deleted
public function isUserSoftDeleted($userId)
{
    try {
        $stmt = $this->conn->prepare("SELECT deleted_at FROM {$this->table} WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && !is_null($result['deleted_at']);
    } catch (\Exception $e) {
        error_log("Error checking soft delete status: " . $e->getMessage());
        return false;
    }
}

// Get soft deleted users (for admin)
public function getSoftDeletedUsers($limit = 50, $offset = 0)
{
    try {
        $sql = "SELECT u.*, up.first_name, up.last_name, 
                       deleter.email as deleted_by_email,
                       deleter.phone as deleted_by_phone
                FROM {$this->table} u
                LEFT JOIN {$this->profileTable} up ON u.id = up.user_id
                LEFT JOIN {$this->table} deleter ON u.deleted_by = deleter.id
                WHERE u.deleted_at IS NOT NULL
                ORDER BY u.deleted_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        error_log("Error getting soft deleted users: " . $e->getMessage());
        return [];
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

    // Profile Management Methods
    public function updateUserEmail($userId, $email)
    {
        try {
            $sql = "UPDATE users SET email = :email, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'email' => $email,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("updateUserEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateUserPhone($userId, $phone)
    {
        try {
            $sql = "UPDATE users SET phone = :phone, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'phone' => $phone,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("updateUserPhone error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserProfile($userId)
    {
        try {
            $sql = "SELECT * FROM user_profiles WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getUserProfile error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateUserProfile($profileData)
    {
        try {
            $sql = "UPDATE user_profiles SET 
                    first_name = :first_name, 
                    last_name = :last_name 
                    WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'first_name' => $profileData['first_name'],
                'last_name' => $profileData['last_name'],
                'user_id' => $profileData['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("updateUserProfile error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProfilePicture($userId, $profilePictureUrl)
    {
        try {
            $sql = "UPDATE user_profiles SET profile_picture = :profile_picture, updated_at = NOW() WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'profile_picture' => $profilePictureUrl,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("updateProfilePicture error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword($userId, $hashedPassword)
    {
        try {
            $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'password' => $hashedPassword,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("updatePassword error: " . $e->getMessage());
            return false;
        }
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }
    
    public function commit()
    {
        return $this->conn->commit();
    }
    
    public function rollback()
    {
        return $this->conn->rollBack();
    }

}
