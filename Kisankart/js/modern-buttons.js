// Modern Buttons JavaScript for Kisan Kart

document.addEventListener('DOMContentLoaded', function() {
    // Add ripple effect to buttons with btn-ripple class
    const rippleButtons = document.querySelectorAll('.btn-ripple');
    
    rippleButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            button.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
    
    // Disable buttons when clicked to prevent double submission
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (submitButton) {
                // Store original text
                const originalText = submitButton.innerHTML;
                
                // Change to loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Reset button after 5 seconds in case the form submission fails
                setTimeout(function() {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 5000);
            }
        });
    });
});
