// Parse image settings if available
let imageSettings = {};
if (product.image_settings) {
    try {
        imageSettings = JSON.parse(product.image_settings);
    } catch (e) {
        console.error('Error parsing image settings for product', product.id, ':', e);
    }
}

// Set default values if not specified
const imageFit = imageSettings.fit || 'cover';
const imageZoom = typeof imageSettings.zoom !== 'undefined' ? imageSettings.zoom : 1;
const panX = typeof imageSettings.panX !== 'undefined' ? imageSettings.panX : 0;
const panY = typeof imageSettings.panY !== 'undefined' ? imageSettings.panY : 0;
const style = `object-fit: ${imageFit}; position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; transform-origin: center center; transform: translate(calc(-50% + ${panX}px), calc(-50% + ${panY}px)) scale(${imageZoom});`;

// When rendering the image in the card:
const imageContainer = document.createElement('div');
imageContainer.style.width = '100%';
imageContainer.style.height = '200px';
imageContainer.style.overflow = 'hidden';
imageContainer.style.position = 'relative';
imageContainer.style.background = '#f8f9fa';
imageContainer.style.borderRadius = '8px';

const img = document.createElement('img');
img.src = imageUrl;
img.alt = product.name;
img.className = 'product-image border-0 p-0';
img.style = style;
img.onerror = function() {
    this.src = 'https://via.placeholder.com/300x200?text=No+Image';
};

imageContainer.appendChild(img);
card.appendChild(imageContainer); 