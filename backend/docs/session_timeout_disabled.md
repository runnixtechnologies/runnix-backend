# Session Timeout Disabled

## Overview
The automatic session timeout system has been **completely disabled** for all user types (user, merchant, and rider). Users will no longer be automatically logged out due to inactivity.

## What Changed

### 1. JWT Token Expiry
- **Before**: 15 minutes (900 seconds)
- **After**: 30 days (2,592,000 seconds)
- **File**: `backend/config/JwtHandler.php`

### 2. Inactivity-Based Session Expiry
- **Before**: Users were logged out after 15 minutes of inactivity
- **After**: No automatic logout due to inactivity
- **File**: `backend/config/JwtHandler.php` (decode method)

### 3. Session Cleanup Script
- **Before**: Cron job cleaned up expired sessions every hour
- **After**: Script is disabled and won't run
- **File**: `backend/scripts/cleanup_expired_sessions.php`

### 4. Frontend Session Manager
- **Before**: JavaScript tracked inactivity and showed warnings
- **After**: Completely disabled
- **File**: `frontend/js/session-manager.js`

### 5. API Endpoints Updated
- **session_status.php**: Now shows "No timeout - 30 days"
- **update_activity.php**: No more timeout warnings

## Current Behavior

✅ **Users stay logged in for 30 days** regardless of activity level
✅ **No automatic logout** due to inactivity
✅ **No session timeout warnings** displayed
✅ **Manual logout still works** via logout endpoint
✅ **Token blacklisting still works** for security

## How to Re-enable (If Needed)

### Option 1: Quick Re-enable
1. Change JWT expiry back to 15 minutes in `JwtHandler.php`
2. Remove the `exit()` statement from `cleanup_expired_sessions.php`
3. Remove the `return;` statement from `session-manager.js`
4. Restore inactivity checks in `JwtHandler.php` decode method

### Option 2: Custom Timeout
1. Modify the JWT expiry time in `JwtHandler.php`
2. Adjust the timeout values in `UserActivity.php`
3. Update warning thresholds in API endpoints

## Security Considerations

- **Longer sessions** mean compromised tokens remain valid longer
- **Consider implementing** additional security measures like:
  - IP address validation
  - Device fingerprinting
  - Suspicious activity detection
  - Regular password changes

## Files Modified

1. `backend/config/JwtHandler.php` - JWT expiry and inactivity checks
2. `backend/scripts/cleanup_expired_sessions.php` - Disabled cleanup script
3. `frontend/js/session-manager.js` - Disabled frontend manager
4. `backend/api/session_status.php` - Updated status messages
5. `backend/api/update_activity.php` - Updated activity messages

## Testing

To verify the changes:
1. Login with any user type
2. Leave the app inactive for 30+ minutes
3. Verify the user is still logged in
4. Check that no timeout warnings appear
5. Confirm manual logout still works

## Notes

- This change affects **ALL user types** (user, merchant, rider)
- The 30-day expiry is a reasonable compromise between convenience and security
- Consider monitoring for any security issues with longer sessions
- Users can still manually logout when needed
