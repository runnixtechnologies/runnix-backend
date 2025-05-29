<?php

namespace Controller;

use Model\Wallet;

class WalletController
{
    private $wallet;

    public function __construct()
    {
        $this->wallet = new WalletController(); // Matches your controller pattern
    }

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
            "currency" => $wallet['currency']
        ];
    }

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
