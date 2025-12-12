// AJAX Add to Cart - Clean Version
console.log('Cart actions script loaded');

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.add-to-cart-form');
    console.log('Found ' + forms.length + ' cart forms');
    
    forms.forEach((form) => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            if (!productId) return;
            
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            // Disable button immediately to prevent double-clicks
            button.disabled = true;
            
            fetch('/pages/add_to_cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId + '&ajax=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === true || data.success === 'true') {
                    // Show success immediately
                    button.textContent = '✓ Added!';
                    button.style.backgroundColor = '#10b981';
                    button.style.color = 'white';
                    
                    // Update cart badge
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.transform = 'scale(1.3)';
                        cartBadge.style.transition = 'transform 0.3s ease';
                        setTimeout(() => {
                            cartBadge.style.transform = 'scale(1)';
                        }, 300);
                    }
                    
                    // Reset after 2 seconds
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.style.backgroundColor = '';
                        button.style.color = '';
                        button.disabled = false;
                    }, 2000);
                } else {
                    // Show error
                    button.textContent = '✗ Error';
                    button.style.backgroundColor = '#ef4444';
                    button.style.color = 'white';
                    
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.style.backgroundColor = '';
                        button.style.color = '';
                        button.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.textContent = '✗ Failed';
                button.style.backgroundColor = '#ef4444';
                button.style.color = 'white';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.backgroundColor = '';
                    button.style.color = '';
                    button.disabled = false;
                }, 2000);
            });
        });
    });
});
