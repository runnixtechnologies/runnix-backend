<?php
namespace Service;

use Config\Database;
use PDO;

class DiscountService
{
	private $conn;

	public function __construct()
	{
		$this->conn = (new Database())->getConnection();
	}

	/**
	 * Resolve active discount for an item and return discount meta.
	 * @param int $itemId
	 * @param string $itemType One of: 'pack', 'food_side', 'food_section'
	 * @return array{discount_id: ?int, percentage: float, start_date: ?string, end_date: ?string}
	 */
	public function getActiveDiscountMeta(int $itemId, string $itemType): array
	{
		$sql = "SELECT d.id AS discount_id, d.percentage, d.start_date, d.end_date
				FROM discount_items di
				JOIN discounts d ON d.id = di.discount_id
				WHERE di.item_id = :item_id
				  AND di.item_type = :item_type
				  AND d.status = 'active'
				  AND NOW() BETWEEN d.start_date AND d.end_date
				ORDER BY d.percentage DESC, d.end_date ASC, d.id ASC
				LIMIT 1";

		$stmt = $this->conn->prepare($sql);
		$stmt->execute(['item_id' => $itemId, 'item_type' => $itemType]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return [
				'discount_id' => null,
				'percentage' => 0.0,
				'start_date' => null,
				'end_date' => null
			];
		}

		return [
			'discount_id' => (int)$row['discount_id'],
			'percentage' => (float)$row['percentage'],
			'start_date' => $row['start_date'],
			'end_date' => $row['end_date']
		];
	}

	/**
	 * Compute discounted price given base price and percentage.
	 */
	public static function computeDiscountedPrice(float $price, float $percentage): float
	{
		if ($percentage <= 0) {
			return $price;
		}
		return round($price - ($price * $percentage / 100.0), 2);
	}
}
