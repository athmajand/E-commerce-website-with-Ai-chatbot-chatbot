#!/bin/bash

# List of JavaScript files to update
files=(
  "frontend/js/admin/admin-common.js"
  "frontend/js/cart.js"
  "frontend/js/checkout.js"
  "frontend/js/customer-service-detail.js"
  "frontend/js/customer-service.js"
  "frontend/js/dashboard.js"
  "frontend/js/notifications.js"
  "frontend/js/order-confirmation.js"
  "frontend/js/order-details.js"
  "frontend/js/orders.js"
  "frontend/js/payment.js"
  "frontend/js/product-details.js"
  "frontend/js/product-ratings.js"
  "frontend/js/profile.js"
  "frontend/js/seller/seller-common.js"
  "frontend/js/wishlist.js"
)

# Update each file
for file in "${files[@]}"; do
  echo "Updating $file..."
  sed -i 's|const API_BASE_URL = '\''http://localhost:8080/api'\''|const API_BASE_URL = '\''http://localhost:8080/Kisankart/api'\''|g' "$file"
done

echo "All files updated successfully!"
