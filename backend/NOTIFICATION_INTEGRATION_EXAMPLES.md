# Automatic Push Notification Integration Examples

## 🔧 **How to Integrate Automatic Notifications**

### **1. In Your Order Controller (when order status changes)**

```php
<?php
// In your existing order update method
use Service\AutoNotificationService;

public function updateOrderStatus($orderId, $newStatus)
{
    // Your existing order update logic
    $order = $this->getOrder($orderId);
    $this->updateOrderInDatabase($orderId, $newStatus);
    
    // Add automatic notification
    $autoNotification = new AutoNotificationService();
    $notificationResult = $autoNotification->notifyOrderStatusChange(
        $orderId,
        $newStatus,
        $order['merchant_id'],
        $order['order_number'],
        $order['customer_name']
    );
    
    // Log notification result
    if ($notificationResult['status'] === 'success') {
        error_log("Order status notification sent successfully for order: $orderId");
    } else {
        error_log("Failed to send order status notification: " . $notificationResult['message']);
    }
    
    return $this->successResponse();
}
```

### **2. In Your Payment Controller (when payment is processed)**

```php
<?php
// In your existing payment processing method
use Service\AutoNotificationService;

public function processPayment($paymentData)
{
    // Your existing payment processing logic
    $paymentResult = $this->processPaymentWithGateway($paymentData);
    
    if ($paymentResult['status'] === 'success') {
        // Add automatic notification
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyPaymentProcessed(
            $paymentResult['payment_id'],
            $paymentData['merchant_id'],
            $paymentData['amount'],
            $paymentData['method'],
            $paymentResult['transaction_id'],
            $paymentData['order_number'],
            'received'
        );
        
        error_log("Payment notification sent: " . json_encode($notificationResult));
    } else {
        // Notify payment failure
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyPaymentProcessed(
            $paymentResult['payment_id'],
            $paymentData['merchant_id'],
            $paymentData['amount'],
            $paymentData['method'],
            $paymentResult['transaction_id'],
            $paymentData['order_number'],
            'failed'
        );
    }
    
    return $paymentResult;
}
```

### **3. In Your User Controller (when account is verified)**

```php
<?php
// In your existing account verification method
use Service\AutoNotificationService;

public function verifyMerchantAccount($merchantId, $verificationData)
{
    // Your existing verification logic
    $verificationResult = $this->processAccountVerification($merchantId, $verificationData);
    
    if ($verificationResult['status'] === 'success') {
        // Add automatic notification
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyAccountVerification(
            $merchantId,
            'approved',
            $verificationData['admin_notes']
        );
        
        error_log("Account verification notification sent: " . json_encode($notificationResult));
    } else {
        // Notify rejection
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyAccountVerification(
            $merchantId,
            'rejected',
            $verificationData['rejection_reason']
        );
    }
    
    return $verificationResult;
}
```

### **4. In Your Review Controller (when customer leaves review)**

```php
<?php
// In your existing review submission method
use Service\AutoNotificationService;

public function submitReview($reviewData)
{
    // Your existing review processing logic
    $reviewResult = $this->saveReviewToDatabase($reviewData);
    
    if ($reviewResult['status'] === 'success') {
        // Add automatic notification
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyCustomerReview(
            $reviewResult['review_id'],
            $reviewData['merchant_id'],
            $reviewData['customer_name'],
            $reviewData['rating'],
            $reviewData['review_text'],
            $reviewData['order_number']
        );
        
        error_log("Review notification sent: " . json_encode($notificationResult));
    }
    
    return $reviewResult;
}
```

### **5. In Your Message Controller (when customer sends message)**

```php
<?php
// In your existing message handling method
use Service\AutoNotificationService;

public function handleCustomerMessage($messageData)
{
    // Your existing message processing logic
    $messageResult = $this->saveMessageToDatabase($messageData);
    
    if ($messageResult['status'] === 'success') {
        // Add automatic notification
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyCustomerMessage(
            $messageResult['message_id'],
            $messageData['merchant_id'],
            $messageData['customer_name'],
            $messageData['message_text'],
            $messageData['order_id']
        );
        
        error_log("Message notification sent: " . json_encode($notificationResult));
    }
    
    return $messageResult;
}
```

### **6. In Your Order Creation (when new order is placed)**

