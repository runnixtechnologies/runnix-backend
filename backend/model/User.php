<?php
namespace Model;

use Config\Database;
use PDO;

class User
{
    private $conn;
    private $table = "users";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    private function generateReferralCode($email) {
        return substr(md5($email . time()), 0, 8);
    }
    
    public function createUser($email, $phone, $password, $role, $referrer_id = null, $google_id = null)
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $referral_code = $this->generateReferralCode($email);


        $sql = "INSERT INTO {$this->table} 
    (email, phone, password, role, referred_by, referral_code, google_id, is_verified, status, created_at)
    VALUES (:email, :phone, :password, :role, :referred_by, :referral_code, :google_id, 1, 1, NOW())";


    
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':referral_code', $referral_code);
        $stmt->bindValue(':referred_by', $referrer_id, is_null($referrer_id) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':google_id', $google_id);
    
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
    
        return false;
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
    

    public function login($phone, $password) {
        $query = "SELECT * FROM {$this->table} WHERE phone = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $phone);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
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

}
