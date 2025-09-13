# Rate Limiting System for OTP Requests

## Overview

The rate limiting system prevents abuse of OTP (One-Time Password) sending functionality by restricting the number of requests per user/IP address within specific time windows. This enhances security and prevents spam attacks.

## Features

- **Multi-level Rate Limiting**: Phone, Email, and IP-based limits
- **Purpose-specific Limits**: Different limits for signup, password reset, login, etc.
- **Automatic Blocking**: Temporary blocks when limits are exceeded
- **Flexible Configuration**: Configurable limits per purpose
- **Admin Monitoring**: Admin endpoints to check rate limit status
- **Automatic Cleanup**: Old records are automatically cleaned up

## Rate Limits

### Default Limits (per hour)

| Purpose | Max Requests | Block Duration |
|---------|-------------|----------------|
| Signup | 3 | 1 hour |
| Password Reset | 5 | 1 hour |
| Login | 10 | 30 minutes |
| Verification | 3 | 1 hour |
| Default | 5 | 1 hour |

### IP-based Limits

- **20 requests per hour per IP address**
- **1 hour block duration**

## Database Schema

### rate_limits Table

```sql
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'Phone, email, or IP address',
    identifier_type ENUM('phone', 'email', 'ip') NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action being rate limited',
    request_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    window_duration INT DEFAULT 3600 COMMENT 'Window duration in seconds',
    blocked_until TIMESTAMP NULL COMMENT 'When the identifier will be unblocked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_identifier_action (identifier, action),
    INDEX idx_window_start (window_start),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_identifier_type (identifier_type),
    
    UNIQUE KEY unique_identifier_action_window (identifier, action, window_start)
);
```

## API Endpoints

### Protected Endpoints (with Rate Limiting)

1. **POST /api/send_otp.php**
   - Rate limited by phone/email and IP
   - Returns rate limit info in response

2. **POST /api/request_password_reset.php**
   - Rate limited by phone and IP
   - Returns rate limit info in response

### Admin Endpoints

1. **POST /api/admin/rate_limit_status.php**
   - Check rate limit status for any identifier
   - Requires admin authentication

### Test Endpoints

1. **GET/POST /api/test_rate_limit.php**
   - Test rate limiting functionality
   - Development/testing purposes only

## Usage Examples

### Send OTP (with Rate Limiting)

```bash
curl -X POST https://api.runnix.africa/api/send_otp.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "08123456789",
    "purpose": "signup"
  }'
```

**Success Response:**
```json
{
  "status": "success",
  "message": "OTP sent successfully",
  "rate_limit": {
    "allowed": true,
    "current_count": 1,
    "max_requests": 3,
    "remaining_requests": 2
  }
}
```

**Rate Limited Response:**
```json
{
  "status": "error",
  "message": "Too many requests. Try again in 45 minutes",
  "rate_limit": {
    "allowed": false,
    "reason": "phone_blocked",
    "blocked_until": "2025-09-13 15:30:00",
    "remaining_seconds": 2700,
    "current_count": 3,
    "max_requests": 3
  }
}
```

### Check Rate Limit Status (Admin)

```bash
curl -X POST https://api.runnix.africa/api/admin/rate_limit_status.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "identifier": "2348123456789",
    "identifier_type": "phone",
    "purpose": "signup"
  }'
```

**Response:**
```json
{
  "status": "success",
  "identifier": "2348123456789",
  "identifier_type": "phone",
  "purpose": "signup",
  "rate_limit_status": {
    "request_count": 2,
    "last_request": "2025-09-13 14:15:30",
    "blocked_until": null,
    "is_blocked": false
  }
}
```

## Implementation Details

### Rate Limiting Logic

1. **Check Current Block**: If identifier is currently blocked, deny request
2. **Count Requests**: Count requests in current time window
3. **Apply Limits**: If limit exceeded, block identifier and deny request
4. **Record Request**: If allowed, record the request
5. **Return Response**: Include rate limit info in response

### Time Windows

- **Sliding Window**: Each request creates a new window entry
- **Window Duration**: Configurable (default 1 hour)
- **Cleanup**: Old records cleaned up automatically

### Blocking Mechanism

- **Temporary Blocks**: Identifiers blocked for specified duration
- **Automatic Unblocking**: Blocks expire automatically
- **Multiple Identifiers**: Phone, email, and IP can be blocked independently

## Security Features

1. **IP-based Limiting**: Prevents abuse from single IP
2. **Identifier-based Limiting**: Prevents abuse per phone/email
3. **Purpose-specific Limits**: Different limits for different use cases
4. **Automatic Cleanup**: Prevents database bloat
5. **Admin Monitoring**: Allows monitoring and debugging

## Configuration

### Rate Limits Configuration

Located in `backend/controller/RateLimiterController.php`:

```php
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
    // ... more configurations
];
```

### Database Cleanup

Automatic cleanup runs to remove old records:

```php
// Clean up records older than 24 hours
$rateLimiter->cleanupOldRecords(24);
```

## Error Handling

### HTTP Status Codes

- **200**: Success
- **400**: Bad Request (invalid input)
- **401**: Unauthorized (authentication required)
- **403**: Forbidden (admin access required)
- **429**: Too Many Requests (rate limited)

### Error Messages

- Clear, user-friendly error messages
- Include remaining time for blocked requests
- Provide rate limit information

## Monitoring and Maintenance

### Admin Tools

1. **Rate Limit Status Check**: Monitor any identifier's status
2. **Database Cleanup**: Automatic cleanup of old records
3. **Error Logging**: All rate limit events logged

### Performance Considerations

1. **Indexed Queries**: Optimized database queries with proper indexes
2. **Efficient Counting**: Fast request counting within time windows
3. **Minimal Overhead**: Rate limiting adds minimal processing overhead

## Testing

### Test Endpoint

Use `/api/test_rate_limit.php` to test rate limiting:

```bash
# Test rate limiting
curl -X POST https://api.runnix.africa/api/test_rate_limit.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "08123456789",
    "email": "test@example.com",
    "purpose": "signup"
  }'
```

### Test Scenarios

1. **Normal Usage**: Send requests within limits
2. **Limit Exceeded**: Send requests beyond limits
3. **Blocked Requests**: Try requests while blocked
4. **Different Purposes**: Test different purpose limits
5. **IP Limiting**: Test IP-based limits

## Future Enhancements

1. **Dynamic Limits**: Adjust limits based on user behavior
2. **Whitelist**: Allow trusted users/IPs higher limits
3. **Analytics**: Rate limiting analytics and reporting
4. **Notifications**: Alert admins of abuse attempts
5. **Machine Learning**: Detect and prevent sophisticated attacks

## Troubleshooting

### Common Issues

1. **Rate Limit Not Working**: Check database connection and table creation
2. **False Positives**: Adjust rate limits if too restrictive
3. **Performance Issues**: Check database indexes and query optimization
4. **Cleanup Issues**: Ensure cleanup runs regularly

### Debug Information

Enable error logging to debug issues:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Check error logs for rate limiting issues and database connection problems.
