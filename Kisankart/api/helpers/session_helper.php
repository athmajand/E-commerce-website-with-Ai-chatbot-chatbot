<?php
/**
 * Session Helper Functions
 *
 * This file contains helper functions for managing session variables
 * in the Kisan Kart application, particularly for handling the transition
 * from user_id to customer_id in session variables.
 */

/**
 * Get the customer ID from session
 *
 * This function checks both customer_id and user_id session variables
 * and returns the appropriate value. This helps with backward compatibility
 * during the transition from user_id to customer_id.
 *
 * @return int|null The customer ID or null if not found
 */
function getCustomerIdFromSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check for customer_id first, then fall back to user_id
    if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
        return $_SESSION['customer_id'];
    } else if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }

    return null;
}

/**
 * Ensure both customer_id and user_id are set in session
 *
 * This function ensures that both customer_id and user_id are set
 * in the session, copying from one to the other if only one exists.
 * This helps with backward compatibility during the transition.
 *
 * @return bool True if successful, false if neither ID exists
 */
function synchronizeSessionIds() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // If customer_id exists but user_id doesn't, copy customer_id to user_id
    if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']) &&
        (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))) {
        $_SESSION['user_id'] = $_SESSION['customer_id'];
        return true;
    }

    // If user_id exists but customer_id doesn't, copy user_id to customer_id
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) &&
        (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id']))) {
        $_SESSION['customer_id'] = $_SESSION['user_id'];
        return true;
    }

    // If both exist, make sure they're the same
    if (isset($_SESSION['user_id']) && isset($_SESSION['customer_id']) &&
        $_SESSION['user_id'] != $_SESSION['customer_id']) {
        // Prioritize customer_id
        $_SESSION['user_id'] = $_SESSION['customer_id'];
        return true;
    }

    // Return false if neither ID exists
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
        return false;
    }

    return true;
}

/**
 * Check if user is logged in
 *
 * This function checks if the user is logged in by looking for
 * either customer_id or user_id in the session.
 *
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    return (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) ||
           (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));
}

/**
 * Check if user is a customer
 *
 * This function checks if the logged-in user is a customer
 * by checking the role session variable.
 *
 * @return bool True if customer, false otherwise
 */
function isCustomer() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * Get the seller ID from session
 *
 * This function checks both seller_id and user_id session variables
 * and returns the appropriate value. This helps with backward compatibility
 * during the transition from user_id to seller_id.
 *
 * @return int|null The seller ID or null if not found
 */
function getSellerIdFromSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check for seller_id first, then fall back to user_id
    if (isset($_SESSION['seller_id']) && !empty($_SESSION['seller_id'])) {
        return $_SESSION['seller_id'];
    } else if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) &&
              isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller') {
        return $_SESSION['user_id'];
    }

    return null;
}

/**
 * Ensure both seller_id and user_id are set in session for sellers
 *
 * This function ensures that both seller_id and user_id are set
 * in the session for sellers, copying from one to the other if only one exists.
 * This helps with backward compatibility during the transition.
 *
 * @return bool True if successful, false if neither ID exists
 */
function synchronizeSellerSessionIds() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Only proceed if this is a seller account
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
        return false;
    }

    // If seller_id exists but user_id doesn't, copy seller_id to user_id
    if (isset($_SESSION['seller_id']) && !empty($_SESSION['seller_id']) &&
        (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))) {
        $_SESSION['user_id'] = $_SESSION['seller_id'];
        return true;
    }

    // If user_id exists but seller_id doesn't, copy user_id to seller_id
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) &&
        (!isset($_SESSION['seller_id']) || empty($_SESSION['seller_id']))) {
        $_SESSION['seller_id'] = $_SESSION['user_id'];
        return true;
    }

    // If both exist, make sure they're the same
    if (isset($_SESSION['user_id']) && isset($_SESSION['seller_id']) &&
        $_SESSION['user_id'] != $_SESSION['seller_id']) {
        // Prioritize seller_id
        $_SESSION['user_id'] = $_SESSION['seller_id'];
        return true;
    }

    // Return false if neither ID exists
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['seller_id'])) {
        return false;
    }

    return true;
}

/**
 * Check if user is a seller
 *
 * This function checks if the logged-in user is a seller
 * by checking the role session variable.
 *
 * @return bool True if seller, false otherwise
 */
function isSeller() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
}
