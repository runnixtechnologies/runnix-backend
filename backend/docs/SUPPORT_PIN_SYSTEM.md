# Support Pin System Documentation

## Overview
The Support Pin System provides secure authentication for users when contacting support. Each user account automatically receives a unique 8-character support pin during signup, which must be provided when submitting support requests.

## Features
- **Automatic Generation**: Support pins are generated automatically during user signup
- **Unique Format**: 8 characters (4 letters + 4 numbers, e.g., ABCD1234)
- **Security**: Prevents unauthorized support requests
- **User Verification**: Links support requests to verified user accounts
- **Admin Tools**: Support team can verify pins and manage requests

## Database Schema

### Users Table Addition
```sql
ALTER TABLE users 
ADD COLUMN support_pin VARCHAR(8) UNIQUE NOT NULL AFTER referral_code;
```

### Support Contact Form Table
```sql
CREATE TABLE support_contact_form (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    interest_complaints TEXT NOT NULL,
    message TEXT NOT NULL,
    support_pin VARCHAR(8) NOT NULL,
    user_id INT NOT NULL,
    is_verified BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_support_pin (support_pin),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

## API Endpoints

### 1. Verify Support Pin
**Endpoint**: `POST /api/verify-support-pin.php`

**Request Body**:
```json
{
    "support_pin": "ABCD1234",
    "user_identifier": "user@example.com" // Optional
}
```

**Response**:
```json
{
    "status": "success",
    "message": "Support pin verified successfully",
    "user": {
        "id": 123,
        "email": "user@example.com",
        "phone": "2341234567890",
        "role": "user",
        "is_verified": 1,
        "status": "active",
        "created_at": "2024-01-01 12:00:00"
    }
}
```

### 2. Verify Support Pin for Specific User
**Endpoint**: `POST /api/verify-user-support-pin.php`

**Request Body**:
```json
{
    "user_id": 123,
    "support_pin": "ABCD1234"
}
```

### 3. Get Support Pin
**Endpoint**: `GET /api/get-support-pin.php?user_id=123`
**Endpoint**: `POST /api/get-support-pin.php`

**Request Body**:
```json
{
    "user_id": 123
}
```

### 4. Regenerate Support Pin
**Endpoint**: `POST /api/regenerate-support-pin.php`

**Request Body**:
```json
{
    "user_id": 123
}
```

**Response**:
```json
{
    "status": "success",
    "message": "Support pin regenerated successfully",
    "support_pin": "EFGH5678"
}
```

### 5. Support Contact Form
**Endpoint**: `POST /api/support-contact.php`

**For Pin Verification Only**:
```json
{
    "action": "verify_pin",
    "support_pin": "ABCD1234",
    "user_identifier": "user@example.com"
}
```

**For Full Support Request**:
```json
{
    "fullname": "John Doe",
    "email": "john@example.com",
    "phone": "2341234567890",
    "interest_complaints": "Account Issue",
    "message": "I need help with my account",
    "support_pin": "ABCD1234",
    "user_identifier": "user@example.com"
}
```

## Implementation Details

### User Model Methods
- `generateSupportPin()`: Generates random 8-character pin
- `generateUniqueSupportPin()`: Ensures pin uniqueness
- `isSupportPinExists($pin)`: Checks if pin already exists
- `getUserBySupportPin($pin)`: Retrieves user by support pin
- `verifySupportPin($userId, $pin)`: Verifies pin for specific user
- `regenerateSupportPin($userId)`: Generates new pin for user

### Support Controller Methods
- `verifySupportPin($pin, $identifier)`: Verifies pin with optional user verification
- `verifySupportPinForUser($userId, $pin)`: Verifies pin for specific user
- `regenerateSupportPin($userId)`: Regenerates pin for user
- `getSupportPin($userId)`: Retrieves user's support pin

### Support Contact Controller Methods
- `handleSupportFormSubmission()`: Processes support form with pin verification
- `verifySupportPin()`: Verifies pin without form submission

## Security Features

1. **Format Validation**: Pins must be exactly 8 characters (4 letters + 4 numbers)
2. **Uniqueness**: Each pin is unique across all users
3. **User Verification**: Optional email/phone verification for additional security
4. **Database Constraints**: Foreign key relationships ensure data integrity
5. **Input Sanitization**: All inputs are sanitized and validated

## Usage Examples

### Frontend Integration

#### 1. Display Support Pin to User
```javascript
// Get user's support pin
fetch('/api/get-support-pin.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        user_id: userId
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        document.getElementById('support-pin-display').textContent = data.support_pin;
    }
});
```

#### 2. Verify Support Pin Before Form Submission
```javascript
// Verify support pin
fetch('/api/support-contact.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        action: 'verify_pin',
        support_pin: document.getElementById('support-pin').value,
        user_identifier: document.getElementById('email').value
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        // Enable form submission
        document.getElementById('submit-btn').disabled = false;
    } else {
        alert('Invalid support pin: ' + data.message);
    }
});
```

#### 3. Submit Support Request
```javascript
// Submit support form
fetch('/api/support-contact.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        fullname: document.getElementById('fullname').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        interest_complaints: document.getElementById('interest').value,
        message: document.getElementById('message').value,
        support_pin: document.getElementById('support-pin').value
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        alert('Support request submitted successfully!');
        // Show user info for confirmation
        console.log('User verified:', data.user_info);
    } else {
        alert('Error: ' + data.message);
    }
});
```

## Migration Instructions

1. **Run Database Migrations**:
   ```bash
   # Add support_pin column to users table
   mysql -u username -p database_name < backend/migrations/add_support_pin_to_users.sql
   
   # Create support contact form table
   mysql -u username -p database_name < backend/migrations/create_support_contact_form_table.sql
   ```

2. **Update Existing Users**: The migration script automatically generates support pins for existing users.

3. **Test the System**: Use the API endpoints to verify functionality.

## Error Handling

### Common Error Responses
- `400 Bad Request`: Invalid input or missing required fields
- `404 Not Found`: User or support pin not found
- `500 Internal Server Error`: Database or server errors

### Error Response Format
```json
{
    "status": "error",
    "message": "Error description"
}
```

## Best Practices

1. **Pin Display**: Show support pin prominently in user dashboard
2. **Pin Security**: Advise users to keep their support pin secure
3. **Form Validation**: Validate pin format before submission
4. **User Education**: Explain the purpose of support pins to users
5. **Admin Training**: Train support staff on pin verification process

## Future Enhancements

1. **Pin Expiration**: Add expiration dates for support pins
2. **Pin History**: Track pin changes and usage
3. **Advanced Verification**: Add SMS or email verification for pin changes
4. **Admin Dashboard**: Create admin interface for managing support requests
5. **Analytics**: Track support request patterns and pin usage
