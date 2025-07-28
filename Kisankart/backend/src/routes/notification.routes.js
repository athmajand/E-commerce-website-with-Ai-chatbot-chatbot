const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth.middleware');
const notificationController = require('../controllers/notification.controller');

// All routes require authentication
router.use(authenticate);

// Get user's notifications
router.get('/', notificationController.getUserNotifications);

// Mark notification as read
router.put('/:id/read', notificationController.markAsRead);

// Mark all notifications as read
router.put('/read-all', notificationController.markAllAsRead);

// Delete notification
router.delete('/:id', notificationController.deleteNotification);

// Delete all notifications
router.delete('/', notificationController.deleteAllNotifications);

module.exports = router;
