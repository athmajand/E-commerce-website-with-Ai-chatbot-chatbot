# Session ID Changes in Kisan Kart

## Overview

This document explains the changes made to handle both `user_id` and `customer_id` session variables in the Kisan Kart application.

## Problem

There was an inconsistency in the codebase where some files were using `$_SESSION['user_id']` while others were using `$_SESSION['customer_id']` to identify the logged-in customer. This caused issues when users logged in and tried to access certain pages.

## Solution

1. **Modified login.php**: Updated to set both `$_SESSION['user_id']` and `$_SESSION['customer_id']` when a user logs in.

2. **Created session_helper.php**: Added helper functions to:
   - Get the customer ID from either session variable
   - Synchronize both session variables
   - Check if a user is logged in
   - Check if a user is a customer or seller

3. **Updated files that use customer_id**: Modified files like `customer_wishlist.php` and `delivery_address.php` to check for both session variables.

4. **Updated api/check_login.php**: Modified to check for both session variables and return consistent responses.

5. **Created update_session_ids.php**: A utility script to update existing sessions to include both IDs.

## How to Use

1. **For new code**: Use the helper functions in `api/helpers/session_helper.php`:
   ```php
   include_once __DIR__ . '/api/helpers/session_helper.php';
   
   // Get customer ID
   $customer_id = getCustomerIdFromSession();
   
   // Check if logged in
   if (isLoggedIn()) {
       // User is logged in
   }
   
   // Check if customer
   if (isCustomer()) {
       // User is a customer
   }
   ```

2. **For existing sessions**: Run `update_session_ids.php` to synchronize session variables for currently logged-in users.

## Technical Details

- Both `$_SESSION['user_id']` and `$_SESSION['customer_id']` now refer to the same value: the `id` field from the `customer_registrations` table.
- The login process sets both variables to ensure compatibility with all parts of the application.
- The logout process clears all session variables, so no changes were needed there.

## Future Improvements

In the future, we should standardize on using just one session variable (`customer_id`) throughout the codebase for consistency. This current implementation serves as a transition to ensure backward compatibility.
