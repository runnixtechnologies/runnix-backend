<?php

namespace Service;

use Config\FCMConfig;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;
use Exception;

class FCMService
{
    private $messaging;
    private $config;
    private $isConfigured = false;
    
    public function __construct()
    {
        $this->config = FCMConfig::getInstance();
        
        if (!$this->config->isConfigured()) {
            error_log('FCM is not properly configured. Please check your environment variables.');
            $this->isConfigured = false;
            return; // Don't throw exception, just mark as not configured
        }
        
        $this->isConfigured = true;
        $this->initializeFirebase();
    }
    
    public function isConfigured()
    {
        return $this->isConfigured;
    }
    
    private function initializeFirebase()
    {
        try {
            $factory = (new Factory())
                ->withProjectId($this->config->getProjectId());
            
            // Use service account if available, otherwise use server key
            if ($this->config->getServiceAccountPath() && file_exists($this->config->getServiceAccountPath())) {
                $factory = $factory->withServiceAccount($this->config->getServiceAccountPath());
            } else {
                // Fallback to server key (legacy method)
                $factory = $factory->withServiceAccount([
                    'project_id' => $this->config->getProjectId(),
                    'private_key' => $this->config->getServerKey()
                ]);
            }
            
            $this->messaging = $factory->createMessaging();
            
        } catch (Exception $e) {
            error_log("FCM Initialization Error: " . $e->getMessage());
            throw new Exception('Failed to initialize Firebase Cloud Messaging: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification to a single device
     */
    public function sendToDevice($token, $title, $body, $data = [])
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
            
            $result = $this->messaging->send($message);
            
            return [
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'message_id' => $result
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Send Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to multiple devices
     */
    public function sendToMultipleDevices($tokens, $title, $body, $data = [])
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
            
            $result = $this->messaging->sendMulticast($message, $tokens);
            
            return [
                'status' => 'success',
                'message' => 'Notifications sent successfully',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count(),
                'results' => $result
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Multicast Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send notifications: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to a topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
            
            $result = $this->messaging->send($message);
            
            return [
                'status' => 'success',
                'message' => 'Topic notification sent successfully',
                'message_id' => $result
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Topic Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send topic notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification with custom Android config
     */
    public function sendWithAndroidConfig($token, $title, $body, $data = [], $androidConfig = [])
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
            
            if (!empty($androidConfig)) {
                $android = AndroidConfig::fromArray($androidConfig);
                $message = $message->withAndroidConfig($android);
            }
            
            $result = $this->messaging->send($message);
            
            return [
                'status' => 'success',
                'message' => 'Android notification sent successfully',
                'message_id' => $result
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Android Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send Android notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification with custom iOS config
     */
    public function sendWithIOSConfig($token, $title, $body, $data = [], $iosConfig = [])
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
            
            if (!empty($iosConfig)) {
                $apns = ApnsConfig::fromArray($iosConfig);
                $message = $message->withApnsConfig($apns);
            }
            
            $result = $this->messaging->send($message);
            
            return [
                'status' => 'success',
                'message' => 'iOS notification sent successfully',
                'message_id' => $result
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM iOS Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send iOS notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Subscribe device to topic
     */
    public function subscribeToTopic($tokens, $topic)
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $result = $this->messaging->subscribeToTopic($topic, $tokens);
            
            return [
                'status' => 'success',
                'message' => 'Successfully subscribed to topic',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Subscribe Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to subscribe to topic: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Unsubscribe device from topic
     */
    public function unsubscribeFromTopic($tokens, $topic)
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            $result = $this->messaging->unsubscribeFromTopic($topic, $tokens);
            
            return [
                'status' => 'success',
                'message' => 'Successfully unsubscribed from topic',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];
            
        } catch (MessagingException $e) {
            error_log("FCM Unsubscribe Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to unsubscribe from topic: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate FCM token
     */
    public function validateToken($token)
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'FCM is not properly configured. Please check your environment variables.'
            ];
        }
        
        try {
            // Try to send a test message to validate the token
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create('Test', 'Token validation'));
            
            $this->messaging->send($message);
            
            return [
                'status' => 'success',
                'valid' => true,
                'message' => 'Token is valid'
            ];
            
        } catch (MessagingException $e) {
            return [
                'status' => 'error',
                'valid' => false,
                'message' => 'Token is invalid: ' . $e->getMessage()
            ];
        }
    }
}
