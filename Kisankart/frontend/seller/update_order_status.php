<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Check if user is logged in as a seller
if (!getSellerIdFromSession() || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page if not logged in as a seller
    header("Location: ../../seller_login.php?redirect=frontend/seller/orders.php");
    exit;
}

// Include database configuration
include_once __DIR__ . '/../../api/config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$error_message = '';
$success_message = '';

// Include session helper
require_once '../../api/helpers/session_helper.php';

// Synchronize seller IDs in session
synchronizeSellerSessionIds();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $seller_id = getSellerIdFromSession();

    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $error_message = "Invalid status value!";
    } else {
        try {
            // Start transaction
            $db->beginTransaction();

            // First, verify that this order item belongs to the current seller
            $check_query = "SELECT oi.id
                           FROM order_items oi
                           WHERE oi.id = ? AND oi.seller_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $order_id);
            $check_stmt->bindParam(2, $seller_id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                // Update order item status
                $update_item_query = "UPDATE order_items SET status = ? WHERE id = ?";
                $update_item_stmt = $db->prepare($update_item_query);
                $update_item_stmt->bindParam(1, $status);
                $update_item_stmt->bindParam(2, $order_id);

                if ($update_item_stmt->execute()) {
                    // Get the order ID for this order item
                    $order_query = "SELECT order_id FROM order_items WHERE id = ?";
                    $order_stmt = $db->prepare($order_query);
                    $order_stmt->bindParam(1, $order_id);
                    $order_stmt->execute();
                    $order_row = $order_stmt->fetch(PDO::FETCH_ASSOC);
                    $main_order_id = $order_row['order_id'];

                    // Check if all items in this order have the same status
                    $check_all_query = "SELECT COUNT(*) as total,
                                       SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as same_status
                                       FROM order_items
                                       WHERE order_id = ?";
                    $check_all_stmt = $db->prepare($check_all_query);
                    $check_all_stmt->bindParam(1, $status);
                    $check_all_stmt->bindParam(2, $main_order_id);
                    $check_all_stmt->execute();
                    $counts = $check_all_stmt->fetch(PDO::FETCH_ASSOC);

                    // If all items have the same status, update the main order status
                    if ($counts['total'] == $counts['same_status']) {
                        $update_order_query = "UPDATE orders SET status = ? WHERE id = ?";
                        $update_order_stmt = $db->prepare($update_order_query);
                        $update_order_stmt->bindParam(1, $status);
                        $update_order_stmt->bindParam(2, $main_order_id);
                        $update_order_stmt->execute();
                    }

                    // Commit transaction
                    $db->commit();

                    // Create a more user-friendly message based on the status
                    if ($status == 'processing') {
                        $success_message = "Order marked as Packed successfully!";
                    } elseif ($status == 'shipped') {
                        $success_message = "Order marked as Sent successfully!";
                    } elseif ($status == 'delivered') {
                        $success_message = "Order marked as Delivered successfully!";
                    } else {
                        $success_message = "Order status updated to " . ucfirst($status) . " successfully!";
                    }
                } else {
                    throw new Exception("Failed to update order status");
                }
            } else {
                throw new Exception("You don't have permission to update this order");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

// Store messages in session for display after redirect
if (!empty($error_message)) {
    $_SESSION['error_message'] = $error_message;
}
if (!empty($success_message)) {
    $_SESSION['success_message'] = $success_message;
}

// Redirect back to orders page
header("Location: orders.php");
exit;
?>
