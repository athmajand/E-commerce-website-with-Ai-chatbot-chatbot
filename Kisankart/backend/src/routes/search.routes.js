const express = require('express');
const router = express.Router();
const searchController = require('../controllers/search.controller');

// Search products
router.get('/products', searchController.searchProducts);

// Autocomplete suggestions
router.get('/autocomplete', searchController.getAutocompleteSuggestions);

module.exports = router;
