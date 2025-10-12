# Mobile App Data Format for Food Item Creation

## ❌ **INCORRECT Format (What you're currently sending)**

```javascript
{
  category_id: 1, 
  name: "Food One", 
  price: 1000.0, 
  short_description: "One!", 
  sides: {
    required: true, 
    max_quantity: 1, 
    items: [10]
  }, 
  packs: {
    required: true, 
    max_quantity: 1, 
    items: [11]
  }, 
  sections: []
}
```

**Problem**: Missing quotes around property names! This is NOT valid JSON.

## ✅ **CORRECT Format (What you should send)**

```json
{
  "category_id": 1,
  "name": "Food One",
  "price": 1000.0,
  "short_description": "One!",
  "sides": {
    "required": true,
    "max_quantity": 1,
    "items": [10]
  },
  "packs": {
    "required": true,
    "max_quantity": 1,
    "items": [11]
  },
  "sections": []
}
```

## 🔑 **Key Points**

1. **All property names must be in quotes**: `"category_id"` not `category_id`
2. **String values must be in quotes**: `"Food One"` not `Food One`
3. **Boolean values**: `true` and `false` (no quotes)
4. **Numbers**: No quotes needed
5. **Arrays**: Use square brackets `[10]`
6. **Objects**: Use curly braces `{"key": "value"}`

## 📱 **Mobile App Implementation**

### **JavaScript/TypeScript**
```typescript
const foodItemData = {
  category_id: 1,
  name: "Food One",
  price: 1000.0,
  short_description: "One!",
  sides: {
    required: true,
    max_quantity: 1,
    items: [10]
  },
  packs: {
    required: true,
    max_quantity: 1,
    items: [11]
  },
  sections: []
};

// Convert to JSON string for API call
const jsonData = JSON.stringify(foodItemData);

// Send to API
fetch('/api/create_food_item.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: jsonData
});
```

### **React Native Example**
```javascript
const createFoodItem = async () => {
  const foodItemData = {
    category_id: 1,
    name: "Food One",
    price: 1000.0,
    short_description: "One!",
    sides: {
      required: true,
      max_quantity: 1,
      items: [10]
    },
    packs: {
      required: true,
      max_quantity: 1,
      items: [11]
    },
    sections: []
  };

  try {
    const response = await fetch('https://your-api.com/api/create_food_item.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(foodItemData)
    });

    const result = await response.json();
    console.log('Result:', result);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

## 🚨 **Common Mistakes to Avoid**

1. **Missing quotes around property names**
2. **Missing quotes around string values**
3. **Using single quotes instead of double quotes**
4. **Trailing commas in JSON**
5. **Sending raw object instead of JSON string**

## 🧪 **Testing Your JSON**

Before sending to the API, validate your JSON:

1. **Use JSON.stringify()** to convert your object to JSON
2. **Test on jsonlint.com** to validate the format
3. **Check the Network tab** in browser dev tools to see what's actually sent

## 📋 **Expected Response**

If successful, you should get:
```json
{
  "status": "success",
  "message": "Food item created successfully",
  "data": {
    "id": 123,
    "name": "Food One",
    "price": 1000.0
  }
}
```

If there's an error, you'll get:
```json
{
  "status": "error",
  "message": "Specific error message"
}
```

## 🔧 **Debugging**

If you still get errors:
1. Check the server error logs
2. Verify your JSON format is valid
3. Ensure all required fields are present
4. Check that the `items` arrays contain valid IDs
