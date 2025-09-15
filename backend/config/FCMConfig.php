<?php

namespace Config;

use Dotenv\Dotenv;

class FCMConfig
{
    private static $instance = null;
    private $serverKey;
    private $projectId;
    private $serviceAccountPath;
    
    private function __construct()
    {
        // Load .env file from project root
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(dirname(dirname(__DIR__)));
            $dotenv->load();
        }
        
        $this->serverKey = $_ENV['FCM_SERVER_KEY'] ?? null;
        $this->projectId = $_ENV['FCM_PROJECT_ID'] ?? null;
        $this->serviceAccountPath = $_ENV['FCM_SERVICE_ACCOUNT_PATH'] ?? null;
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getServerKey()
    {
        return $this->serverKey;
    }
    
    public function getProjectId()
    {
        return $this->projectId;
    }
    
    public function getServiceAccountPath()
    {
        return $this->serviceAccountPath;
    }
    
    public function isConfigured()
    {
        return !empty($this->serverKey) && !empty($this->projectId);
    }
    
    public function getConfig()
    {
        return [
            'server_key' => $this->serverKey,
            'project_id' => $this->projectId,
            'service_account_path' => $this->serviceAccountPath,
            'configured' => $this->isConfigured()
        ];
    }
}
