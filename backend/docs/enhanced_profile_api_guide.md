# Enhanced Profile API Guide

## Overview
The enhanced profile endpoint now provides comprehensive user information, including additional merchant business details when the user is a merchant.

## Endpoint
```
GET /api/get-profile.php
```

## Authentication
- Requires valid JWT token in Authorization header
- Token must contain user_id and role information

## Response Structure

### For All Users (Basic Profile)
```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "user_id": 123,
    "role": "merchant|user|rider",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "2348031234567",
    "email": "john.doe@example.com",
    "address": "123 Main Street",
    "profile_picture": "https://api.runnix.africa/uploads/profiles/profile_123.jpg",
    "is_verified": true,
    "status": "active",
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-15 14:30:00",
    "business": null  // Only for merchants with store setup
  }
}
```

### For Merchants (Enhanced Profile)
When the user is a merchant and has a store setup, additional business information is included:

```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "user_id": 123,
    "role": "merchant",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "2348031234567",
    "email": "john.doe@example.com",
    "address": "123 Main Street",
    "profile_picture": "https://api.runnix.africa/uploads/profiles/profile_123.jpg",
    "is_verified": true,
    "status": "active",
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-15 14:30:00",
    "business": {
      "store_name": "John's Restaurant",
      "business_address": "456 Business Avenue, Lagos",
      "business_email": "business@johnsrestaurant.com",
      "business_phone": "2348037654321",
      "business_registration_number": "RC123456789",
      "business_logo": "https://api.runnix.africa/uploads/logos/logo_123.jpg",
      "business_url": "https://www.johnsrestaurant.com",
      "store_id": 45,
      "store_type_id": 2,
      "operating_hours": {
        "business_24_7": false,
        "operating_hours": {
          "monday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "08:00:00",
            "close_time": "22:00:00"
          },
          "tuesday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "08:00:00",
            "close_time": "22:00:00"
          },
          "wednesday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "08:00:00",
            "close_time": "22:00:00"
          },
          "thursday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "08:00:00",
            "close_time": "22:00:00"
          },
          "friday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "08:00:00",
            "close_time": "23:00:00"
          },
          "saturday": {
            "enabled": true,
            "is_24hrs": false,
            "is_closed": false,
            "open_time": "09:00:00",
            "close_time": "23:00:00"
          },
          "sunday": {
            "enabled": false,
            "is_24hrs": false,
            "is_closed": true,
            "open_time": null,
            "close_time": null
          }
        }
      }
    }
  }
}
```

## Field Descriptions

### Basic Profile Fields
- **user_id**: Unique identifier for the user
- **role**: User role (merchant, user, rider)
- **first_name**: User's first name
- **last_name**: User's last name
- **phone**: User's phone number (international format)
- **email**: User's email address
- **address**: User's personal address
- **profile_picture**: URL to user's profile picture
- **is_verified**: Whether the user account is verified
- **status**: Account status (active, inactive, etc.)
- **created_at**: Account creation timestamp
- **updated_at**: Last profile update timestamp

### Business Fields (Merchants Only)
- **business**: Object containing business information (null for non-merchants or merchants without store setup)
  - **store_name**: Name of the business/store
  - **business_address**: Physical address of the business
  - **business_email**: Business email address
  - **business_phone**: Business phone number
  - **business_registration_number**: Official business registration number
  - **business_logo**: URL to business logo image
  - **business_url**: Business website URL (optional)
  - **store_id**: Internal store identifier
  - **store_type_id**: Type of store (restaurant, retail, etc.)
  - **operating_hours**: Business operating hours information
    - **business_24_7**: Whether the business operates 24/7
    - **operating_hours**: Object with days of the week as keys
      - Each day contains:
        - **enabled**: Whether the day is enabled for business
        - **is_24hrs**: Whether the business is open 24 hours on this day
        - **is_closed**: Whether the business is closed on this day
        - **open_time**: Opening time (HH:MM:SS format)
        - **close_time**: Closing time (HH:MM:SS format)

## Error Responses

### User Not Found
```json
{
  "status": "error",
  "message": "User not found"
}
```

### Server Error
```json
{
  "status": "error",
  "message": "Failed to retrieve profile"
}
```

## Usage Examples

### JavaScript/Fetch
```javascript
const response = await fetch('/api/get-profile.php', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();

if (data.status === 'success') {
  const profile = data.data;
  
  // Access basic profile information
  console.log('Name:', profile.first_name, profile.last_name);
  console.log('Email:', profile.email);
  console.log('Phone:', profile.phone);
  
  // Access business information (merchants only)
  if (profile.role === 'merchant' && profile.business) {
    console.log('Store Name:', profile.business.store_name);
    console.log('Business Address:', profile.business.business_address);
    console.log('Operating Hours:', profile.business.operating_hours);
  }
}
```

### PHP/cURL
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.runnix.africa/api/get-profile.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['status'] === 'success') {
    $profile = $data['data'];
    
    // Access profile information
    echo "Name: " . $profile['first_name'] . " " . $profile['last_name'] . "\n";
    echo "Email: " . $profile['email'] . "\n";
    
    // Access business information for merchants
    if ($profile['role'] === 'merchant' && $profile['business']) {
        echo "Store: " . $profile['business']['store_name'] . "\n";
        echo "Address: " . $profile['business']['business_address'] . "\n";
    }
}
```

## Migration Requirements

To use the enhanced profile endpoint with business URL support, run the migration:

```bash
php backend/migrations/add_business_url_to_stores.php
```

This will add the `biz_url` field to the stores table if it doesn't already exist.

## Notes

1. **Business Information**: Only available for users with role "merchant" who have completed store setup
2. **Operating Hours**: Returns null if no operating hours are configured for the store
3. **Business URL**: Optional field that may be null if not set by the merchant
4. **Profile Picture**: Returns null if no profile picture is uploaded
5. **Business Logo**: Returns null if no business logo is uploaded
6. **Backward Compatibility**: The endpoint maintains backward compatibility with existing clients

## Testing

Use the provided test script to verify the enhanced profile endpoint:

```bash
php backend/test_enhanced_profile_endpoint.php
```

This will test the endpoint with both merchant and non-merchant users to ensure proper functionality.
