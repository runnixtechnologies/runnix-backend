<?php
namespace Model;

use Config\Database;
use PDO;

class Order
{
    private $conn;
    private $table = "orders";
    private $itemsTable = "order_items";
    private $selectionsTable = "order_selections";
    private $statusHistoryTable = "order_status_history";
    private $notificationsTable = "order_notifications";
    private $trackingTable = "delivery_tracking";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Create order
     */
    public function createOrder($orderData)
    {
        try {
            $this->conn->beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            $sql = "INSERT INTO {$this->table} 
                    (order_number, user_id, store_id, merchant_id, total_amount, delivery_fee, 
                     tax_amount, final_amount, payment_status, payment_method, delivery_address, 
                     delivery_instructions, customer_note, status, created_at, updated_at) 
                    VALUES (:order_number, :user_id, :store_id, :merchant_id, :total_amount, 
                            :delivery_fee, :tax_amount, :final_amount, :payment_status, :payment_method, 
                            :delivery_address, :delivery_instructions, :customer_note, :status, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':order_number' => $orderNumber,
                ':user_id' => $orderData['user_id'],
                ':store_id' => $orderData['store_id'],
                ':merchant_id' => $orderData['merchant_id'],
                ':total_amount' => $orderData['total_amount'],
                ':delivery_fee' => $orderData['delivery_fee'],
                ':tax_amount' => $orderData['tax_amount'],
                ':final_amount' => $orderData['final_amount'],
                ':payment_status' => $orderData['payment_status'],
                ':payment_method' => $orderData['payment_method'],
                ':delivery_address' => $orderData['delivery_address'],
                ':delivery_instructions' => $orderData['delivery_instructions'],
                ':customer_note' => $orderData['customer_note'],
                ':status' => $orderData['status']
            ]);

            $orderId = $this->conn->lastInsertId();

            // Add status history
            $this->addStatusHistory($orderId, $orderData['status'], $orderData['user_id']);

            $this->conn->commit();
            return $orderId;

        } catch (\PDOException $e) {
            $this->conn->rollback();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get store by ID
     */
    public function getStoreById($storeId)
    {
        try {
            $sql = "SELECT s.*, st.name AS store_type_name 
                    FROM stores s 
                    LEFT JOIN store_types st ON s.store_type_id = st.id 
                    WHERE s.id = :store_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':store_id', $storeId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Error getting store by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add status history
     */
    private function addStatusHistory($orderId, $status, $userId)
    {
        try {
            $sql = "INSERT INTO {$this->statusHistoryTable} 
                    (order_id, status, changed_by, created_at) 
                    VALUES (:order_id, :status, :changed_by, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':order_id' => $orderId,
                ':status' => $status,
                ':changed_by' => $userId
            ]);
            
        } catch (\PDOException $e) {
            error_log("Error adding status history: " . $e->getMessage());
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }


    /**
     * Add item to order
     */
    private function addOrderItem($orderId, $item)
    {
        $sql = "INSERT INTO {$this->itemsTable} 
                (order_id, item_id, item_type, item_name, quantity, price, discount, 
                 item_image, item_description, total_price, side_id)
                VALUES (:order_id, :item_id, :item_type, :item_name, :quantity, :price, :discount,
                        :item_image, :item_description, :total_price, :side_id)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':item_id', $item['item_id']);
        $stmt->bindParam(':item_type', $item['item_type'] ?? 'item');
        $stmt->bindParam(':item_name', $item['item_name'] ?? null);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':price', $item['item_price']);
        $stmt->bindParam(':discount', $item['discount'] ?? 0.00);
        $stmt->bindParam(':item_image', $item['item_image'] ?? null);
        $stmt->bindParam(':item_description', $item['item_description'] ?? null);
        $stmt->bindParam(':total_price', $item['total_price']);
        $stmt->bindParam(':side_id', $item['side_id'] ?? null);

        if (!$stmt->execute()) {
            throw new \Exception("Failed to add order item");
        }

        $orderItemId = $this->conn->lastInsertId();

        // Add selections if any
        if (!empty($item['selections'])) {
            foreach ($item['selections'] as $selection) {
                $this->addOrderSelection($orderItemId, $selection);
            }
        }

        return $orderItemId;
    }

    /**
     * Add selection to order item
     */
    private function addOrderSelection($orderItemId, $selection)
    {
        $sql = "INSERT INTO {$this->selectionsTable} 
                (order_item_id, selection_type, selection_name, selection_price, quantity)
                VALUES (:order_item_id, :selection_type, :selection_name, :selection_price, :quantity)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_item_id', $orderItemId);
        $stmt->bindParam(':selection_type', $selection['type']);
        $stmt->bindParam(':selection_name', $selection['name']);
        $stmt->bindParam(':selection_price', $selection['price']);
        $stmt->bindParam(':quantity', $selection['quantity']);

        return $stmt->execute();
    }

    /**
     * Get orders for merchant with pagination
     */
    public function getMerchantOrders($merchantId, $status = null, $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT o.*, 
                           COUNT(oi.id) as item_count,
                           GROUP_CONCAT(COALESCE(oi.item_name, 'Unknown Item') SEPARATOR ', ') as items_summary
                    FROM {$this->table} o
                    LEFT JOIN {$this->itemsTable} oi ON o.id = oi.order_id
                    WHERE o.merchant_id = :merchant_id";
            
            $params = [':merchant_id' => $merchantId];
            
            if ($status) {
                $sql .= " AND o.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " GROUP BY o.id 
                      ORDER BY o.created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $errorMessage = "getMerchantOrders error: " . $e->getMessage() . " | Stack trace: " . $e->getTraceAsString();
            error_log($errorMessage, 3, __DIR__ . '/php-error.log');
            return []; // Return empty array if there's an error
        }
    }

    /**
     * Get order details with items and selections
     */
    public function getOrderDetails($orderId)
    {
        // Get main order info
        // Get name from user_profiles table (plural)
        $sql = "SELECT o.*, 
                       s.store_name,
                       cp.first_name as customer_first_name,
                       cp.last_name as customer_last_name,
                       c.phone as customer_phone,
                       c.email as customer_email,
                       rp.first_name as rider_first_name,
                       rp.last_name as rider_last_name,
                       r.phone as rider_phone,
                       r.email as rider_email
                FROM {$this->table} o
                LEFT JOIN stores s ON o.store_id = s.id
                LEFT JOIN users c ON o.user_id = c.id
                LEFT JOIN user_profiles cp ON c.id = cp.user_id
                LEFT JOIN users r ON o.rider_id = r.id
                LEFT JOIN user_profiles rp ON r.id = rp.user_id
                WHERE o.id = :order_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        // Get order items with selections
        // Join with both items and food_items tables to get item details
        $sql = "SELECT oi.*, 
                       COALESCE(fi.name, i.name) as name,
                       COALESCE(fi.photo, i.photo) as photo,
                       GROUP_CONCAT(
                           CONCAT(os.selection_type, ':', os.selection_name, ':', os.selection_price, ':', os.quantity)
                           SEPARATOR '|'
                       ) as selections
                FROM {$this->itemsTable} oi
                LEFT JOIN food_items fi ON oi.food_item_id = fi.id
                LEFT JOIN items i ON oi.item_id = i.id
                LEFT JOIN {$this->selectionsTable} os ON oi.id = os.order_item_id
                WHERE oi.order_id = :order_id
                GROUP BY oi.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse selections
        foreach ($items as &$item) {
            $item['selections'] = [];
            if ($item['selections']) {
                $selections = explode('|', $item['selections']);
                foreach ($selections as $selection) {
                    if ($selection) {
                        $parts = explode(':', $selection);
                        $item['selections'][] = [
                            'type' => $parts[0],
                            'name' => $parts[1],
                            'price' => $parts[2],
                            'quantity' => $parts[3]
                        ];
                    }
                }
            }
        }
        
        $order['items'] = $items;
        
        // Get status history
        $order['status_history'] = $this->getOrderStatusHistory($orderId);
        
        return $order;
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status, $userId, $reason = null, $notes = null)
    {
        try {
            $this->conn->beginTransaction();
            
            // Update order status
            $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW()";
            
            // Add timestamp based on status
            switch ($status) {
                case 'accepted':
                    $sql .= ", accepted_at = NOW()";
                    break;
                case 'ready_for_pickup':
                    $sql .= ", ready_at = NOW()";
                    break;
                case 'in_transit':
                    $sql .= ", picked_up_at = NOW()";
                    break;
                case 'delivered':
                    $sql .= ", delivered_at = NOW()";
                    break;
                case 'cancelled':
                    $sql .= ", cancelled_at = NOW()";
                    break;
            }
            
            $sql .= " WHERE id = :order_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':order_id', $orderId);
            
            if (!$stmt->execute()) {
                throw new \Exception("Failed to update order status");
            }
            
            // Record status change
            $this->recordStatusChange($orderId, $status, $userId, $reason, $notes);
            
            // Create notifications
            $this->createStatusNotification($orderId, $status);
            
            $this->conn->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("Status update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record status change in history
     */
    private function recordStatusChange($orderId, $status, $userId, $reason = null, $notes = null)
    {
        $sql = "INSERT INTO {$this->statusHistoryTable} 
                (order_id, status, changed_by, change_reason, notes)
                VALUES (:order_id, :status, :changed_by, :change_reason, :notes)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':changed_by', $userId);
        $stmt->bindParam(':change_reason', $reason);
        $stmt->bindParam(':notes', $notes);
        
        return $stmt->execute();
    }

    /**
     * Get order status history
     */
    public function getOrderStatusHistory($orderId)
    {
        $sql = "SELECT osh.*, 
                       up.email as changed_by_email
                FROM {$this->statusHistoryTable} osh
                LEFT JOIN users up ON osh.changed_by = up.id
                WHERE osh.order_id = :order_id
                ORDER BY osh.created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create notification
     */
    private function createNotification($orderId, $userId, $type, $title, $message)
    {
        $sql = "INSERT INTO {$this->notificationsTable} 
                (order_id, user_id, notification_type, title, message)
                VALUES (:order_id, :user_id, :notification_type, :title, :message)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':notification_type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }

    /**
     * Create status-specific notification
     */
    private function createStatusNotification($orderId, $status)
    {
        // Get order details for notification
        $order = $this->getOrderById($orderId);
        if (!$order) return false;
        
        $notifications = [
            'accepted' => [
                'customer' => ['title' => 'Order Accepted', 'message' => "Your order #{$order['order_number']} has been accepted and is being prepared."],
                'merchant' => ['title' => 'Order Status Updated', 'message' => "Order #{$order['order_number']} status updated to accepted."]
            ],
            'ready_for_pickup' => [
                'customer' => ['title' => 'Order Ready', 'message' => "Your order #{$order['order_number']} is ready for pickup."],
                'merchant' => ['title' => 'Order Ready', 'message' => "Order #{$order['order_number']} is ready for pickup."]
            ],
            'in_transit' => [
                'customer' => ['title' => 'Order Picked Up', 'message' => "Your order #{$order['order_number']} has been picked up and is on its way."],
                'merchant' => ['title' => 'Order Picked Up', 'message' => "Order #{$order['order_number']} has been picked up by rider."]
            ],
            'delivered' => [
                'customer' => ['title' => 'Order Delivered', 'message' => "Your order #{$order['order_number']} has been delivered successfully."],
                'merchant' => ['title' => 'Order Delivered', 'message' => "Order #{$order['order_number']} has been delivered to customer."]
            ],
            'cancelled' => [
                'customer' => ['title' => 'Order Cancelled', 'message' => "Your order #{$order['order_number']} has been cancelled."],
                'merchant' => ['title' => 'Order Cancelled', 'message' => "Order #{$order['order_number']} has been cancelled."]
            ]
        ];
        
        if (isset($notifications[$status])) {
            // Notify customer
            $this->createNotification($orderId, $order['customer_id'], $status, 
                $notifications[$status]['customer']['title'], 
                $notifications[$status]['customer']['message']);
            
            // Notify merchant
            $this->createNotification($orderId, $order['merchant_id'], $status, 
                $notifications[$status]['merchant']['title'], 
                $notifications[$status]['merchant']['message']);
        }
        
        return true;
    }

    /**
     * Get order by ID
     */
    public function getOrderById($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :order_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get order by order number
     */
    public function getOrderByNumber($orderNumber)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_number = :order_number";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_number', $orderNumber);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get orders count for merchant
     */
    public function getMerchantOrdersCount($merchantId, $status = null)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE merchant_id = :merchant_id";
            $params = [':merchant_id' => $merchantId];
            
            if ($status) {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch (Exception $e) {
            $errorMessage = "getMerchantOrdersCount error: " . $e->getMessage() . " | Stack trace: " . $e->getTraceAsString();
            error_log($errorMessage, 3, __DIR__ . '/php-error.log');
            return 0; // Return 0 if there's an error
        }
    }

    /**
     * Add delivery tracking
     */
    public function addDeliveryTracking($orderId, $riderId, $latitude, $longitude, $address, $status)
    {
        $sql = "INSERT INTO {$this->trackingTable} 
                (order_id, rider_id, latitude, longitude, address, status)
                VALUES (:order_id, :rider_id, :latitude, :longitude, :address, :status)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':rider_id', $riderId);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }

    /**
     * Get delivery tracking for order
     */
    public function getDeliveryTracking($orderId)
    {
        $sql = "SELECT dt.*, 
                       up.email as rider_email, up.phone as rider_phone
                FROM {$this->trackingTable} dt
                LEFT JOIN users up ON dt.rider_id = up.id
                WHERE dt.order_id = :order_id
                ORDER BY dt.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
