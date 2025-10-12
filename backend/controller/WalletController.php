<?php

namespace Controller;

use Model\Wallet;

class WalletController
{
    private $wallet;

    public function __construct()
    {
        $this->wallet = new Wallet(); // Correct model used here
    }

    // Check wallet balance
    public function checkBalance($userId)
    {
        if (empty($userId)) {
            http_response_code(400);
            return ["status" => "error", "message" => "User ID is required"];
        }

        $wallet = $this->wallet->getWalletByUserId($userId);

        if (!$wallet) {
            http_response_code(404);
            return ["status" => "error", "message" => "Wallet not found"];
        }

        return [
            "status" => "success",
            "balance" => $wallet['balance'],
            "currency" => $wallet['currency'],
            "last_transaction_at" => $wallet['last_transaction_at']
        ];
    }

    // Fund wallet
    public function fundWallet($data)
    {
        if (empty($data['user_id']) || empty($data['amount']) || empty($data['reference'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required fields"];
        }

        $userId = $data['user_id'];
        $amount = floatval($data['amount']);
        $reference = $data['reference'];
        $role = $data['role'] ?? 'user';

        $result = $this->wallet->fund($userId, $role, $amount, $reference);

        if ($result) {
            return ["status" => "success", "message" => "Wallet funded successfully"];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to fund wallet"];
        }
    }

    // Spend from wallet
    public function spendFromWallet($data)
    {
        if (empty($data['user_id']) || empty($data['amount']) || empty($data['description'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required fields"];
        }

        $userId = $data['user_id'];
        $amount = floatval($data['amount']);
        $description = $data['description'];
        $role = $data['role'] ?? 'user';

        $result = $this->wallet->spend($userId, $role, $amount, $description);

        if ($result) {
            return ["status" => "success", "message" => "Transaction successful"];
        } else {
            http_response_code(400);
            return ["status" => "error", "message" => "Insufficient balance or transaction failed"];
        }
    }
}
