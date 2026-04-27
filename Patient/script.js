document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const navLinks = document.querySelector('.nav-links');
            
            // Create mobile menu if it doesn't exist
            if (!document.querySelector('.mobile-nav')) {
                const mobileNav = document.createElement('div');
                mobileNav.className = 'mobile-nav';
                
                // Add styles to mobile nav
                mobileNav.style.position = 'fixed';
                mobileNav.style.top = '0';
                mobileNav.style.left = '0';
                mobileNav.style.width = '100%';
                mobileNav.style.height = '100vh';
                mobileNav.style.backgroundColor = 'white';
                mobileNav.style.padding = '2rem';
                mobileNav.style.zIndex = '1000';
                mobileNav.style.display = 'none';
                mobileNav.style.flexDirection = 'column';
                
                // Clone the navigation links
                const navClone = navLinks.cloneNode(true);
                navClone.style.display = 'flex';
                navClone.style.flexDirection = 'column';
                navClone.style.gap = '1.5rem';
                navClone.style.marginBottom = '2rem';
                
                // Add phone number to mobile menu
                const phoneNumber = document.querySelector('.phone-number').cloneNode(true);
                phoneNumber.style.display = 'flex';
                phoneNumber.style.marginBottom = '1.5rem';
                
                // Add appointment button
                const appointmentBtn = document.querySelector('.header-right .btn-primary').cloneNode(true);
                appointmentBtn.style.display = 'inline-flex';
                appointmentBtn.style.marginBottom = '2rem';
                
                mobileNav.appendChild(navClone);
                mobileNav.appendChild(phoneNumber);
                mobileNav.appendChild(appointmentBtn);
                
                document.body.appendChild(mobileNav);
                
                // Add close button
                const closeBtn = document.createElement('button');
                closeBtn.className = 'mobile-nav-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.style.position = 'absolute';
                closeBtn.style.top = '1rem';
                closeBtn.style.right = '1rem';
                closeBtn.style.background = 'none';
                closeBtn.style.border = 'none';
                closeBtn.style.fontSize = '2rem';
                closeBtn.style.cursor = 'pointer';
                mobileNav.prepend(closeBtn);
                
                closeBtn.addEventListener('click', function() {
                    mobileNav.style.display = 'none';
                });
            }
            
            // Toggle mobile nav
            const mobileNav = document.querySelector('.mobile-nav');
            mobileNav.style.display = mobileNav.style.display === 'flex' ? 'none' : 'flex';
        });
    }
    
    // Testimonials slider
    const testimonialsSlider = document.querySelector('.testimonials-slider');
    
    if (testimonialsSlider) {
        // Auto scroll testimonials
        let scrollPosition = 0;
        const testimonialCards = document.querySelectorAll('.testimonial-card');
        const cardWidth = testimonialCards[0].offsetWidth + 32; // Card width + gap
        
        setInterval(() => {
            scrollPosition += cardWidth;
            
            // Reset scroll position when reaching the end
            if (scrollPosition >= testimonialsSlider.scrollWidth - testimonialsSlider.offsetWidth) {
                scrollPosition = 0;
            }
            
            testimonialsSlider.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });
        }, 5000);
    }
    
    // Chatbot functionality
    const chatbotButton = document.getElementById('chatbot-button');
    const dfMessenger = document.querySelector('df-messenger');

    if (chatbotButton && dfMessenger) {
        chatbotButton.addEventListener('click', function() {
            dfMessenger.style.display = dfMessenger.style.display === 'block' ? 'none' : 'block';
        });

        // Optional: Handle token storage if needed
        dfMessenger.addEventListener('df-response-received', function(event) {
            const response = event.detail.response.queryResult.fulfillmentText;
            if (response.includes('token')) {
                const authToken = response.split('Your token: ')[1];
                localStorage.setItem('authToken', authToken); // Store token
            }
        });
    }
});