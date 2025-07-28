// Error handling middleware
const errorHandler = (err, req, res, next) => {
  console.error('Error:', err.stack);
  
  // Check if error is a Sequelize validation error
  if (err.name === 'SequelizeValidationError') {
    const errors = err.errors.map(e => ({
      field: e.path,
      message: e.message
    }));
    return res.status(400).json({ 
      message: 'Validation error', 
      errors 
    });
  }
  
  // Check if error is a Sequelize unique constraint error
  if (err.name === 'SequelizeUniqueConstraintError') {
    const errors = err.errors.map(e => ({
      field: e.path,
      message: e.message
    }));
    return res.status(409).json({ 
      message: 'Unique constraint error', 
      errors 
    });
  }
  
  // Check if error is a JWT error
  if (err.name === 'JsonWebTokenError') {
    return res.status(401).json({ 
      message: 'Invalid token' 
    });
  }
  
  // Check if error is a JWT expired error
  if (err.name === 'TokenExpiredError') {
    return res.status(401).json({ 
      message: 'Token expired' 
    });
  }
  
  // Default error response
  res.status(err.statusCode || 500).json({
    message: err.message || 'Internal server error'
  });
};

// Not found middleware
const notFound = (req, res, next) => {
  const error = new Error(`Not Found - ${req.originalUrl}`);
  error.statusCode = 404;
  next(error);
};

module.exports = {
  errorHandler,
  notFound
};
