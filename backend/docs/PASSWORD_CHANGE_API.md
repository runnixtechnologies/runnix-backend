# Password Change API Documentation

## Overview

The password change API allows authenticated users to change their password securely. This endpoint requires the user to provide their current password for verification before allowing the password change.

## Security Features

- **Authentication Required**: User must be logged in with valid JWT token
- **Current Password Verification**: Must provide correct current password
- **Strong Password Validation**: Enforces password strength requirements
- **Rate Limiting**: Prevents abuse with 3 attempts per hour
- **Activity Logging**: All password changes are logged for security
- **Password Uniqueness**: New password must be different from current password

## Password Requirements

### Minimum Requirements
- **Length**: At least 8 characters, maximum 128 characters
- **Uppercase**: At least one uppercase letter (A-Z)
- **Lowercase**: At least one lowercase letter (a-z)
- **Number**: At least one digit (0-9)
- **Special Character**: At least one special character (!@#$%^&*)

### Additional Security Checks
- **No Common Passwords**: Rejects common weak passwords
- **No Repeated Characters**: Cannot have more than 2 consecutive identical characters
- **No Sequential Characters**: Cannot contain sequential patterns (123, abc, etc.)
- **Must Be Different**: New password must be different from current password

## API Endpoint

### POST /api/change_password.php

**Authentication**: Required (JWT token in Authorization header)

**Request Body**:
```json
{
  "current_password": "CurrentPassword123!",
  "new_password": "NewStrongPassword456!",
  "confirm_password": "NewStrongPassword456!"
}
```

**Response Examples**:

#### Success Response
```json
{
  "status": "success",
  "message": "Password changed successfully",
  "rate_limit": {
    "allowed": true,
    "current_count": 1,
    "max_requests": 3,
    "remaining_requests": 2
  }
}
```

#### Error Responses

**Missing Fields**:
```json
{
  "status": "error",
  "message": "Current password, new password, and confirm password are required"
}
```

**Password Mismatch**:
```json
{
  "status": "error",
  "message": "New password and confirm password do not match"
}
```

**Weak Password**:
```json
{
  "status": "error",
  "message": "Password must be at least 8 characters long. Password must contain at least one uppercase letter. Password must contain at least one number. Password must contain at least one special character"
}
```

**Incorrect Current Password**:
```json
{
  "status": "error",
  "message": "Current password is incorrect"
}
```

**Same Password**:
```json
{
  "status": "error",
  "message": "New password must be different from current password"
}
```

**Rate Limited**:
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

## Rate Limiting

### Limits
- **3 password changes per hour** per user
- **1 hour block duration** when limit exceeded
- **IP-based limiting** also applies (20 requests per hour per IP)

### Rate Limit Headers
The API includes rate limit information in successful responses:
- `current_count`: Number of requests made in current window
- `max_requests`: Maximum allowed requests
- `remaining_requests`: Requests remaining in current window

## Usage Examples

### cURL Example
```bash
curl -X POST https://api.runnix.africa/api/change_password.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "current_password": "OldPassword123!",
    "new_password": "NewPassword456!",
    "confirm_password": "NewPassword456!"
  }'
```

### JavaScript Example
```javascript
const changePassword = async (currentPassword, newPassword, confirmPassword) => {
  const response = await fetch('https://api.runnix.africa/api/change_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${localStorage.getItem('token')}`
    },
    body: JSON.stringify({
      current_password: currentPassword,
      new_password: newPassword,
      confirm_password: confirmPassword
    })
  });
  
  const data = await response.json();
  return data;
};

// Usage
changePassword('OldPass123!', 'NewPass456!', 'NewPass456!')
  .then(result => {
    if (result.status === 'success') {
      console.log('Password changed successfully');
      console.log('Remaining attempts:', result.rate_limit.remaining_requests);
    } else {
      console.error('Error:', result.message);
    }
  });
