const express = require('express');
const router = express.Router();
const { authenticate, isAdmin } = require('../middleware/auth.middleware');
const customerServiceController = require('../controllers/customerService.controller');

// All routes require authentication
router.use(authenticate);

// Customer routes
router.post('/', customerServiceController.createRequest);
router.get('/', customerServiceController.getUserRequests);
router.get('/:id', customerServiceController.getRequestDetails);
router.put('/:id', customerServiceController.updateRequest);

// Admin routes
router.get('/admin/all', isAdmin, customerServiceController.getAllRequests);
router.put('/admin/:id', isAdmin, customerServiceController.updateRequestStatus);

module.exports = router;
