<?php
namespace Controller;

use Model\Order;
use Model\User;
use Exception;

class OrderController
{
    private $orderModel;
    private $userModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->userModel = new User();
    }

    /**
     * Get merchant orders with pagination
     */
    public function getMerchantOrders($user, $status = null, $page = 1, $limit = 20)
    {
        try {
            $merchantId = $user['user_id'];
            
            // Validate pagination parameters
            $page = max(1, (int)$page);
            $limit = min(50, max(1, (int)$limit)); // Max 50 items per page
            
            // Get orders
            $orders = $this->orderModel->getMerchantOrders($merchantId, $status, $page, $limit);
            
            // Get total count for pagination
            $totalCount = $this->orderModel->getMerchantOrdersCount($merchantId, $status);
            $totalPages = ceil($totalCount / $limit);
            
            // Format orders for response
            $formattedOrders = [];
            foreach ($orders as $order) {
                $formattedOrders[] = $this->formatOrderForList($order);
            }
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $formattedOrders,
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_count' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get merchant orders error: " . $e->getMessage());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve orders'
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails($orderId, $user)
    {
        try {
            $order = $this->orderModel->getOrderDetails($orderId);
            
            if (!$order) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Order not found'
                ];
            }
            
            // Check if user has access to this order
            // Users can access if they are: customer, merchant (store owner), or assigned rider
            if ($order['customer_id'] != $user['user_id'] && 
                $order['merchant_id'] != $user['user_id'] && 
                $order['rider_id'] != $user['user_id']) {
                http_response_code(403);
                return [
                    'status' => 'error',
                    'message' => 'Access denied'
                ];
            }
            
            // Format order for response
            $formattedOrder = $this->formatOrderDetails($order);
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $formattedOrder
            ];
            
        } catch (Exception $e) {
            error_log("Get order details error: " . $e->getMessage());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve order details'
            ];
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status, $user, $reason = null, $notes = null)
    {
        try {
            // Validate status
            $validStatuses = ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Invalid status'
                ];
            }
            
            // Get order
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Order not found'
                ];
            }
            
            // Check permissions based on user role and order relationship
            $userId = $user['user_id'];
            $userRole = $user['role'];
            
            // Only merchant (store owner) can accept/prepare/ready orders
            if (in_array($status, ['accepted', 'preparing', 'ready_for_pickup']) && $order['merchant_id'] != $userId) {
                http_response_code(403);
                return [
                    'status' => 'error',
                    'message' => 'Only the store owner can update order to this status'
                ];
            }
            
            // Only assigned rider can update to in_transit/delivered
            if (in_array($status, ['in_transit', 'delivered']) && $order['rider_id'] != $userId) {
                http_response_code(403);
                return [
                    'status' => 'error',
                    'message' => 'Only the assigned rider can update order to this status'
                ];
            }
            
            // Update status
            $success = $this->orderModel->updateOrderStatus($orderId, $status, $userId, $reason, $notes);
            
            if ($success) {
                http_response_code(200);
                return [
                    'status' => 'success',
                    'message' => 'Order status updated successfully'
                ];
            } else {
                http_response_code(500);
                return [
                    'status' => 'error',
                    'message' => 'Failed to update order status'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to update order status'
            ];
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $user, $reason = null)
    {
        try {
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Order not found'
                ];
            }
            
            // Check if order can be cancelled
            if (in_array($order['status'], ['delivered', 'cancelled'])) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Order cannot be cancelled'
                ];
            }
            
            // Check permissions - only customer or merchant can cancel
            $userId = $user['user_id'];
            if ($order['customer_id'] != $userId && $order['merchant_id'] != $userId) {
                http_response_code(403);
                return [
                    'status' => 'error',
                    'message' => 'Only the customer or store owner can cancel this order'
                ];
            }
            
            // Cancel order
            $success = $this->orderModel->updateOrderStatus($orderId, 'cancelled', $userId, $reason, 'Order cancelled');
            
            if ($success) {
                http_response_code(200);
                return [
                    'status' => 'success',
                    'message' => 'Order cancelled successfully'
                ];
            } else {
                http_response_code(500);
                return [
                    'status' => 'error',
                    'message' => 'Failed to cancel order'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Cancel order error: " . $e->getMessage());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to cancel order'
            ];
        }
    }

    /**
     * Get delivery tracking for order
     */
    public function getDeliveryTracking($orderId, $user)
    {
        try {
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Order not found'
                ];
            }
            
            // Check permissions - customer, merchant, or rider can view tracking
            $userId = $user['user_id'];
            if ($order['customer_id'] != $userId && $order['merchant_id'] != $userId && $order['rider_id'] != $userId) {
                http_response_code(403);
                return [
                    'status' => 'error',
                    'message' => 'Access denied'
                ];
            }
            
            $tracking = $this->orderModel->getDeliveryTracking($orderId);
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $tracking
            ];
            
        } catch (Exception $e) {
            error_log("Get delivery tracking error: " . $e->getMessage());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve delivery tracking'
            ];
        }
    }

    /**
     * Format order for list view
     */
    private function formatOrderForList($order)
    {
        $timeAgo = $this->getTimeAgo($order['created_at']);
        
        return [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'total_amount' => $order['total_amount'],
            'final_amount' => $order['final_amount'],
            'item_count' => $order['item_count'],
            'items_summary' => $order['items_summary'],
            'delivery_address' => $order['delivery_address'],
            'customer_note' => $order['customer_note'],
            'created_at' => $order['created_at'],
            'time_ago' => $timeAgo,
            'can_cancel' => in_array($order['status'], ['pending', 'accepted', 'preparing']),
            'can_accept' => $order['status'] === 'pending',
            'can_prepare' => $order['status'] === 'accepted',
            'can_ready' => $order['status'] === 'preparing'
        ];
    }

    /**
     * Format order details for detailed view
     */
    private function formatOrderDetails($order)
    {
        $timeAgo = $this->getTimeAgo($order['created_at']);
        
        return [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'total_amount' => $order['total_amount'],
            'delivery_fee' => $order['delivery_fee'],
            'tax_amount' => $order['tax_amount'],
            'final_amount' => $order['final_amount'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'],
            'delivery_address' => $order['delivery_address'],
            'delivery_instructions' => $order['delivery_instructions'],
            'customer_note' => $order['customer_note'],
            'merchant_note' => $order['merchant_note'],
            'store_name' => $order['store_name'],
            'customer' => [
                'name' => trim($order['customer_first_name'] . ' ' . $order['customer_last_name']),
                'phone' => $order['customer_phone'],
                'email' => $order['customer_email']
            ],
            'rider' => $order['rider_id'] ? [
                'name' => trim($order['rider_first_name'] . ' ' . $order['rider_last_name']),
                'phone' => $order['rider_phone']
            ] : null,
            'items' => $order['items'],
            'status_history' => $order['status_history'],
            'created_at' => $order['created_at'],
            'time_ago' => $timeAgo,
            'timestamps' => [
                'accepted_at' => $order['accepted_at'],
                'ready_at' => $order['ready_at'],
                'picked_up_at' => $order['picked_up_at'],
                'delivered_at' => $order['delivered_at'],
                'cancelled_at' => $order['cancelled_at']
            ]
        ];
    }

    /**
     * Get time ago string
     */
    private function getTimeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'Just now';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($time / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}
