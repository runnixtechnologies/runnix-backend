<?php
namespace Model;

use Config\Database;
use PDO;

class Analytics
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Check if store has food items
     */
    public function hasFoodItems($storeId)
    {
        $sql = "SELECT COUNT(*) as count FROM food_items WHERE store_id = :store_id AND deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['store_id' => $storeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get total revenue for a store within date range
     */
    public function getTotalRevenue($storeId, $dateRangeData)
    {
        $sql = "SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.store_id = :store_id
                AND o.status IN ('completed', 'delivered')
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.store_id = :store_id
                    AND o.status IN ('completed', 'delivered')
                    AND o.created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)$result['total_revenue'];
    }

    /**
     * Get total orders for a store within date range
     */
    public function getTotalOrders($storeId, $dateRangeData)
    {
        $sql = "SELECT COUNT(*) as total_orders
                FROM orders
                WHERE store_id = :store_id
                AND created_at >= :start_date
                AND created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT COUNT(*) as total_orders
                    FROM orders
                    WHERE store_id = :store_id
                    AND created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total_orders'];
    }

    /**
     * Get total profile visits for a store within date range
     */
    public function getTotalProfileVisits($storeId, $dateRangeData)
    {
        // For now, we'll simulate this with order views or use a separate visits table if it exists
        // This could be implemented with a store_visits table in the future
        $sql = "SELECT COUNT(DISTINCT o.user_id) as total_visits
                FROM orders o
                WHERE o.store_id = :store_id
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT COUNT(DISTINCT o.user_id) as total_visits
                    FROM orders o
                    WHERE o.store_id = :store_id
                    AND o.created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total_visits'];
    }

    /**
     * Get total users (customers) for a store within date range
     */
    public function getTotalUsers($storeId, $dateRangeData)
    {
        $sql = "SELECT COUNT(DISTINCT o.user_id) as total_users
                FROM orders o
                WHERE o.store_id = :store_id
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT COUNT(DISTINCT o.user_id) as total_users
                    FROM orders o
                    WHERE o.store_id = :store_id
                    AND o.created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total_users'];
    }

    /**
     * Get average response time for a store within date range
     */
    public function getAvgResponseTime($storeId, $dateRangeData)
    {
        // This would typically come from order processing times or response logs
        // For now, we'll simulate with order processing time
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at)) as avg_response_time
                FROM orders o
                WHERE o.store_id = :store_id
                AND o.status IN ('completed', 'delivered')
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at)) as avg_response_time
                    FROM orders o
                    WHERE o.store_id = :store_id
                    AND o.status IN ('completed', 'delivered')
                    AND o.created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return round((float)$result['avg_response_time'], 1);
    }

    /**
     * Get total rating for a store within date range
     */
    public function getTotalRating($storeId, $dateRangeData)
    {
        // This would typically come from a reviews/ratings table
        // For now, we'll simulate with a default rating
        $sql = "SELECT AVG(COALESCE(rating, 4.5)) as total_rating
                FROM orders o
                WHERE o.store_id = :store_id
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT AVG(COALESCE(rating, 4.5)) as total_rating
                    FROM orders o
                    WHERE o.store_id = :store_id
                    AND o.created_at <= :end_date";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return round((float)$result['total_rating'], 1);
    }

    /**
     * Get orders analytics for bar chart (daily breakdown)
     */
    public function getOrdersAnalytics($storeId, $dateRangeData)
    {
        $sql = "SELECT 
                    DATE(o.created_at) as order_date,
                    COUNT(*) as order_count,
                    SUM(oi.quantity * oi.price) as daily_revenue
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.store_id = :store_id
                AND o.created_at >= :start_date
                AND o.created_at <= :end_date
                GROUP BY DATE(o.created_at)
                ORDER BY order_date ASC";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date']
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT 
                        DATE(o.created_at) as order_date,
                        COUNT(*) as order_count,
                        SUM(oi.quantity * oi.price) as daily_revenue
                    FROM orders o
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.store_id = :store_id
                    AND o.created_at <= :end_date
                    GROUP BY DATE(o.created_at)
                    ORDER BY order_date ASC";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for frontend consumption
        $analytics = [];
        foreach ($results as $row) {
            $analytics[] = [
                'date' => $row['order_date'],
                'orders' => (int)$row['order_count'],
                'revenue' => (float)$row['daily_revenue']
            ];
        }
        
        return $analytics;
    }

    /**
     * Get top performing food items
     */
    public function getTopFoodItems($storeId, $dateRangeData, $limit = 5)
    {
        $sql = "SELECT 
                    fi.id,
                    fi.name,
                    fi.price,
                    fi.photo,
                    fi.short_description,
                    COUNT(oi.id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                FROM food_items fi
                LEFT JOIN order_items oi ON fi.id = oi.item_id AND oi.item_type = 'food_item'
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE fi.store_id = :store_id
                AND fi.deleted = 0
                AND (o.created_at >= :start_date OR o.created_at IS NULL)
                AND (o.created_at <= :end_date OR o.created_at IS NULL)
                GROUP BY fi.id
                ORDER BY total_revenue DESC, order_count DESC
                LIMIT :limit";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date'],
            'limit' => $limit
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT 
                        fi.id,
                        fi.name,
                        fi.price,
                        fi.photo,
                        fi.short_description,
                        COUNT(oi.id) as order_count,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.quantity * oi.price) as total_revenue
                    FROM food_items fi
                    LEFT JOIN order_items oi ON fi.id = oi.item_id AND oi.item_type = 'food_item'
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE fi.store_id = :store_id
                    AND fi.deleted = 0
                    AND (o.created_at <= :end_date OR o.created_at IS NULL)
                    GROUP BY fi.id
                    ORDER BY total_revenue DESC, order_count DESC
                    LIMIT :limit";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format results
        $items = [];
        foreach ($results as $row) {
            $items[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'photo' => $row['photo'],
                'short_description' => $row['short_description'],
                'order_count' => (int)$row['order_count'],
                'total_quantity' => (int)$row['total_quantity'],
                'total_revenue' => (float)$row['total_revenue']
            ];
        }
        
        return $items;
    }

    /**
     * Get top performing regular items (non-food)
     */
    public function getTopItems($storeId, $dateRangeData, $limit = 5)
    {
        $sql = "SELECT 
                    i.id,
                    i.name,
                    i.price,
                    i.photo,
                    i.short_description,
                    COUNT(oi.id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                FROM items i
                LEFT JOIN order_items oi ON i.id = oi.item_id AND oi.item_type = 'item'
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE i.store_id = :store_id
                AND i.deleted = 0
                AND (o.created_at >= :start_date OR o.created_at IS NULL)
                AND (o.created_at <= :end_date OR o.created_at IS NULL)
                GROUP BY i.id
                ORDER BY total_revenue DESC, order_count DESC
                LIMIT :limit";
        
        $params = [
            'store_id' => $storeId,
            'start_date' => $dateRangeData['start_date'],
            'end_date' => $dateRangeData['end_date'],
            'limit' => $limit
        ];
        
        // Handle all time case
        if ($dateRangeData['start_date'] === null) {
            $sql = "SELECT 
                        i.id,
                        i.name,
                        i.price,
                        i.photo,
                        i.short_description,
                        COUNT(oi.id) as order_count,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.quantity * oi.price) as total_revenue
                    FROM items i
                    LEFT JOIN order_items oi ON i.id = oi.item_id AND oi.item_type = 'item'
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE i.store_id = :store_id
                    AND i.deleted = 0
                    AND (o.created_at <= :end_date OR o.created_at IS NULL)
                    GROUP BY i.id
                    ORDER BY total_revenue DESC, order_count DESC
                    LIMIT :limit";
            unset($params['start_date']);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format results
        $items = [];
        foreach ($results as $row) {
            $items[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'photo' => $row['photo'],
                'short_description' => $row['short_description'],
                'order_count' => (int)$row['order_count'],
                'total_quantity' => (int)$row['total_quantity'],
                'total_revenue' => (float)$row['total_revenue']
            ];
        }
        
        return $items;
    }

    /**
     * Get analytics for specific date range with comparison
     */
    public function getAnalyticsComparison($storeId, $currentRange, $previousRange, $isFoodStore)
    {
        $currentMetrics = [
            'revenue' => $this->getTotalRevenue($storeId, $currentRange),
            'orders' => $this->getTotalOrders($storeId, $currentRange),
            'users' => $this->getTotalUsers($storeId, $currentRange),
            'profile_visits' => $this->getTotalProfileVisits($storeId, $currentRange)
        ];
        
        $previousMetrics = [
            'revenue' => $this->getTotalRevenue($storeId, $previousRange),
            'orders' => $this->getTotalOrders($storeId, $previousRange),
            'users' => $this->getTotalUsers($storeId, $previousRange),
            'profile_visits' => $this->getTotalProfileVisits($storeId, $previousRange)
        ];
        
        // Calculate growth percentages
        $growth = [];
        foreach ($currentMetrics as $key => $current) {
            $previous = $previousMetrics[$key] ?? 0;
            if ($previous > 0) {
                $growth[$key] = round((($current - $previous) / $previous) * 100, 2);
            } else {
                $growth[$key] = $current > 0 ? 100 : 0;
            }
        }
        
        return [
            'current' => $currentMetrics,
            'previous' => $previousMetrics,
            'growth' => $growth
        ];
    }
}
