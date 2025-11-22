// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('mobile-menu');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Close menu when clicking on a nav link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            });
        });
    }
    
    // Back to Top Button
    const backToTopButton = document.getElementById('back-to-top');
    
    if (backToTopButton) {
        // Show/hide the button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        
        // Smooth scroll to top when button is clicked
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            e.preventDefault();
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Adjust for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add active class to current navigation link
    const currentPage = window.location.pathname.split('/').pop().split('.')[0];
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage) && currentPage !== '') {
            link.classList.add('active');
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
                
                // Email validation
                if (input.type === 'email' && input.value.trim() !== '') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value.trim())) {
                        isValid = false;
                        input.classList.add('error');
                    }
                }
                
                // Password confirmation
                if (input.dataset.confirm) {
                    const confirmInput = document.querySelector(`[name="${input.dataset.confirm}"]`);
                    if (input.value !== confirmInput.value) {
                        isValid = false;
                        input.classList.add('error');
                        if (confirmInput) {
                            confirmInput.classList.add('error');
                        }
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Show error message
                const errorMessage = this.querySelector('.form-error') || document.createElement('div');
                errorMessage.className = 'form-error';
                errorMessage.textContent = 'Please fill in all required fields correctly.';
                errorMessage.style.color = 'var(--danger-color)';
                errorMessage.style.marginTop = '1rem';
                
                if (!this.querySelector('.form-error')) {
                    this.appendChild(errorMessage);
                }
                
                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    });
    
    // Remove error class when user starts typing
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const formError = this.closest('form')?.querySelector('.form-error');
            if (formError) {
                formError.remove();
            }
        });
    });
    
    // Add animation to elements with data-animate attribute
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('[data-animate]');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.classList.add('fade-in');
            }
        });
    };
    
    // Initial check for elements in viewport
    animateOnScroll();
    
    // Check for elements as user scrolls
    window.addEventListener('scroll', animateOnScroll);
    
    // Tooltip initialization
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
    
    tooltipTriggers.forEach(trigger => {
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = trigger.getAttribute('data-tooltip');
        document.body.appendChild(tooltip);
        
        // Position tooltip on hover
        trigger.addEventListener('mouseenter', (e) => {
            const rect = trigger.getBoundingClientRect();
            tooltip.style.display = 'block';
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
            tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
        });
        
        trigger.addEventListener('mouseleave', () => {
            tooltip.style.display = 'none';
        });
    });
    
    // Add CSS for tooltips
    const tooltipStyle = document.createElement('style');
    tooltipStyle.textContent = `
        .tooltip {
            position: fixed;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            pointer-events: none;
            z-index: 1000;
            display: none;
            transform: translateX(-50%);
            white-space: nowrap;
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
        }
    `;
    document.head.appendChild(tooltipStyle);
});

// Function to show a notification/message
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Add show class after a small delay to trigger the animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove notification after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Add CSS for notifications
    if (!document.getElementById('notification-style')) {
        const style = document.createElement('style');
        style.id = 'notification-style';
        style.textContent = `
            .notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 4px;
                color: white;
                font-weight: 500;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 1100;
                max-width: 300px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
            
            .notification.show {
                transform: translateY(0);
                opacity: 1;
            }
            
            .notification.success {
                background: var(--success-color);
                border-left: 4px solid #00a884;
            }
            
            .notification.error {
                background: var(--danger-color);
                border-left: 4px solid #b71c1c;
            }
            
            .notification.warning {
                background: var(--warning-color);
                color: var(--dark-color);
                border-left: 4px solid #e6b800;
            }
            
            .notification.info {
                background: var(--primary-color);
                border-left: 4px solid #5e35b1;
            }
        `;
        document.head.appendChild(style);
    }
}

// Function to handle AJAX form submissions
function handleFormSubmit(form, options = {}) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton ? submitButton.innerHTML : '';
        
        // Show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = options.loadingText || 'Processing...';
        }
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Success
                if (options.onSuccess) {
                    options.onSuccess(data);
                } else {
                    showNotification(data.message || 'Action completed successfully!', 'success');
                    if (options.redirect) {
                        setTimeout(() => {
                            window.location.href = options.redirect;
                        }, 1500);
                    }
                }
            } else {
                // Error
                throw new Error(data.message || 'An error occurred');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showNotification(error.message || 'An error occurred. Please try again.', 'error');
        } finally {
            // Reset button state
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        }
    });
}

// Initialize all AJAX forms on the page
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        handleFormSubmit(form, {
            loadingText: form.getAttribute('data-loading-text') || 'Processing...',
            redirect: form.getAttribute('data-redirect') || ''
        });
    });
});
