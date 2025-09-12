# Soft Delete Account Feature

## Overview
This feature implements **soft deletion** for user accounts, which is a standard practice used by major platforms like Facebook, Instagram, Twitter, and LinkedIn. Instead of permanently deleting user data, accounts are marked as deleted but preserved for audit purposes, legal compliance, and potential reactivation.

## Key Benefits

### ✅ **Legal & Compliance**
- **Data Retention Laws** - Maintains data for audit purposes
- **Financial Records** - Preserves transaction history for accounting/tax
- **Dispute Resolution** - Keeps data for potential legal issues

### ✅ **Business Benefits**
- **User Recovery** - Users can reactivate their accounts
- **Analytics** - Historical data for business insights
- **Fraud Prevention** - Detect patterns in deleted accounts
- **Customer Support** - Help users with past transactions

### ✅ **Technical Benefits**
- **Data Integrity** - Maintains foreign key relationships
- **Audit Trail** - Complete history of user actions
- **Backup/Restore** - Easier data management

## Database Schema Changes

### New Fields Added to `users` Table:
```sql
ALTER TABLE users 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when account was soft deleted',
ADD COLUMN deleted_by INT NULL DEFAULT NULL COMMENT 'User ID who initiated the deletion',
ADD COLUMN deletion_reason TEXT NULL DEFAULT NULL COMMENT 'Reason provided by user',
ADD COLUMN deletion_method ENUM('self', 'admin', 'system') DEFAULT 'self' COMMENT 'Method used to delete',
ADD COLUMN can_reactivate BOOLEAN DEFAULT TRUE COMMENT 'Whether account can be reactivated',
ADD COLUMN reactivation_deadline TIMESTAMP NULL DEFAULT NULL COMMENT 'Deadline for reactivation';
```

## API Endpoints

### 1. **Soft Delete Account** (User)
- **Endpoint**: `POST /api/soft_delete_account.php`
- **Authentication**: Required
- **Request Body**:
```json
{
  "confirmation": "yes",
  "reason": "No longer using the service" // Optional
}
```
- **Response**:
```json
{
  "status": "success",
  "message": "Your account has been successfully deleted. You can reactivate it within 30 days by contacting support.",
  "reactivation_deadline": "2024-02-15 10:30:00",
  "can_reactivate": true
}
```

### 2. **Check Account Status** (User)
- **Endpoint**: `GET /api/check_account_status.php`
- **Authentication**: Required
- **Response**:
```json
{
  "status": "success",
  "account_status": "active", // or "deleted"
  "deleted_at": "2024-01-15 10:30:00",
  "deletion_reason": "No longer using the service",
  "reactivation_deadline": "2024-02-15 10:30:00",
  "can_reactivate": true
}
```

### 3. **Get Deleted Accounts** (Admin)
- **Endpoint**: `GET /api/admin/get_deleted_accounts.php`
- **Authentication**: Required (Admin only)
- **Query Parameters**: `page`, `limit`
- **Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 123,
      "email": "user@example.com",
      "phone": "+1234567890",
      "role": "merchant",
      "deleted_at": "2024-01-15 10:30:00",
      "deletion_reason": "No longer using the service",
      "deletion_method": "self",
      "reactivation_deadline": "2024-02-15 10:30:00",
      "can_reactivate": true,
      "first_name": "John",
      "last_name": "Doe"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "total_pages": 3,
    "has_next": true,
    "has_prev": false
  }
}
```

### 4. **Reactivate Account** (Admin)
- **Endpoint**: `POST /api/admin/reactivate_account.php`
- **Authentication**: Required (Admin only)
- **Request Body**:
```json
{
  "user_id": 123
}
```
- **Response**:
```json
{
  "status": "success",
  "message": "Account has been successfully reactivated"
}
```

## User Flow

### **Account Deletion Flow:**
1. User clicks "Delete Account" button
2. System shows confirmation prompt: "Are you sure you want to delete your account?"
3. User clicks "Yes" → Frontend sends `confirmation: "yes"` to API
4. System soft deletes account and returns success message
5. User is logged out and cannot access the system
6. Account data is preserved but hidden from other users

### **Account Reactivation Flow:**
1. User contacts support requesting account reactivation
2. Admin checks deleted accounts list
3. Admin reactivates account via admin endpoint
4. User can log in again with all data restored

## Security Features

### **Audit Logging**
- All deletion attempts are logged with user ID, timestamp, and reason
- All reactivation attempts are logged with admin ID and timestamp
- Complete audit trail for compliance

### **Access Control**
- Only authenticated users can delete their own accounts
- Only admins can view deleted accounts and reactivate them
- Soft-deleted users cannot log in or access the system

### **Data Protection**
- Soft-deleted users are excluded from all public queries
- Account appears "gone" to other users
- Data remains accessible to admins for support purposes

## Implementation Details

### **Model Methods Added:**
- `softDeleteUser()` - Marks account as deleted
- `reactivateUser()` - Restores deleted account
- `isUserSoftDeleted()` - Checks deletion status
- `getSoftDeletedUsers()` - Gets deleted accounts (admin)

### **Query Updates:**
All user queries now include `AND deleted_at IS NULL` to exclude soft-deleted users:
- `getUserByEmail()`
- `getUserByPhone()`
- `getUserById()`
- `login()`
- `getUserByReferralCode()`

### **Controller Methods Added:**
- `softDeleteAccount()` - Handles user-initiated deletion
- `reactivateAccount()` - Handles admin-initiated reactivation

## Configuration

### **Reactivation Period**
- Default: 30 days from deletion
- Configurable in `User::softDeleteUser()` method
- Can be extended or made permanent based on business needs

### **Deletion Methods**
- `self` - User-initiated deletion
- `admin` - Admin-initiated deletion
- `system` - System-initiated deletion (future use)

## Migration Instructions

1. **Run Database Migration**:
```bash
mysql -u username -p database_name < backend/migrations/add_soft_delete_fields.sql
```

2. **Test the Feature**:
- Create a test account
- Soft delete it via API
- Verify it's hidden from normal queries
- Reactivate it via admin API
- Verify it's accessible again

## Future Enhancements

### **Potential Improvements:**
- **Automatic Cleanup** - Permanently delete accounts after extended period
- **Bulk Operations** - Admin tools for bulk reactivation/deletion
- **Email Notifications** - Notify users of reactivation deadlines
- **Analytics Dashboard** - Track deletion patterns and reasons
- **Export Functionality** - Export deleted account data for compliance

## Compliance Notes

This implementation follows industry best practices and helps with:
- **GDPR Compliance** - Right to be forgotten (with audit trail)
- **Financial Regulations** - Transaction history preservation
- **Legal Discovery** - Data available for legal proceedings
- **Customer Support** - Historical data for issue resolution

---

**Note**: This feature is designed to be secure, compliant, and user-friendly while maintaining data integrity and providing comprehensive audit capabilities.
