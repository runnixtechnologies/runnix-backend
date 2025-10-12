<?php
namespace Controller;

use Model\Analytics;
use Model\Store;
use Model\User;

class AnalyticsController
{
    private $analyticsModel;
    private $storeModel;
    private $userModel;

    public function __construct()
    {
        $this->analyticsModel = new Analytics();
        $this->storeModel = new Store();
        $this->userModel = new User();
    }

    /**
     * Get merchant analytics dashboard data
     */
    public function getMerchantAnalytics($storeId, $dateRange = 'this_week', $startDate = null, $endDate = null)
    {
        try {
            // Validate store exists and get store type
            $store = $this->storeModel->getStoreById($storeId);
            if (!$store) {
                return [
                    'status' => 'error',
                    'message' => 'Store not found'
                ];
            }

            // Determine store type (food vs non-food)
            $isFoodStore = $this->isFoodStore($store);
            
            // Calculate date range
            $dateRangeData = $this->calculateDateRange($dateRange, $startDate, $endDate);
            
            // Get core metrics
            $metrics = $this->getCoreMetrics($storeId, $dateRangeData, $isFoodStore);
            
            // Get orders analytics for bar chart
            $ordersAnalytics = $this->getOrdersAnalyticsData($storeId, $dateRangeData);
            
            // Get top performing items
            $topItems = $this->getTopPerformingItemsData($storeId, $dateRangeData, $isFoodStore);
            
            return [
                'status' => 'success',
                'data' => [
                    'store_info' => [
                        'id' => $store['id'],
                        'name' => $store['store_name'],
                        'type' => $isFoodStore ? 'food' : 'non_food',
                        'store_type' => $store['store_type_name'] ?? 'Unknown'
                    ],
                    'date_range' => [
                        'period' => $dateRange,
                        'start_date' => $dateRangeData['start_date'],
                        'end_date' => $dateRangeData['end_date']
                    ],
                    'metrics' => $metrics,
                    'orders_analytics' => $ordersAnalytics,
                    'top_performing_items' => $topItems
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Analytics Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch analytics data'
            ];
        }
    }

    /**
     * Determine if store is a food store
     */
    private function isFoodStore($store)
    {
        // Check if store has food items or if store type indicates food
        $storeTypeName = strtolower($store['store_type_name'] ?? '');
        $foodKeywords = ['food', 'restaurant', 'cafe', 'kitchen', 'dining'];
        
        foreach ($foodKeywords as $keyword) {
            if (strpos($storeTypeName, $keyword) !== false) {
                return true;
            }
        }
        
        // Additional check: if store has food_items, it's a food store
        return $this->analyticsModel->hasFoodItems($store['id']);
    }

    /**
     * Calculate date range based on period
     */
    private function calculateDateRange($period, $startDate = null, $endDate = null)
    {
        $now = new \DateTime();
        $start = new \DateTime();
        $end = new \DateTime();

        switch ($period) {
            case 'today':
                $start->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
                break;
                
            case 'yesterday':
                $start->modify('-1 day')->setTime(0, 0, 0);
                $end->modify('-1 day')->setTime(23, 59, 59);
                break;
                
            case 'this_week':
                $start->modify('monday this week')->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
                break;
                
            case 'last_week':
                $start->modify('monday last week')->setTime(0, 0, 0);
                $end->modify('sunday last week')->setTime(23, 59, 59);
                break;
                
            case 'this_month':
                $start->modify('first day of this month')->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
                break;
                
            case 'last_month':
                $start->modify('first day of last month')->setTime(0, 0, 0);
                $end->modify('last day of last month')->setTime(23, 59, 59);
                break;
                
            case 'this_year':
                $start->modify('first day of january this year')->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
                break;
                
            case 'all_time':
                $start = null; // No start limit
                $end->setTime(23, 59, 59);
                break;
                
            case 'custom':
                if ($startDate && $endDate) {
                    $start = new \DateTime($startDate);
                    $end = new \DateTime($endDate);
                    $start->setTime(0, 0, 0);
                    $end->setTime(23, 59, 59);
                }
                break;
                
            default:
                // Default to this week
                $start->modify('monday this week')->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
        }

        return [
            'start_date' => $start ? $start->format('Y-m-d H:i:s') : null,
            'end_date' => $end->format('Y-m-d H:i:s'),
            'start_timestamp' => $start ? $start->getTimestamp() : null,
            'end_timestamp' => $end->getTimestamp()
        ];
    }

    /**
     * Get core metrics for the dashboard cards
     */
    private function getCoreMetrics($storeId, $dateRangeData, $isFoodStore)
    {
        return [
            'total_revenue' => $this->analyticsModel->getTotalRevenue($storeId, $dateRangeData),
            'total_orders' => $this->analyticsModel->getTotalOrders($storeId, $dateRangeData),
            'total_profile_visits' => $this->analyticsModel->getTotalProfileVisits($storeId, $dateRangeData),
            'total_users' => $this->analyticsModel->getTotalUsers($storeId, $dateRangeData),
            'avg_response_time' => $this->analyticsModel->getAvgResponseTime($storeId, $dateRangeData),
            'total_rating' => $this->analyticsModel->getTotalRating($storeId, $dateRangeData)
        ];
    }

    /**
     * Get orders analytics for bar chart (private helper)
     */
    private function getOrdersAnalyticsData($storeId, $dateRangeData)
    {
        return $this->analyticsModel->getOrdersAnalytics($storeId, $dateRangeData);
    }

    /**
     * Get top performing items (private helper)
     */
    private function getTopPerformingItemsData($storeId, $dateRangeData, $isFoodStore)
    {
        if ($isFoodStore) {
            return $this->analyticsModel->getTopFoodItems($storeId, $dateRangeData, 5);
        } else {
            return $this->analyticsModel->getTopItems($storeId, $dateRangeData, 5);
        }
    }

    /**
     * Get merchant metrics only (for dashboard cards)
     */
    public function getMerchantMetrics($storeId, $dateRange = 'this_week', $startDate = null, $endDate = null)
    {
        try {
            // Validate store exists and get store type
            $store = $this->storeModel->getStoreById($storeId);
            if (!$store) {
                return [
                    'status' => 'error',
                    'message' => 'Store not found'
                ];
            }

            // Determine store type (food vs non-food)
            $isFoodStore = $this->isFoodStore($store);
            
            // Calculate date range
            $dateRangeData = $this->calculateDateRange($dateRange, $startDate, $endDate);
            
            // Get core metrics only
            $metrics = $this->getCoreMetrics($storeId, $dateRangeData, $isFoodStore);
            
            return [
                'status' => 'success',
                'data' => [
                    'store_info' => [
                        'id' => $store['id'],
                        'name' => $store['store_name'],
                        'type' => $isFoodStore ? 'food' : 'non_food',
                        'store_type' => $store['store_type_name'] ?? 'Unknown'
                    ],
                    'date_range' => [
                        'period' => $dateRange,
                        'start_date' => $dateRangeData['start_date'],
                        'end_date' => $dateRangeData['end_date']
                    ],
                    'metrics' => $metrics
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Merchant Metrics Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch metrics data'
            ];
        }
    }

    /**
     * Get orders analytics only (for bar chart)
     */
    public function getOrdersAnalytics($storeId, $dateRange = 'this_week', $startDate = null, $endDate = null)
    {
        try {
            // Validate store exists
            $store = $this->storeModel->getStoreById($storeId);
            if (!$store) {
                return [
                    'status' => 'error',
                    'message' => 'Store not found'
                ];
            }

            // Calculate date range
            $dateRangeData = $this->calculateDateRange($dateRange, $startDate, $endDate);
            
            // Get orders analytics data
            $ordersAnalytics = $this->getOrdersAnalyticsData($storeId, $dateRangeData);
            
            return [
                'status' => 'success',
                'data' => [
                    'store_info' => [
                        'id' => $store['id'],
                        'name' => $store['store_name']
                    ],
                    'date_range' => [
                        'period' => $dateRange,
                        'start_date' => $dateRangeData['start_date'],
                        'end_date' => $dateRangeData['end_date']
                    ],
                    'orders_analytics' => $ordersAnalytics
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Orders Analytics Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch orders analytics data'
            ];
        }
    }

    /**
     * Get top performing items only
     */
    public function getTopPerformingItems($storeId, $dateRange = 'this_week', $startDate = null, $endDate = null, $limit = 5)
    {
        try {
            // Validate store exists and get store type
            $store = $this->storeModel->getStoreById($storeId);
            if (!$store) {
                return [
                    'status' => 'error',
                    'message' => 'Store not found'
                ];
            }

            // Determine store type (food vs non-food)
            $isFoodStore = $this->isFoodStore($store);
            
            // Calculate date range
            $dateRangeData = $this->calculateDateRange($dateRange, $startDate, $endDate);
            
            // Get top performing items
            $topItems = $isFoodStore 
                ? $this->analyticsModel->getTopFoodItems($storeId, $dateRangeData, $limit)
                : $this->analyticsModel->getTopItems($storeId, $dateRangeData, $limit);
            
            return [
                'status' => 'success',
                'data' => [
                    'store_info' => [
                        'id' => $store['id'],
                        'name' => $store['store_name'],
                        'type' => $isFoodStore ? 'food' : 'non_food',
                        'store_type' => $store['store_type_name'] ?? 'Unknown'
                    ],
                    'date_range' => [
                        'period' => $dateRange,
                        'start_date' => $dateRangeData['start_date'],
                        'end_date' => $dateRangeData['end_date']
                    ],
                    'top_performing_items' => $topItems,
                    'limit' => $limit
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Top Performing Items Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch top performing items data'
            ];
        }
    }

    /**
     * Get analytics summary for quick overview
     */
    public function getAnalyticsSummary($storeId)
    {
        try {
            $store = $this->storeModel->getStoreById($storeId);
            if (!$store) {
                return [
                    'status' => 'error',
                    'message' => 'Store not found'
                ];
            }

            $isFoodStore = $this->isFoodStore($store);
            
            // Get current week data
            $thisWeek = $this->calculateDateRange('this_week');
            $lastWeek = $this->calculateDateRange('last_week');
            
            $currentMetrics = $this->getCoreMetrics($storeId, $thisWeek, $isFoodStore);
            $previousMetrics = $this->getCoreMetrics($storeId, $lastWeek, $isFoodStore);
            
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
                'status' => 'success',
                'data' => [
                    'current_metrics' => $currentMetrics,
                    'growth_percentages' => $growth,
                    'store_type' => $isFoodStore ? 'food' : 'non_food'
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Analytics Summary Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch analytics summary'
            ];
        }
    }
}
