<script>
// Animation and Interaction Enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }
        });
    });

    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple-effect';
            ripple.style.left = (e.offsetX - 10) + 'px';
            ripple.style.top = (e.offsetY - 10) + 'px';
            this.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe elements for scroll animations
    const animateElements = document.querySelectorAll('.card, .stat-card, .alert, .module-card');
    animateElements.forEach(element => {
        observer.observe(element);
    });

    // Add special animations for dashboard modules
    const moduleCards = document.querySelectorAll('.module-card');
    moduleCards.forEach((card, index) => {
        // Add click animation
        card.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });

        // Add hover sound effect (visual feedback)
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 15px 50px rgba(0,0,0,0.2)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
        });
    });

    // Add keyboard navigation enhancements
    document.addEventListener('keydown', function(e) {
        // Enhanced tab navigation
        if (e.key === 'Tab') {
            const focusedElement = document.activeElement;
            if (focusedElement && focusedElement.classList.contains('btn')) {
                focusedElement.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    focusedElement.style.transform = '';
                }, 150);
            }
        }

        // Escape key handling
        if (e.key === 'Escape') {
            // Close any open modals or focus traps
            const modal = document.querySelector('.modal.show');
            if (modal) {
                modal.classList.remove('show');
            }
        }
    });

    // Add success animations for completed actions
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transform = 'scale(1.05)';
            setTimeout(() => {
                alert.style.transform = 'scale(1)';
            }, 200);
        }, 500);
    });

    // Table row highlighting
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Add counter animations for stats
    const statNumbers = document.querySelectorAll('.stat-content h3');
    statNumbers.forEach(stat => {
        const text = stat.textContent;
        const isMoney = text.includes('$');

        if (isMoney || /^\d+$/.test(text.replace(/[$,]/g, ''))) {
            animateCounter(stat, text);
        }
    });

    function animateCounter(element, finalText) {
        const isMoney = finalText.includes('$');
        const numericValue = parseFloat(finalText.replace(/[$,]/g, ''));

        if (isNaN(numericValue)) return;

        let currentValue = 0;
        const increment = numericValue / 50; // 50 steps
        const duration = 1000; // 1 second
        const stepTime = duration / 50;

        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= numericValue) {
                currentValue = numericValue;
                clearInterval(timer);
            }

            const displayValue = isMoney ?
                '$' + number_format(currentValue, 2) :
                Math.round(currentValue).toString();

            element.textContent = displayValue;
        }, stepTime);
    }

    function number_format(number, decimals = 2) {
        return number.toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Add page transition effects
    window.addEventListener('beforeunload', function() {
        document.body.style.opacity = '0';
        document.body.style.transform = 'scale(0.95)';
        document.body.style.transition = 'all 0.2s ease';
    });
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.4);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .animate-in {
        animation: fadeInUp 0.6s ease-out forwards !important;
    }
`;
document.head.appendChild(style);</script>