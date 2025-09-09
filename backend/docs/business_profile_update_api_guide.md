# Business Profile Update API Guide

This guide covers the business profile update functionality with OTP verification for phone number changes, matching the UI/UX design provided.

## Overview

The business profile update system allows merchants to update their store information including:
- Store Name
- Business Address  
- Business Phone Number
- Business Registration Number

**Important**: Phone number changes require OTP verification before the form can be submitted.

## API Endpoints

### 1. Send OTP for Business Phone Update

**Endpoint**: `POST /api/send_business_phone_otp.php`

**Purpose**: Send OTP to a new business phone number for verification

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <jwt_token>
```

**Request Body**:
```json
{
    "phone": "08102940964"
}
```

**Response**:
```json
{
    "status": "success",
    "message": "OTP sent to phone"
}
```

**Error Responses**:
- `400`: Invalid phone format or missing phone number
- `401`: Unauthorized (invalid/missing token)
- `403`: User is not a merchant
- `409`: Phone number already exists
- `500`: Failed to send OTP

### 2. Verify OTP for Business Phone Update

**Endpoint**: `POST /api/verify_business_phone_otp.php`

**Purpose**: Verify the OTP sent to the business phone number

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <jwt_token>
```

**Request Body**:
```json
{
    "phone": "08102940964",
    "otp": "231456"
}
```

**Response**:
```json
{
    "status": "success",
    "message": "OTP verified successfully"
}
```

**Error Responses**:
- `400`: Invalid phone/OTP format or missing fields
- `401`: Unauthorized or invalid/expired OTP
- `403`: User is not a merchant
- `500`: Internal server error

### 3. Update Business Profile

**Endpoint**: `PUT /api/update_business_profile.php`

**Purpose**: Update business profile information (requires OTP verification if phone is changed)

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <jwt_token>
```

**Request Body**:
```json
{
    "store_name": "Palmy Mart",
    "biz_address": "103 Kishi-Iseyin Road, Kishi, Ilorin",
    "biz_phone": "08102940964",
    "biz_reg_number": "RN5344949309"
}
```

**Response**:
```json
{
    "status": "success",
    "message": "Business profile updated successfully",
    "data": {
        "store_name": "Palmy Mart",
        "biz_address": "103 Kishi-Iseyin Road, Kishi, Ilorin",
        "biz_phone": "2348102940964",
        "biz_reg_number": "RN5344949309"
    }
}
```

**Error Responses**:
- `400`: Missing required fields, invalid phone format, or OTP not verified for phone change
- `401`: Unauthorized (invalid/missing token)
- `403`: User is not a merchant or unauthorized access to store
- `409`: Store name, phone number, or registration number already exists
- `500`: Failed to update business profile

## Workflow

### For Phone Number Changes:

1. **User enters new phone number** in the form
2. **Send OTP**: Call `POST /api/send_business_phone_otp.php` with the new phone number
3. **User receives OTP** via SMS
4. **User enters OTP** in the verification modal
5. **Verify OTP**: Call `POST /api/verify_business_phone_otp.php` with phone and OTP
6. **Submit form**: Call `PUT /api/update_business_profile.php` with all form data

### For Other Field Changes:

1. **User updates fields** (store name, address, registration number)
2. **Submit form**: Call `PUT /api/update_business_profile.php` with all form data

## Validation Rules

### Store Name
- Required field
- Must be unique across all stores
- Trimmed of whitespace

### Business Address
- Required field
- Trimmed of whitespace

### Business Phone Number
- Required field
- Must be 10 or 11 digits (Nigerian format)
- Automatically formatted to international format (234XXXXXXXXXX)
- Must be unique across all stores
- Requires OTP verification if changed

### Business Registration Number
- Required field
- Must be unique across all stores
- Trimmed of whitespace

## Security Features

1. **Authentication**: All endpoints require valid JWT token
2. **Authorization**: Only merchants can update business profiles
3. **Store Ownership**: Users can only update their own store
4. **OTP Verification**: Phone number changes require OTP verification
5. **Uniqueness Validation**: Prevents duplicate store names, phone numbers, and registration numbers
6. **Transaction Safety**: Database updates use transactions for data integrity

## Error Handling

All endpoints include comprehensive error logging and return appropriate HTTP status codes with descriptive error messages.

## Testing

### Test Cases:

1. **Valid Update (No Phone Change)**:
   ```bash
   curl -X PUT /api/update_business_profile.php \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{
       "store_name": "Updated Store Name",
       "biz_address": "New Address",
       "biz_phone": "08102940964",
       "biz_reg_number": "RN123456789"
     }'
   ```

2. **Phone Change with OTP**:
   ```bash
   # Step 1: Send OTP
   curl -X POST /api/send_business_phone_otp.php \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"phone": "08123456789"}'
   
   # Step 2: Verify OTP
   curl -X POST /api/verify_business_phone_otp.php \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"phone": "08123456789", "otp": "123456"}'
   
   # Step 3: Update Profile
   curl -X PUT /api/update_business_profile.php \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{
       "store_name": "Store Name",
       "biz_address": "Address",
       "biz_phone": "08123456789",
       "biz_reg_number": "RN123456789"
     }'
   ```

## Notes

- Phone numbers are automatically converted to international format (234XXXXXXXXXX)
- OTP expires in 10 minutes
- All database operations are logged for debugging
- The system prevents duplicate business information across all stores
- Form validation matches the UI/UX design requirements
