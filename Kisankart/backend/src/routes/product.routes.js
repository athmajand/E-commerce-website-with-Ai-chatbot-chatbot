const express = require('express');
const router = express.Router();
const productController = require('../controllers/product.controller');
const { authenticate, isSeller } = require('../middleware/auth.middleware');

// Public routes
router.get('/', productController.getAllProducts);
router.get('/featured', productController.getFeaturedProducts);
router.get('/category/:categoryId', productController.getProductsByCategory);
router.get('/subcategory/:subcategoryId', productController.getProductsBySubcategory);
router.get('/search', productController.searchProducts);
router.get('/:id', productController.getProductById);

// Seller routes
router.post('/', authenticate, isSeller, productController.createProduct);
router.put('/:id', authenticate, isSeller, productController.updateProduct);
router.delete('/:id', authenticate, isSeller, productController.deleteProduct);

module.exports = router;
