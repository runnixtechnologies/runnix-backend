# Rate Limiting Testing Guide

## Quick Test Steps

### 1. **Run Database Migration**
First, create the rate_limits table:
```sql
USE runnix;
SOURCE backend/migrations/create_rate_limits_table.sql;
```

### 2. **Test Basic Functionality**
Run the simple test:
```bash
php backend/api/test_rate_limit_simple.php
```

### 3. **Test OTP Endpoints**

#### Test 1: Send OTP (should work)
```bash
curl -X POST https://api.runnix.africa/api/send_otp.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "08123456789",
    "purpose": "signup"
  }'
```

**Expected Response:**
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

#### Test 2: Send Multiple OTPs (should trigger rate limit)
Send the same request 4 times quickly:

```bash
# Send 4 requests in a row
for i in {1..4}; do
  curl -X POST https://api.runnix.africa/api/send_otp.php \
    -H "Content-Type: application/json" \
    -d '{"phone": "08123456789", "purpose": "signup"}'
  echo "Request $i completed"
done
```

**Expected Result:**
- First 3 requests: Success
- 4th request: Rate limited with 429 status

#### Test 3: Test Password Reset (different limits)
```bash
curl -X POST https://api.runnix.africa/api/request_password_reset.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "08123456789"
  }'
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "OTP sent successfully",
  "rate_limit": {
    "allowed": true,
    "current_count": 1,
    "max_requests": 5,
    "remaining_requests": 4
  }
}
```

### 4. **Test Rate Limit Status (Admin)**
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

### 5. **Test Rate Limit Endpoint**
```bash
curl -X POST https://api.runnix.africa/api/test_rate_limit.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "08123456789",
    "email": "test@example.com",
    "purpose": "signup"
  }'
```

## Test Scenarios

### Scenario 1: Normal Usage
1. Send 1-2 OTP requests
2. Verify success responses
3. Check rate limit info in response

### Scenario 2: Rate Limit Exceeded
1. Send 4+ OTP requests quickly
2. Verify 4th request gets 429 status
3. Check error message includes remaining time

### Scenario 3: Different Purposes
1. Test signup (3 requests/hour)
2. Test password reset (5 requests/hour)
3. Test login (10 requests/hour)
4. Verify different limits apply

### Scenario 4: IP Rate Limiting
1. Send 20+ requests from same IP
2. Verify IP-based rate limiting kicks in
3. Check error message

## Expected Behavior

### Success Response
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

### Rate Limited Response
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

## Troubleshooting

### Issue: Rate limiting not working
**Check:**
1. Database table exists: `SHOW TABLES LIKE 'rate_limits';`
2. Classes load properly: `php -l backend/model/RateLimiter.php`
3. Database connection works
4. Error logs for any issues

### Issue: False positives
**Check:**
1. Rate limits are too restrictive
2. Time windows are correct
3. Cleanup is running properly

### Issue: Performance problems
**Check:**
1. Database indexes are created
2. Queries are optimized
3. Cleanup runs regularly

## Manual Testing Checklist

- [ ] Database migration completed
- [ ] Basic functionality test passes
- [ ] OTP endpoints include rate limiting
- [ ] Multiple requests trigger rate limiting
- [ ] Different purposes have different limits
- [ ] IP-based rate limiting works
- [ ] Admin status check works
- [ ] Error messages are clear
- [ ] Rate limit info included in responses
- [ ] Blocking/unblocking works correctly

## Production Testing

1. **Start with lenient limits** (higher than default)
2. **Monitor for false positives**
3. **Adjust limits based on usage patterns**
4. **Set up monitoring alerts**
5. **Test with real user scenarios**

## Rate Limit Configuration

Current limits in `RateLimiterController.php`:
```php
'signup' => [
    'max_requests' => 3,      // 3 OTPs per hour
    'window_duration' => 3600, // 1 hour window
    'block_duration' => 3600   // Block for 1 hour
],
'password_reset' => [
    'max_requests' => 5,      // 5 OTPs per hour
    'window_duration' => 3600, // 1 hour window
    'block_duration' => 3600   // Block for 1 hour
],
'login' => [
    'max_requests' => 10,     // 10 OTPs per hour
    'window_duration' => 3600, // 1 hour window
    'block_duration' => 1800   // Block for 30 minutes
]
```

Adjust these values based on your testing results and user needs.
