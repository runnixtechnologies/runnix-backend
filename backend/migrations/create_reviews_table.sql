-- Migration: Create reviews table for store ratings
-- Purpose: Support AVG rating and COUNT reviews in store listings

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  store_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,

  rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),

  order_id INT UNSIGNED NULL,
  title VARCHAR(120) NULL,
  comment TEXT NULL,

  status TINYINT(1) NOT NULL DEFAULT 1,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_reviews_store
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

  INDEX idx_reviews_store (store_id),
  INDEX idx_reviews_user (user_id),
  INDEX idx_reviews_store_status (store_id, status),
  INDEX idx_reviews_store_rating (store_id, rating),
  UNIQUE KEY uq_store_user_order (store_id, user_id, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


