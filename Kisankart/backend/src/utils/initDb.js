const sequelize = require('../config/database');
const { User, Category, Subcategory } = require('../models');
const bcrypt = require('bcryptjs');

// Initialize database
const initDb = async () => {
  try {
    // Sync all models with database
    await sequelize.sync({ force: true });
    console.log('Database synchronized');
    
    // Create admin user
    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash('admin123', salt);
    
    await User.create({
      firstName: 'Admin',
      lastName: 'User',
      email: 'admin@kisankart.com',
      password: hashedPassword,
      role: 'admin',
      isActive: true
    });
    
    console.log('Admin user created');
    
    // Create categories
    const categories = [
      {
        name: 'Fruits',
        description: 'Fresh fruits directly from farms',
        image: 'fruits.jpg'
      },
      {
        name: 'Vegetables',
        description: 'Fresh vegetables directly from farms',
        image: 'vegetables.jpg'
      },
      {
        name: 'Grains',
        description: 'Organic grains and pulses',
        image: 'grains.jpg'
      },
      {
        name: 'Dairy',
        description: 'Fresh dairy products',
        image: 'dairy.jpg'
      },
      {
        name: 'Spices',
        description: 'Organic spices and herbs',
        image: 'spices.jpg'
      }
    ];
    
    for (const category of categories) {
      await Category.create(category);
    }
    
    console.log('Categories created');
    
    // Create subcategories
    const subcategories = [
      {
        categoryId: 1, // Fruits
        name: 'Seasonal Fruits',
        description: 'Fruits available in current season',
        image: 'seasonal-fruits.jpg'
      },
      {
        categoryId: 1, // Fruits
        name: 'Exotic Fruits',
        description: 'Imported and exotic fruits',
        image: 'exotic-fruits.jpg'
      },
      {
        categoryId: 2, // Vegetables
        name: 'Leafy Vegetables',
        description: 'Fresh leafy vegetables',
        image: 'leafy-vegetables.jpg'
      },
      {
        categoryId: 2, // Vegetables
        name: 'Root Vegetables',
        description: 'Fresh root vegetables',
        image: 'root-vegetables.jpg'
      },
      {
        categoryId: 3, // Grains
        name: 'Rice',
        description: 'Different varieties of rice',
        image: 'rice.jpg'
      },
      {
        categoryId: 3, // Grains
        name: 'Pulses',
        description: 'Different varieties of pulses',
        image: 'pulses.jpg'
      },
      {
        categoryId: 4, // Dairy
        name: 'Milk',
        description: 'Fresh milk and milk products',
        image: 'milk.jpg'
      },
      {
        categoryId: 4, // Dairy
        name: 'Cheese',
        description: 'Different varieties of cheese',
        image: 'cheese.jpg'
      },
      {
        categoryId: 5, // Spices
        name: 'Whole Spices',
        description: 'Whole spices for authentic flavor',
        image: 'whole-spices.jpg'
      },
      {
        categoryId: 5, // Spices
        name: 'Ground Spices',
        description: 'Freshly ground spices',
        image: 'ground-spices.jpg'
      }
    ];
    
    for (const subcategory of subcategories) {
      await Subcategory.create(subcategory);
    }
    
    console.log('Subcategories created');
    
    console.log('Database initialization completed');
  } catch (error) {
    console.error('Database initialization error:', error);
  }
};

// Run if this script is executed directly
if (require.main === module) {
  initDb()
    .then(() => {
      console.log('Database initialization completed');
      process.exit(0);
    })
    .catch(error => {
      console.error('Database initialization failed:', error);
      process.exit(1);
    });
}

module.exports = initDb;
