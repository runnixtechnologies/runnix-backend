<?php
namespace Middleware;

use Controller\RateLimiterController;

function checkOtpRateLimit($phone = null, $email = null, $purpose = 'default')
{
    $rateLimiterController = new RateLimiterController();
    
    // Check multiple rate limits
    $result = $rateLimiterController->checkMultipleRateLimits($phone, $email, $purpose);
    
    if (!$result['allowed']) {
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
            'rate_limit' => [
                'allowed' => false,
                'reason' => $result['reason'],
                'details' => $result['details'] ?? null
            ]
        ]);
        exit;
    }
    
    return $result;
}

function checkIpRateLimit()
{
    $rateLimiterController = new RateLimiterController();
    $clientIP = \Model\RateLimiter::getClientIP();
    
    $result = $rateLimiterController->checkIpRateLimit($clientIP);
    
    if (!$result['allowed']) {
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
            'rate_limit' => [
                'allowed' => false,
                'reason' => 'ip_blocked',
                'blocked_until' => $result['blocked_until'],
                'remaining_seconds' => $result['remaining_seconds']
            ]
        ]);
        exit;
    }
    
    return $result;
}

function getRateLimitStatus($identifier, $identifierType, $purpose = null)
{
    $rateLimiterController = new RateLimiterController();
    return $rateLimiterController->getRateLimitStatus($identifier, $identifierType, $purpose);
}