```php
<?php
// In your existing order creation method
use Service\AutoNotificationService;

public function createOrder($orderData)
{
    // Your existing order creation logic
    $orderResult = $this->createOrderInDatabase($orderData);
    
    if ($orderResult['status'] === 'success') {
        // Add automatic notification
        $autoNotification = new AutoNotificationService();
        $notificationResult = $autoNotification->notifyNewOrder(
            $orderResult['order_id'],
            $orderData['merchant_id'],
            $orderResult['order_number'],
            $orderData['customer_name'],
            $orderData['customer_phone'],
            $orderData['order_total'],
            $orderData['delivery_address'],
            $orderData['items_count']
        );
        
        error_log("New order notification sent: " . json_encode($notificationResult));
    }
    
    return $orderResult;
}
```

## 🚀 **Database Triggers (Alternative Approach)**

You can also use database triggers to automatically send notifications:

```sql
-- Example trigger for order status updates
DELIMITER $$
CREATE TRIGGER order_status_notification_trigger
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        -- Call PHP script to send notification
        SET @cmd = CONCAT('php /path/to/backend/scripts/send_notification.php ',
                         'order_status_update ',
                         NEW.id, ' ',
                         NEW.status, ' ',
                         NEW.merchant_id, ' ',
                         NEW.order_number, ' ',
                         NEW.customer_name);
        SET @result = sys_exec(@cmd);
    END IF;
END$$
DELIMITER ;
```

## 📱 **Mobile App Integration**

### **Register FCM Token (when user logs in)**

```javascript
// In your mobile app
import messaging from '@react-native-firebase/messaging';

const registerFCMToken = async (userId, userType) => {
  try {
    const token = await messaging().getToken();
    
    const response = await fetch('/api/fcm_register_token.php', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        token: token,
        device_type: 'android', // or 'ios'
        device_id: deviceId,
        app_version: '1.0.0'
      })
    });
    
    const result = await response.json();
    console.log('FCM token registered:', result);
  } catch (error) {
    console.error('Failed to register FCM token:', error);
  }
};
```

### **Handle Incoming Notifications**

```javascript
// Handle background notifications
messaging().setBackgroundMessageHandler(async remoteMessage => {
  console.log('Background notification:', remoteMessage);
  
  // Update UI or show local notification
  if (remoteMessage.data.type === 'order') {
    // Navigate to order details
    navigation.navigate('OrderDetails', { orderId: remoteMessage.data.reference_id });
  }
});

// Handle foreground notifications
messaging().onMessage(async remoteMessage => {
  console.log('Foreground notification:', remoteMessage);
  
  // Show in-app notification
  showInAppNotification({
    title: remoteMessage.notification.title,
    body: remoteMessage.notification.body,
    data: remoteMessage.data
  });
});
```

## 🔧 **Testing Automatic Notifications**

### **Test Script**

```php
<?php
// test_auto_notifications.php
require_once 'vendor/autoload.php';
use Service\AutoNotificationService;

$autoNotification = new AutoNotificationService();

// Test new order notification
$result = $autoNotification->notifyNewOrder(
    123, // order_id
    456, // merchant_id
    'ORD-001', // order_number
    'John Doe', // customer_name
    '08012345678', // customer_phone
    '2500', // order_total
    '123 Main Street, Lagos', // delivery_address
    3 // items_count
);

echo "New Order Notification: " . json_encode($result) . "\n";

// Test payment notification
$result = $autoNotification->notifyPaymentProcessed(
    789, // payment_id
    456, // merchant_id
    '2500', // amount
    'Card', // method
    'TXN-123', // transaction_id
    'ORD-001', // order_number
    'received' // status
);

echo "Payment Notification: " . json_encode($result) . "\n";
?>
```

## 📋 **Integration Checklist**

- [ ] Add `use Service\AutoNotificationService;` to your controllers
- [ ] Call notification methods after successful operations
- [ ] Test notifications with real merchant accounts
- [ ] Ensure FCM tokens are registered for all users
- [ ] Monitor notification delivery in Firebase Console
- [ ] Set up error logging for failed notifications
- [ ] Test on both Android and iOS devices

## 🎯 **Key Benefits**

1. **Automatic** - No manual intervention needed
2. **Real-time** - Notifications sent immediately
3. **Reliable** - Multiple channels (push, SMS, email)
4. **User-friendly** - Respects user preferences
5. **Scalable** - Handles high volume of notifications
6. **Trackable** - Full notification history logged

This integration ensures that merchants receive timely notifications about all important events in your marketplace! 🚀
