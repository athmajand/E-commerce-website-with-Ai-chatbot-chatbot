# Kisan Kart Accessibility and Compatibility Guide

This document provides guidelines for maintaining accessibility and browser compatibility in the Kisan Kart application.

## Accessibility Features

### 1. Screen Reader Support

- All buttons with only icons have proper `aria-label` attributes
- Icons inside buttons have `aria-hidden="true"` to prevent duplicate announcements
- Select elements have proper labels or title attributes
- Form controls have associated labels
- Screen reader only text is provided using the `.sr-only` class

### 2. Keyboard Navigation

- All interactive elements are keyboard accessible
- Focus states are visible
- Tab order follows a logical sequence

### 3. Color Contrast

- Text has sufficient contrast against its background
- Color is not the only means of conveying information

## Browser Compatibility

### 1. CSS Properties

- Vendor-specific prefixes are accompanied by standard properties
- Example:
  ```css
  -webkit-text-size-adjust: 100%;
  text-size-adjust: 100%;
  ```

- For `text-align: -webkit-match-parent`, always include the standard property:
  ```css
  text-align: -webkit-match-parent;
  text-align: match-parent;
  ```

- For `color-adjust`, use both vendor-specific and standard properties:
  ```css
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
  ```

### 2. HTTP Headers

- Always use lowercase `utf-8` for charset values:
  ```php
  header("Content-Type: application/json; charset=utf-8");
  ```

- Use the correct media type for HTML content:
  ```php
  header("Content-Type: text/html; charset=utf-8");
  ```

### 3. Cache Busting

- All static resources (CSS, JS, images) should use cache busting
- Use the provided helper functions in `includes/cache_helpers.php`:
  ```php
  <?php
  include_once 'includes/cache_helpers.php';
  
  // In HTML head
  css_link('css/style.css');
  js_script('js/main.js', true); // true for defer
  
  // For images
  cache_bust_img('images/logo.png', 'Kisan Kart Logo', 'logo-img');
  ?>
  ```

## Helper Files

### 1. CSS Compatibility Fixes

Include the compatibility fixes CSS file in your HTML:

```html
<link rel="stylesheet" href="css/compatibility-fixes.css">
```

### 2. PHP Helper Functions

Several helper files are available:

- `includes/accessibility_helpers.php` - Functions for creating accessible UI components
- `includes/cache_helpers.php` - Functions for cache busting
- `includes/header_helpers.php` - Functions for setting proper HTTP headers
- `api/includes/api_helpers.php` - Functions for API responses

### 3. JavaScript Enhancements

The main.js file includes functions to enhance accessibility and add cache busting:

- `enhanceAccessibility()` - Adds accessibility attributes to elements
- `addCacheBustingToImages()` - Adds cache busting to image URLs

## Best Practices

### 1. Buttons and Links

- Always provide text or aria-label for buttons
- Use the appropriate element (button for actions, a for navigation)
- Example:
  ```html
  <!-- Icon-only button with aria-label -->
  <button class="btn btn-outline-success" aria-label="Add to wishlist">
    <i class="bi bi-heart" aria-hidden="true"></i>
    <span class="sr-only">Add to Wishlist</span>
  </button>
  ```

### 2. Form Controls

- Always associate labels with form controls
- Use fieldset and legend for groups of controls
- Example:
  ```html
  <div class="form-group">
    <label for="sort-select">Sort by</label>
    <select id="sort-select" class="form-select">
      <option value="newest">Newest First</option>
      <option value="price_asc">Price: Low to High</option>
    </select>
  </div>
  ```

### 3. Images

- Always provide alt text for images
- Use empty alt text for decorative images
- Example:
  ```html
  <!-- Informative image -->
  <img src="product.jpg" alt="Organic Tomatoes">
  
  <!-- Decorative image -->
  <img src="background.jpg" alt="" aria-hidden="true">
  ```

## Testing

Regularly test your application for:

1. Screen reader compatibility (using NVDA, JAWS, or VoiceOver)
2. Keyboard navigation
3. Color contrast (using tools like WebAIM's Contrast Checker)
4. Browser compatibility (Chrome, Firefox, Safari, Edge)
5. Mobile responsiveness

## Resources

- [Web Content Accessibility Guidelines (WCAG)](https://www.w3.org/WAI/standards-guidelines/wcag/)
- [MDN Web Docs: Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [WebAIM: Web Accessibility In Mind](https://webaim.org/)
