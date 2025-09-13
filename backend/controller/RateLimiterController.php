<?php
namespace Controller;

use Model\RateLimiter;

class RateLimiterController
{
    private $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Check OTP sending rate limit
     */
    public function checkOtpRateLimit($identifier, $identifierType = 'phone')
    {
        // Different rate limits for different purposes
        $rateLimits = [
            'signup' => [
                'max_requests' => 3,      // 3 OTPs per hour for signup
                'window_duration' => 3600, // 1 hour
                'block_duration' => 3600   // Block for 1 hour
            ],
            'password_reset' => [
                'max_requests' => 5,      // 5 OTPs per hour for password reset
                'window_duration' => 3600, // 1 hour
                'block_duration' => 3600   // Block for 1 hour
            ],
            'login' => [
                'max_requests' => 10,     // 10 OTPs per hour for login
                'window_duration' => 3600, // 1 hour
                'block_duration' => 1800   // Block for 30 minutes
            ],
            'default' => [
                'max_requests' => 5,      // 5 OTPs per hour default
                'window_duration' => 3600, // 1 hour
                'block_duration' => 3600   // Block for 1 hour
            ]
        ];

        $action = 'send_otp';
        $limits = $rateLimits['default'];

        return $this->rateLimiter->checkRateLimit(
            $identifier,
            $identifierType,
            $action,
            $limits['max_requests'],
            $limits['window_duration'],
            $limits['block_duration']
        );
    }

    /**
     * Check OTP sending rate limit with specific purpose
     */
    public function checkOtpRateLimitWithPurpose($identifier, $identifierType, $purpose)
    {
        $rateLimits = [
            'signup' => [
                'max_requests' => 3,
                'window_duration' => 3600,
                'block_duration' => 3600
            ],
            'password_reset' => [
                'max_requests' => 5,
                'window_duration' => 3600,
                'block_duration' => 3600
            ],
            'login' => [
                'max_requests' => 10,
                'window_duration' => 3600,
                'block_duration' => 1800
            ],
            'verification' => [
                'max_requests' => 3,
                'window_duration' => 3600,
                'block_duration' => 3600
            ],
            'default' => [
                'max_requests' => 5,
                'window_duration' => 3600,
                'block_duration' => 3600
            ]
        ];

        $action = 'send_otp_' . $purpose;
        $limits = $rateLimits[$purpose] ?? $rateLimits['default'];

        return $this->rateLimiter->checkRateLimit(
            $identifier,
            $identifierType,
            $action,
            $limits['max_requests'],
            $limits['window_duration'],
            $limits['block_duration']
        );
    }

    /**
     * Check IP-based rate limit for OTP requests
     */
    public function checkIpRateLimit($ipAddress)
    {
        // IP-based rate limiting (more lenient)
        return $this->rateLimiter->checkRateLimit(
            $ipAddress,
            'ip',
            'send_otp_ip',
            20, // 20 OTPs per hour per IP
            3600, // 1 hour window
            3600  // Block for 1 hour
        );
    }

    /**
     * Get rate limit status for debugging/admin purposes
     */
    public function getRateLimitStatus($identifier, $identifierType, $purpose = null)
    {
        $action = $purpose ? 'send_otp_' . $purpose : 'send_otp';
        return $this->rateLimiter->getRateLimitStatus($identifier, $identifierType, $action);
    }

    /**
     * Clean up old rate limit records
     */
    public function cleanupOldRecords()
    {
        return $this->rateLimiter->cleanupOldRecords();
    }

    /**
     * Check multiple rate limits (phone, email, IP)
     */
    public function checkMultipleRateLimits($phone = null, $email = null, $purpose = 'default')
    {
        $results = [];
        $clientIP = RateLimiter::getClientIP();

        // Check IP rate limit first (most restrictive)
        $ipResult = $this->checkIpRateLimit($clientIP);
        $results['ip'] = $ipResult;

        if (!$ipResult['allowed']) {
            return [
                'allowed' => false,
                'reason' => 'ip_blocked',
                'message' => $ipResult['message'],
                'details' => $results
            ];
        }

        // Check phone rate limit if provided
        if ($phone) {
            $phoneResult = $this->checkOtpRateLimitWithPurpose($phone, 'phone', $purpose);
            $results['phone'] = $phoneResult;

            if (!$phoneResult['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'phone_blocked',
                    'message' => $phoneResult['message'],
                    'details' => $results
                ];
            }
        }

        // Check email rate limit if provided
        if ($email) {
            $emailResult = $this->checkOtpRateLimitWithPurpose($email, 'email', $purpose);
            $results['email'] = $emailResult;

            if (!$emailResult['allowed']) {
                return [
                    'allowed' => false,
                    'reason' => 'email_blocked',
                    'message' => $emailResult['message'],
                    'details' => $results
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => 'All rate limits passed',
            'details' => $results
        ];
    }

    /**
     * Format rate limit response for API
     */
    public function formatRateLimitResponse($rateLimitResult)
    {
        if ($rateLimitResult['allowed']) {
            return [
                'status' => 'success',
                'rate_limit' => [
                    'allowed' => true,
                    'current_count' => $rateLimitResult['current_count'],
                    'max_requests' => $rateLimitResult['max_requests'],
                    'remaining_requests' => $rateLimitResult['remaining_requests'],
                    'window_duration' => $rateLimitResult['window_duration']
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $rateLimitResult['message'],
                'rate_limit' => [
                    'allowed' => false,
                    'reason' => $rateLimitResult['reason'],
                    'blocked_until' => $rateLimitResult['blocked_until'] ?? null,
                    'remaining_seconds' => $rateLimitResult['remaining_seconds'] ?? 0,
                    'current_count' => $rateLimitResult['current_count'] ?? 0,
                    'max_requests' => $rateLimitResult['max_requests'] ?? 0
                ]
            ];
        }
    }
}
