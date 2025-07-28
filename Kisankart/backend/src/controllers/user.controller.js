const jwt = require('jsonwebtoken');
const { User, Address } = require('../models');

// Generate JWT token
const generateToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, {
    expiresIn: process.env.JWT_EXPIRES_IN
  });
};

// Register a new user
const register = async (req, res) => {
  try {
    const { firstName, lastName, email, password, phone, role } = req.body;

    // Check if user already exists
    const userExists = await User.findOne({ where: { email } });
    if (userExists) {
      return res.status(400).json({ message: 'User already exists' });
    }

    // Create user
    const user = await User.create({
      firstName,
      lastName,
      email,
      password,
      phone,
      role: role || 'customer'
    });

    // Generate token
    const token = generateToken(user.id);

    res.status(201).json({
      id: user.id,
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      role: user.role,
      token
    });
  } catch (error) {
    console.error('Register error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Login user
const login = async (req, res) => {
  try {
    const { email, password } = req.body;

    // Find user
    const user = await User.findOne({ where: { email } });
    if (!user) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    // Check password
    const isMatch = await user.comparePassword(password);
    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    // Update last login
    user.lastLogin = new Date();
    await user.save();

    // Generate token
    const token = generateToken(user.id);

    res.json({
      id: user.id,
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      role: user.role,
      token
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user profile
const getProfile = async (req, res) => {
  try {
    const user = await User.findByPk(req.user.id, {
      attributes: { exclude: ['password'] }
    });
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    res.json(user);
  } catch (error) {
    console.error('Get profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update user profile
const updateProfile = async (req, res) => {
  try {
    const { firstName, lastName, phone, profileImage } = req.body;
    
    const user = await User.findByPk(req.user.id);
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    // Update fields
    if (firstName) user.firstName = firstName;
    if (lastName) user.lastName = lastName;
    if (phone) user.phone = phone;
    if (profileImage) user.profileImage = profileImage;
    
    await user.save();
    
    res.json({
      id: user.id,
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      phone: user.phone,
      role: user.role,
      profileImage: user.profileImage
    });
  } catch (error) {
    console.error('Update profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Change password
const changePassword = async (req, res) => {
  try {
    const { currentPassword, newPassword } = req.body;
    
    const user = await User.findByPk(req.user.id);
    
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }
    
    // Check current password
    const isMatch = await user.comparePassword(currentPassword);
    if (!isMatch) {
      return res.status(401).json({ message: 'Current password is incorrect' });
    }
    
    // Update password
    user.password = newPassword;
    await user.save();
    
    res.json({ message: 'Password updated successfully' });
  } catch (error) {
    console.error('Change password error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Get user addresses
const getAddresses = async (req, res) => {
  try {
    const addresses = await Address.findAll({
      where: { userId: req.user.id }
    });
    
    res.json(addresses);
  } catch (error) {
    console.error('Get addresses error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Add new address
const addAddress = async (req, res) => {
  try {
    const {
      addressLine1,
      addressLine2,
      city,
      state,
      postalCode,
      country,
      isDefault,
      addressType,
      contactPhone
    } = req.body;
    
    // If this is the default address, unset any existing default
    if (isDefault) {
      await Address.update(
        { isDefault: false },
        { where: { userId: req.user.id, isDefault: true } }
      );
    }
    
    const address = await Address.create({
      userId: req.user.id,
      addressLine1,
      addressLine2,
      city,
      state,
      postalCode,
      country,
      isDefault,
      addressType,
      contactPhone
    });
    
    res.status(201).json(address);
  } catch (error) {
    console.error('Add address error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Update address
const updateAddress = async (req, res) => {
  try {
    const { id } = req.params;
    const {
      addressLine1,
      addressLine2,
      city,
      state,
      postalCode,
      country,
      isDefault,
      addressType,
      contactPhone
    } = req.body;
    
    const address = await Address.findOne({
      where: { id, userId: req.user.id }
    });
    
    if (!address) {
      return res.status(404).json({ message: 'Address not found' });
    }
    
    // If this is being set as default, unset any existing default
    if (isDefault && !address.isDefault) {
      await Address.update(
        { isDefault: false },
        { where: { userId: req.user.id, isDefault: true } }
      );
    }
    
    // Update fields
    if (addressLine1) address.addressLine1 = addressLine1;
    if (addressLine2 !== undefined) address.addressLine2 = addressLine2;
    if (city) address.city = city;
    if (state) address.state = state;
    if (postalCode) address.postalCode = postalCode;
    if (country) address.country = country;
    if (isDefault !== undefined) address.isDefault = isDefault;
    if (addressType) address.addressType = addressType;
    if (contactPhone) address.contactPhone = contactPhone;
    
    await address.save();
    
    res.json(address);
  } catch (error) {
    console.error('Update address error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Delete address
const deleteAddress = async (req, res) => {
  try {
    const { id } = req.params;
    
    const address = await Address.findOne({
      where: { id, userId: req.user.id }
    });
    
    if (!address) {
      return res.status(404).json({ message: 'Address not found' });
    }
    
    await address.destroy();
    
    res.json({ message: 'Address deleted successfully' });
  } catch (error) {
    console.error('Delete address error:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

module.exports = {
  register,
  login,
  getProfile,
  updateProfile,
  changePassword,
  getAddresses,
  addAddress,
  updateAddress,
  deleteAddress
};
