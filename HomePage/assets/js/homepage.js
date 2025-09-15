// JavaScript pour la page d'accueil RMS-Ticket

document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les éléments
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observer les cartes de fonctionnalités
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.2}s, transform 0.6s ease ${index * 0.2}s`;
        observer.observe(card);
    });

    // Animation du titre principal
    const heroTitle = document.querySelector('.hero-title');
    if (heroTitle) {
        heroTitle.style.opacity = '0';
        heroTitle.style.transform = 'translateY(30px)';
        heroTitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        
        setTimeout(() => {
            heroTitle.style.opacity = '1';
            heroTitle.style.transform = 'translateY(0)';
        }, 200);
    }

    // Animation du sous-titre
    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        heroSubtitle.style.opacity = '0';
        heroSubtitle.style.transform = 'translateY(30px)';
        heroSubtitle.style.transition = 'opacity 0.8s ease 0.3s, transform 0.8s ease 0.3s';
        
        setTimeout(() => {
            heroSubtitle.style.opacity = '1';
            heroSubtitle.style.transform = 'translateY(0)';
        }, 500);
    }

    // Animation des boutons d'action
    const actionButtons = document.querySelector('.action-buttons');
    if (actionButtons) {
        actionButtons.style.opacity = '0';
        actionButtons.style.transform = 'translateY(30px)';
        actionButtons.style.transition = 'opacity 0.8s ease 0.6s, transform 0.8s ease 0.6s';
        
        setTimeout(() => {
            actionButtons.style.opacity = '1';
            actionButtons.style.transform = 'translateY(0)';
        }, 800);
    }

    // Effet de parallaxe léger pour le bruit de fond
    let ticking = false;
    
    function updateParallax() {
        const scrolled = window.pageYOffset;
        const noise = document.querySelector('.noise');
        if (noise) {
            noise.style.transform = `translateY(${scrolled * 0.1}px)`;
        }
        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestTick);

    // Animation des icônes au survol
    const featureIcons = document.querySelectorAll('.feature-icon');
    featureIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(5deg)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    });

    // Gestion de la navbar sur mobile
    const navbar = document.querySelector('.navbar');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scroll vers le bas - masquer la navbar
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Scroll vers le haut - afficher la navbar
            navbar.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });

    // Smooth scroll pour les liens internes
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Effet de typing pour le titre principal (optionnel)
    const titleText = "Bienvenue sur RMS-Ticket";
    const heroTitleElement = document.querySelector('.hero-title');
    
    if (heroTitleElement && window.innerWidth > 768) {
        heroTitleElement.textContent = '';
        let i = 0;
        
        function typeWriter() {
            if (i < titleText.length) {
                heroTitleElement.textContent += titleText.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        }
        
        setTimeout(typeWriter, 1000);
    }
});

// Fonction utilitaire pour les animations
function animateElement(element, animationType = 'fadeInUp', delay = 0) {
    setTimeout(() => {
        element.classList.add('animate-' + animationType);
    }, delay);
}

// Ajouter les classes CSS pour les animations
const style = document.createElement('style');
style.textContent = `
    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease forwards;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .navbar {
        transition: transform 0.3s ease;
    }
`;
document.head.appendChild(style);
