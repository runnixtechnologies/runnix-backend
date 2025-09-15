# PUT Request Debugging Guide

## Issue: "Internal server error during update" with PUT method

Since you're using the PUT method and no errors are showing in the error log, the issue is likely in the request processing or data parsing stage.

## Enhanced Debugging Added

I've added comprehensive logging to help identify the exact issue:

### 1. PUT Request Processing Logs
Look for these log entries:
```
=== PROCESSING PUT REQUEST ===
Content-Type: [content type]
Content-Length: [length]
Raw PUT input: [raw data]
Parsed JSON Data: [parsed data]
=== END PUT PROCESSING ===
```

### 2. Data Validation Logs
```
Final processed data: [final data]
Data type: [type]
Data count: [count]
Data keys: [keys]
```

### 3. Authentication Logs
```
Attempting authentication...
User authenticated successfully: [user data]
```

### 4. Controller Logs
```
Creating FoodItemController...
Controller created successfully
Calling controller->update() with:
  Data: [data]
  User: [user]
```

## Common PUT Request Issues

### 1. Content-Type Header Missing
**Problem**: Server doesn't know how to parse the data
**Solution**: Ensure your mobile app sends `Content-Type: application/json`

### 2. JSON Parsing Issues
**Problem**: Data is not valid JSON
**Solution**: Check the "Raw PUT input" log to see what's being sent

### 3. Empty Data
**Problem**: No data is being received
**Solution**: Check if the PUT request body is being sent correctly

### 4. Authentication Issues
**Problem**: Token not being sent or invalid
**Solution**: Check Authorization header format

## Debugging Steps

### Step 1: Check Request Format
Ensure your mobile app is sending:
```
PUT /backend/api/update_food_item.php
Content-Type: application/json
Authorization: Bearer [your-token]

{
  "id": 1,
  "name": "Updated Item",
  "price": 15.99,
  "category_id": 1
}
```

### Step 2: Run Test Script
Use the test script I created:
```bash
php backend/test_put_request.php
```

### Step 3: Check Error Logs
After making a request, check the logs for:
1. PUT request processing logs
2. Data parsing logs
3. Authentication logs
4. Any error messages

### Step 4: Verify Server Configuration
Ensure your server supports PUT requests:
- Apache: Check if mod_rewrite is enabled
- Nginx: Check PUT method configuration
- PHP: Check if PUT data parsing is enabled

## Expected Log Flow

When working correctly, you should see:
1. `=== PROCESSING PUT REQUEST ===`
2. `Raw PUT input: [your JSON data]`
3. `Parsed JSON Data: [parsed data]`
4. `Final processed data: [final data]`
5. `Attempting authentication...`
6. `User authenticated successfully: [user data]`
7. `Creating FoodItemController...`
8. `Calling controller->update() with: [data and user]`

## If No Logs Appear

If you don't see any logs at all, the issue might be:
1. **Server not reaching the PHP script** - Check URL and routing
2. **PHP error logging disabled** - Check PHP configuration
3. **Wrong log file location** - Check where PHP errors are being logged
4. **Request not reaching the endpoint** - Check network/firewall issues

## Next Steps

1. **Make a PUT request** from your mobile app
2. **Check the error logs** immediately after
3. **Share the log output** - especially the PUT processing section
4. **If no logs appear**, check server configuration and PHP error logging

The enhanced logging will show exactly where the process is failing and what data is being received.
