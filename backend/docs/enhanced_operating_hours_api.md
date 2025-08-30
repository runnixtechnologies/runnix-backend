# Enhanced Operating Hours API Documentation

## Overview
The enhanced operating hours API now supports the UI design requirements including:
- **24/7 Business Toggle**: Global setting to make all enabled days 24-hour operation
- **Day Enable/Disable**: Individual day checkboxes to mark days as available/unavailable
- **Flexible Scheduling**: Mix of 24-hour and specific time slots per day

## Endpoints

### 1. Get Operating Hours
**GET** `/backend/api/get-operating-hours.php`

**Authentication:** Required (Merchant only)

**Response:**
```json
{
  "status": "success",
  "data": {
    "business_24_7": false,
    "operating_hours": {
      "monday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "09:00",
        "close_time": "17:00"
      },
      "tuesday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "09:00",
        "close_time": "17:00"
      },
      "wednesday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "09:00",
        "close_time": "17:00"
      },
      "thursday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "09:00",
        "close_time": "17:00"
      },
      "friday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "09:00",
        "close_time": "17:00"
      },
      "saturday": {
        "enabled": true,
        "is_24hrs": false,
        "is_closed": false,
        "open_time": "10:00",
        "close_time": "15:00"
      },
      "sunday": {
        "enabled": false,
        "is_24hrs": false,
        "is_closed": true,
        "open_time": null,
        "close_time": null
      }
    }
  }
}
```

### 2. Update Operating Hours
**PUT** `/backend/api/update-operating-hours.php`

**Authentication:** Required (Merchant only)

**Request Body:**
```json
{
  "business_24_7": false,
  "operating_hours": {
    "monday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "09:00",
      "close_time": "17:00"
    },
    "tuesday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "09:00",
      "close_time": "17:00"
    },
    "wednesday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "09:00",
      "close_time": "17:00"
    },
    "thursday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "09:00",
      "close_time": "17:00"
    },
    "friday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "09:00",
      "close_time": "17:00"
    },
    "saturday": {
      "enabled": true,
      "is_24hrs": false,
      "open_time": "10:00",
      "close_time": "15:00"
    },
    "sunday": {
      "enabled": false,
      "is_24hrs": false,
      "open_time": null,
      "close_time": null
    }
  }
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Operating hours updated successfully"
}
```

## Field Descriptions

### Business 24/7 Toggle
- **Type:** Boolean
- **Description:** When `true`, all enabled days automatically become 24-hour operation
- **Effect:** Overrides individual day `is_24hrs` settings for enabled days

### Day Configuration
Each day supports the following fields:

#### `enabled`
- **Type:** Boolean
- **Description:** Whether the business is open on this day
- **UI Equivalent:** Day checkbox

#### `is_24hrs`
- **Type:** Boolean
- **Description:** Whether this specific day operates 24 hours
- **Note:** When `business_24_7` is true, this is automatically set for enabled days

#### `open_time`
- **Type:** String (HH:MM format)
- **Description:** Opening time for the day
- **Required:** When `enabled` is true and `is_24hrs` is false

#### `close_time`
- **Type:** String (HH:MM format)
- **Description:** Closing time for the day
- **Required:** When `enabled` is true and `is_24hrs` is false

## Usage Examples

### Example 1: Regular Business Hours
```json
{
  "business_24_7": false,
  "operating_hours": {
    "monday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "tuesday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "wednesday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "thursday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "friday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "saturday": {"enabled": true, "is_24hrs": false, "open_time": "10:00", "close_time": "15:00"},
    "sunday": {"enabled": false, "is_24hrs": false, "open_time": null, "close_time": null}
  }
}
```

### Example 2: 24/7 Business
```json
{
  "business_24_7": true,
  "operating_hours": {
    "monday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "tuesday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "wednesday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "thursday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "friday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "saturday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "sunday": {"enabled": false, "is_24hrs": false, "open_time": null, "close_time": null}
  }
}
```

### Example 3: Mixed Schedule
```json
{
  "business_24_7": false,
  "operating_hours": {
    "monday": {"enabled": true, "is_24hrs": false, "open_time": "08:00", "close_time": "18:00"},
    "tuesday": {"enabled": true, "is_24hrs": true, "open_time": null, "close_time": null},
    "wednesday": {"enabled": false, "is_24hrs": false, "open_time": null, "close_time": null},
    "thursday": {"enabled": true, "is_24hrs": false, "open_time": "10:00", "close_time": "16:00"},
    "friday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "saturday": {"enabled": true, "is_24hrs": false, "open_time": "11:00", "close_time": "14:00"},
    "sunday": {"enabled": false, "is_24hrs": false, "open_time": null, "close_time": null}
  }
}
```

## Error Responses

### 400 Bad Request
```json
{
  "status": "error",
  "message": "Operating hours data is required and must be an array"
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "message": "Only merchants can access operating hours"
}
```

### 500 Internal Server Error
```json
{
  "status": "error",
  "message": "Failed to update operating hours"
}
```

## Mobile App Integration

### Boolean Handling
The API supports various boolean formats for mobile app compatibility:
- String: `"true"`, `"false"`
- Numeric: `1`, `0`
- Boolean: `true`, `false`

### Partial Updates
You can send only the days you want to update:
```json
{
  "business_24_7": false,
  "operating_hours": {
    "monday": {"enabled": true, "is_24hrs": false, "open_time": "09:00", "close_time": "17:00"},
    "sunday": {"enabled": false, "is_24hrs": false, "open_time": null, "close_time": null}
  }
}
```

Missing days will be automatically set to disabled.

## Database Schema

### store_operating_hours Table
```sql
CREATE TABLE store_operating_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    open_time TIME NULL,
    close_time TIME NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    enabled BOOLEAN DEFAULT TRUE,
    is_24hrs BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_day (store_id, day_of_week)
);
```

### stores Table (New Column)
```sql
ALTER TABLE stores ADD COLUMN business_24_7 BOOLEAN DEFAULT FALSE;
```