```

## Security Considerations

### Best Practices
1. **Always verify current password** before allowing changes
2. **Use strong password requirements** to prevent weak passwords
3. **Implement rate limiting** to prevent brute force attacks
4. **Log all password changes** for security monitoring
5. **Use HTTPS** for all password change requests
6. **Hash passwords securely** using PHP's password_hash()

### Security Measures
- **JWT Authentication**: Ensures only authenticated users can change passwords
- **Current Password Verification**: Prevents unauthorized password changes
- **Rate Limiting**: Prevents abuse and brute force attacks
- **Activity Logging**: Tracks all password change attempts
- **Strong Validation**: Enforces password complexity requirements

## Error Handling

### HTTP Status Codes
- **200**: Success
- **400**: Bad Request (validation errors)
- **401**: Unauthorized (invalid/missing token)
- **404**: Not Found (user not found)
- **429**: Too Many Requests (rate limited)
- **500**: Internal Server Error

### Common Error Scenarios
1. **Invalid Token**: User not authenticated
2. **Missing Fields**: Required fields not provided
3. **Weak Password**: Password doesn't meet requirements
4. **Wrong Current Password**: Current password verification failed
5. **Rate Limited**: Too many attempts in time window
6. **Same Password**: New password same as current password

## Testing

### Test Endpoint
Use `/api/test_change_password.php` to test password validation:

```bash
php backend/api/test_change_password.php
```

### Manual Testing
1. **Login** to get a valid JWT token
2. **Test password change** with valid data
3. **Test validation** with weak passwords
4. **Test rate limiting** with multiple attempts
5. **Test authentication** with invalid token

### Test Cases
- ✅ Valid password change
- ❌ Missing current password
- ❌ Missing new password
- ❌ Password mismatch
- ❌ Weak password
- ❌ Wrong current password
- ❌ Same password
- ❌ Rate limited
- ❌ Invalid token

## Implementation Details

### Controller Method
```php
public function changePassword($userId, $currentPassword, $newPassword)
{
    // Validate inputs
    // Check password strength
    // Verify current password
    // Check if new password is different
    // Hash new password
    // Update database
    // Log activity
    // Return response
}
```

### Password Validation
```php
private function validatePasswordStrength($password)
{
    // Check length (8-128 characters)
    // Check uppercase letter
    // Check lowercase letter
    // Check number
    // Check special character
    // Check against common passwords
    // Check for repeated characters
    // Check for sequential characters
    // Return validation result
}
```

### Rate Limiting
- Uses the same rate limiting system as OTP requests
- Tracks attempts per user (phone/email)
- Includes IP-based limiting
- Returns rate limit info in responses

## Monitoring and Logging

### Activity Logging
All password changes are logged with:
- User ID
- Action type (password_change)
- Identifier (email/phone)
- IP address
- User agent
- Timestamp

### Security Monitoring
- Monitor for multiple failed attempts
- Alert on suspicious activity
- Track rate limit violations
- Review password change patterns

## Future Enhancements

1. **Two-Factor Authentication**: Require 2FA for password changes
2. **Password History**: Prevent reusing recent passwords
3. **Account Lockout**: Temporary lockout after multiple failures
4. **Email Notifications**: Notify user of password changes
5. **Admin Override**: Allow admins to reset passwords
6. **Password Expiry**: Force password changes periodically

## Troubleshooting

### Common Issues
1. **"Current password is incorrect"**: User provided wrong current password
2. **"Password too weak"**: New password doesn't meet requirements
3. **"Rate limited"**: Too many attempts in time window
4. **"User not found"**: Invalid user ID in token
5. **"Authentication required"**: Missing or invalid JWT token

### Debug Steps
1. Check JWT token validity
2. Verify password requirements
3. Check rate limit status
4. Review error logs
5. Test with valid data

### Error Logs
Check `php-error.log` for detailed error information:
```bash
tail -f backend/php-error.log
```
