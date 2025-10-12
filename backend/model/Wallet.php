<?php

namespace Model;

use Config\Database;
use PDO;
use PDOException;
class Wallet extends BaseModel
{
    private $conn;

    protected $table = "wallets";
    public function __construct()
    {
        $this->conn = (new Database())->getConnection(); // Matches your model pattern
    }

    public function getWalletByUserId($userId)
    {
        $sql = "SELECT * FROM $this->table WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fund($userId, $role, $amount, $reference)
    {
        try {
            $this->conn->beginTransaction();

            $wallet = $this->getWalletByUserId($userId);

            if (!$wallet) {
                $sql = "INSERT INTO wallet (user_id, role, balance, currency, last_transaction_at)
                        VALUES (:user_id, :role, :balance, 'NGN', NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':role' => $role,
                    ':balance' => $amount
                ]);
                $walletId = $this->conn->lastInsertId();
            } else {
                $sql = "UPDATE wallet SET balance = balance + :amount, last_transaction_at = NOW()
                        WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':amount' => $amount,
                    ':user_id' => $userId
                ]);
                $walletId = $wallet['id'];
            }

            $sql = "INSERT INTO wallet_transactions (wallet_id, type, amount, description, transaction_ref, status, created_at)
                    VALUES (:wallet_id, 'credit', :amount, 'Wallet Funding', :ref, 'successful', NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':wallet_id' => $walletId,
                ':amount' => $amount,
                ':ref' => $reference
            ]);

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("Wallet Error: " . $e->getMessage());
            return false;
        }
    }

    public function spend($userId, $role, $amount, $description,  $reference = '')
    {
        try {
            $wallet = $this->getWalletByUserId($userId);

            if (!$wallet || $wallet['balance'] < $amount) {
                return false;
            }

            $this->conn->beginTransaction();

            $sql = "UPDATE wallet SET balance = balance - :amount, last_transaction_at = NOW()
                    WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':amount' => $amount,
                ':user_id' => $userId
            ]);

            $sql = "INSERT INTO wallet_transactions (wallet_id, type, amount, description, transaction_ref, status, created_at)
                    VALUES (:wallet_id, 'debit', :amount, :description, '', 'successful', NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':wallet_id' => $wallet['id'],
                ':amount' => $amount,
                ':description' => $description
            ]);

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("Wallet Error: " . $e->getMessage());
            return false;
        }
    }
}
