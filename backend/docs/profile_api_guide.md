# Profile Management API Guide

This document describes the Profile Management endpoints for the Runnix multi-vendor marketplace and logistics app. These endpoints allow users (user, merchant, and rider) to view and update their personal profile information.

## Overview

The Profile Management system consists of two main endpoints:
- **GET Profile**: Retrieve user profile information
- **UPDATE Profile**: Update user profile information

All endpoints require JWT authentication and work for all user types (user, merchant, rider).

## Base URL

```
https://api.runnix.africa/api/
```

## Authentication

All profile endpoints require a valid JWT token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### 1. Get Profile

Retrieves the current user's profile information.

**Endpoint:** `GET /get-profile.php`

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response (Success - 200):**
```json
{
    "status": "success",
    "message": "Profile retrieved successfully",
    "data": {
        "user_id": 1,
        "role": "user",
        "first_name": "James",
        "last_name": "Sat",
        "phone": "+234808080808",
        "email": "james.sat@gmail.com",
        "profile_picture": null,
        "is_verified": true,
        "status": "active",
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-01 00:00:00"
    }
}
```

**Response (Error - 404):**
```json
{
    "status": "error",
    "message": "User not found"
}
```

**Response (Error - 500):**
```json
{
    "status": "error",
    "message": "Failed to retrieve profile"
}
```

### 2. Update Profile

Updates the current user's profile information.

**Endpoint:** `PUT /update-profile.php`

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body (JSON Raw):**
```json
{
    "first_name": "James",
    "last_name": "Sat",
    "email": "james.sat@gmail.com",
    "phone": "+234808080808"
}
```

**Field Descriptions:**
- `first_name` (required): User's first name
- `last_name` (required): User's last name
- `email` (optional): User's email address
- `phone` (optional): User's phone number

**Response (Success - 200):**
```json
{
    "status": "success",
    "message": "Profile updated successfully",
    "data": {
        "user_id": 1,
        "role": "user",
        "first_name": "James",
        "last_name": "Sat",
        "phone": "+234808080808",
        "email": "james.sat@gmail.com",
        "profile_picture": null,
        "is_verified": true,
        "status": "active",
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-01 00:00:00"
    }
}
```

**Response (Error - 400):**
```json
{
    "status": "error",
    "message": "First name is required"
}
```

**Response (Error - 400):**
```json
{
    "status": "error",
    "message": "Invalid email format"
}
```

**Response (Error - 409):**
```json
{
    "status": "error",
    "message": "Email is already taken by another user"
}
```

**Response (Error - 500):**
```json
{
    "status": "error",
    "message": "Failed to update profile: [specific error]"
}
```

## Validation Rules

### First Name & Last Name
- Required fields
- Cannot be empty
- Will be trimmed of leading/trailing whitespace

### Email
- Must be a valid email format
- Cannot be already taken by another user
- Optional field (only validated if provided)

### Phone Number
- Must be 10-15 digits
- Automatically formatted to international format (+234)
- Cannot be already taken by another user
- Optional field (only validated if provided)

## Phone Number Formatting

The system automatically handles phone number formatting:

**Input Formats Accepted:**
- `08012345678` → `+2348012345678`
- `8012345678` → `+2348012345678`
- `+2348012345678` → `+2348012345678` (unchanged)

**Validation:**
- Minimum length: 10 digits
- Maximum length: 15 digits
- Must contain only numeric characters

## Error Handling

### Common Error Codes

- **400 Bad Request**: Invalid input data or validation errors
- **401 Unauthorized**: Missing or invalid JWT token
- **404 Not Found**: User not found
- **409 Conflict**: Email or phone already taken by another user
- **500 Internal Server Error**: Server-side errors

### Error Response Format

All error responses follow this format:
```json
{
    "status": "error",
    "message": "Human-readable error description"
}
```

## Security Features

1. **JWT Authentication**: All endpoints require valid JWT tokens
2. **User Isolation**: Users can only access their own profile
3. **Input Validation**: Comprehensive validation of all input data
4. **SQL Injection Protection**: Uses prepared statements
5. **Transaction Safety**: Profile updates use database transactions

## Database Schema

### Users Table
- `id`: Primary key
- `email`: User's email address
- `phone`: User's phone number
- `role`: User role (user/merchant/rider)
- `is_verified`: Email verification status
- `status`: Account status
- `created_at`: Account creation timestamp
- `updated_at`: Last update timestamp

### User Profiles Table
- `user_id`: Foreign key to users table
- `first_name`: User's first name
- `last_name`: User's last name
- `profile_picture`: Profile picture URL
- `created_at`: Profile creation timestamp

**Note:** The `address` field exists in the database but is not part of the profile update functionality. Only first_name, last_name, email, and phone can be updated through the profile endpoints.

## Testing

### Test Script

Use the provided test script to verify endpoint functionality:

```bash
php backend/test_profile_endpoints.php
```

### Manual Testing with Postman

1. **Get Profile:**
   - Method: GET
   - URL: `https://api.runnix.africa/api/get-profile.php`
   - Headers: `Authorization: Bearer <jwt_token>`

2. **Update Profile:**
   - Method: PUT
   - URL: `https://api.runnix.africa/api/update-profile.php`
   - Headers: `Authorization: Bearer <jwt_token>`
   - Body: JSON Raw with profile data

### Sample Test Data

```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "08012345678"
}
```

## Mobile App Integration

### Frontend Implementation

The profile endpoints are designed to work seamlessly with the Edit Profile UI shown in the design:

1. **Load Profile**: Call GET endpoint on screen load
2. **Update Profile**: Call PUT endpoint when Save button is clicked
3. **Validation**: Implement client-side validation matching server rules
4. **Error Handling**: Display appropriate error messages to users

### Data Flow

1. User opens Edit Profile screen
2. App calls GET `/get-profile.php` to load current data
3. User modifies fields and clicks Save
4. App calls PUT `/update-profile.php` with updated data
5. App handles response and shows success/error message

## Rate Limiting

Currently, no rate limiting is implemented. Consider implementing rate limiting for production use.

## Logging

All profile operations are logged for security and debugging purposes:
- Profile retrieval attempts
- Profile update attempts
- Validation errors
- Database errors

## Future Enhancements

1. **Profile Picture Upload**: Separate endpoint for profile picture management
2. **Email Verification**: OTP verification for email changes
3. **Phone Verification**: OTP verification for phone changes
4. **Audit Trail**: Track all profile changes
5. **Profile Export**: Allow users to export their profile data

## Support

For technical support or questions about the Profile Management API, contact the backend development team.
