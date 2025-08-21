# Profile & Business Management API Guide

This document describes the profile management and business management API endpoints that were updated yesterday.

## Authentication

All endpoints require authentication using a valid JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Profile Management Endpoints

### 1. Get User Profile

**Endpoint:** `GET /api/get-profile.php`

**Description:** Retrieves the current user's profile information.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request:**
```bash
curl -X GET "https://api.runnix.africa/api/get-profile.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

**Sample Response:**
```json
{
    "status": "success",
    "data": {
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "2348031234567",
        "address": "123 Main Street, Lagos",
        "profile_picture": "https://api.runnix.africa/uploads/profiles/user_123_abc123.jpg"
    }
}
```

### 2. Update User Profile

**Endpoint:** `PUT /api/update-profile.php`

**Description:** Updates the current user's profile information.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "address": "123 Main Street, Lagos"
}
```

**Sample cURL Request:**
```bash
curl -X PUT "https://api.runnix.africa/api/update-profile.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "address": "123 Main Street, Lagos"
  }'
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "Profile updated successfully"
}
```

**Error Responses:**
- `400`: Missing required fields (name, email)
- `409`: Email already taken by another user
- `500`: Failed to update profile

### 3. Update Profile Picture

**Endpoint:** `POST /api/update-profile-picture.php`

**Description:** Uploads and updates the user's profile picture.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: multipart/form-data
```

**Sample Request Body (Form Data):**
```
profile_picture: [file upload - JPEG/PNG, max 2MB]
```

**Sample cURL Request:**
```bash
curl -X POST "https://api.runnix.africa/api/update-profile-picture.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "profile_picture=@/path/to/your/image.jpg"
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "Profile picture updated successfully",
    "data": {
        "profile_picture_url": "https://api.runnix.africa/uploads/profiles/user_123_abc123.jpg"
    }
}
```

**Error Responses:**
- `400`: Profile picture is required
- `413`: Image exceeds max size of 2MB
- `415`: Unsupported image format (use JPEG or PNG)
- `500`: Failed to upload profile picture

## Business Management Endpoints

### 4. Get Operating Hours

**Endpoint:** `GET /api/get-operating-hours.php`

**Description:** Retrieves the store's operating hours (merchants only).

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request:**
```bash
curl -X GET "https://api.runnix.africa/api/get-operating-hours.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

**Sample Response:**
```json
{
    "status": "success",
    "data": {
        "monday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "tuesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "wednesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "thursday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "friday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "saturday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        },
        "sunday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        }
    }
}
```

**Error Responses:**
- `403`: Only merchants can access operating hours
- `500`: Failed to retrieve operating hours

### 5. Update Operating Hours

**Endpoint:** `PUT /api/update-operating-hours.php`

**Description:** Updates the store's operating hours (merchants only).

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "operating_hours": {
        "monday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "tuesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "wednesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "thursday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "friday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "saturday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        },
        "sunday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        }
    }
}
```

**Sample cURL Request:**
```bash
curl -X PUT "https://api.runnix.africa/api/update-operating-hours.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "operating_hours": {
        "monday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "tuesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "wednesday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "thursday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "friday": {
            "is_closed": false,
            "open_time": "09:00",
            "close_time": "17:00"
        },
        "saturday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        },
        "sunday": {
            "is_closed": true,
            "open_time": null,
            "close_time": null
        }
    }
}'
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "Operating hours updated successfully"
}
```

**Error Responses:**
- `400`: Missing or invalid operating hours data
- `403`: Only merchants can update operating hours
- `500`: Failed to update operating hours

### 6. Set Business Type

**Endpoint:** `POST /api/business-type.php`

**Description:** Sets the business type for a merchant account.

**Headers:**
```
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "business_type": "restaurant"
}
```

**Sample cURL Request:**
```bash
curl -X POST "https://api.runnix.africa/api/business-type.php" \
  -H "Content-Type: application/json" \
  -d '{
    "business_type": "restaurant"
  }'
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "Business type updated successfully"
}
```

**Error Responses:**
- `400`: Business type is required
- `500`: Failed to update business type

## Additional Business Management Features

### 7. Change Password

**Endpoint:** `PUT /api/change-password.php`

**Description:** Changes the user's password.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123"
}
```

### 8. Change Phone Number

**Endpoint:** `PUT /api/change-phone-number.php`

**Description:** Changes the user's phone number.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "new_phone": "2348031234567"
}
```

### 9. Delete Account

**Endpoint:** `DELETE /api/delete-account.php`

**Description:** Deletes the user's account.

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

## Database Schema Updates

### New Tables Created:
1. **operating_hours** - Stores store operating hours
2. **user_profiles** - Enhanced with profile_picture column

### New Columns Added:
- `user_profiles.profile_picture` - VARCHAR(255) for profile picture URLs

## File Upload Structure

### Profile Pictures:
- **Upload Directory:** `/uploads/profiles/`
- **File Naming:** `user_{user_id}_{unique_id}.{extension}`
- **Max Size:** 2MB
- **Allowed Formats:** JPEG, PNG
- **URL Format:** `https://api.runnix.africa/uploads/profiles/{filename}`

## Usage Examples

### JavaScript/Fetch API

```javascript
// Get user profile
const getProfile = async () => {
    const response = await fetch('/api/get-profile.php', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Update profile
const updateProfile = async (profileData) => {
    const response = await fetch('/api/update-profile.php', {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(profileData)
    });
    return await response.json();
};

// Update profile picture
const updateProfilePicture = async (file) => {
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    const response = await fetch('/api/update-profile-picture.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        body: formData
    });
    return await response.json();
};

// Get operating hours
const getOperatingHours = async () => {
    const response = await fetch('/api/get-operating-hours.php', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Update operating hours
const updateOperatingHours = async (operatingHours) => {
    const response = await fetch('/api/update-operating-hours.php', {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ operating_hours: operatingHours })
    });
    return await response.json();
};
```

## Notes

1. **Profile Management**: All users can manage their profiles
2. **Business Management**: Only merchants can access business-related endpoints
3. **File Uploads**: Profile pictures are stored securely with unique naming
4. **Operating Hours**: Supports 7-day week with flexible open/close times
5. **Validation**: Comprehensive input validation for all endpoints
6. **Error Handling**: Detailed error messages for debugging
