const { Product, Category, Subcategory, Seller, User } = require('../models');
const { Op } = require('sequelize');
const sequelize = require('../config/database');

// Search products
const searchProducts = async (req, res) => {
  try {
    const { query, category, subcategory, minPrice, maxPrice, sort } = req.query;
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const offset = (page - 1) * limit;

    // Build where clause
    const whereClause = { isActive: true };

    // Add search query
    if (query) {
      whereClause[Op.or] = [
        { name: { [Op.like]: `%${query}%` } },
        { description: { [Op.like]: `%${query}%` } },
        { tags: { [Op.like]: `%${query}%` } }
      ];
    }

    // Add category filter
    if (category) {
      whereClause.categoryId = category;
    }

    // Add subcategory filter
    if (subcategory) {
      whereClause.subcategoryId = subcategory;
    }

    // Add price range filter
    if (minPrice && maxPrice) {
      whereClause.price = {
        [Op.between]: [minPrice, maxPrice]
      };
    } else if (minPrice) {
      whereClause.price = {
        [Op.gte]: minPrice
      };
    } else if (maxPrice) {
      whereClause.price = {
        [Op.lte]: maxPrice
      };
    }

    // Determine sort order
    let order = [['createdAt', 'DESC']];

    if (sort) {
      switch (sort) {
        case 'price_asc':
          order = [['price', 'ASC']];
          break;
        case 'price_desc':
          order = [['price', 'DESC']];
          break;
        case 'name_asc':
          order = [['name', 'ASC']];
          break;
        case 'name_desc':
          order = [['name', 'DESC']];
          break;
        case 'newest':
          order = [['createdAt', 'DESC']];
          break;
        case 'oldest':
          order = [['createdAt', 'ASC']];
          break;
        default:
          order = [['createdAt', 'DESC']];
      }
    }

    const { count, rows } = await Product.findAndCountAll({
      where: whereClause,
      limit,
      offset,
      include: [
        { model: Category },
        { model: Subcategory },
        {
          model: Seller,
          include: [
            {
              model: User,
              attributes: ['firstName', 'lastName']
            }
          ]
        }
      ],
      order
    });

    // Get all categories for filtering
    const categories = await Category.findAll({
      include: [{ model: Subcategory }]
    });

    // Get price range for filtering
    const priceRange = await Product.findAll({
      attributes: [
        [sequelize.fn('MIN', sequelize.col('price')), 'minPrice'],
        [sequelize.fn('MAX', sequelize.col('price')), 'maxPrice']
      ],
      where: { isActive: true },
      raw: true
    });

    res.json({
      products: rows,
      totalPages: Math.ceil(count / limit),
      currentPage: page,
      totalProducts: count,
      filters: {
        categories,
        priceRange: priceRange[0]
      }
    });
  } catch (error) {
    console.error('Search products error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Autocomplete suggestions
const getAutocompleteSuggestions = async (req, res) => {
  try {
    const { query } = req.query;

    if (!query || query.length < 2) {
      return res.json([]);
    }

    // Get product name suggestions
    const productSuggestions = await Product.findAll({
      attributes: ['name'],
      where: {
        name: { [Op.like]: `%${query}%` },
        isActive: true
      },
      limit: 5
    });

    // Get category name suggestions
    const categorySuggestions = await Category.findAll({
      attributes: ['name'],
      where: {
        name: { [Op.like]: `%${query}%` }
      },
      limit: 3
    });

    // Combine suggestions
    const suggestions = [
      ...productSuggestions.map(p => ({ type: 'product', text: p.name })),
      ...categorySuggestions.map(c => ({ type: 'category', text: c.name }))
    ];

    res.json(suggestions);
  } catch (error) {
    console.error('Autocomplete suggestions error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  searchProducts,
  getAutocompleteSuggestions
};
