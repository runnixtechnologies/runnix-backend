-- Migration: add email_change to otp_requests.purpose enum
ALTER TABLE otp_requests
  MODIFY COLUMN purpose ENUM('signup','login','password_reset','phone_change','email_change','2fa','business_phone_update') NOT NULL;
