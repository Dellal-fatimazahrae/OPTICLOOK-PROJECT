// Menu mobile toggle
document.addEventListener('DOMContentLoaded', function() {
    const menu = document.getElementById('menu');
    const nav = document.getElementById('nav');
    
    if (menu && nav) {
        menu.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // Fermer le menu quand on clique sur un lien (mobile)
    const navLinks = nav.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 800) {
                nav.classList.remove('active');
            }
        });
    });
    
    // Fermer le menu quand on redimensionne la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth > 800) {
            nav.classList.remove('active');
        }
    });
});

// Animation au scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('header');
    if (window.scrollY > 100) {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
        header.style.backdropFilter = 'blur(10px)';
    } else {
        header.style.background = '#fff';
        header.style.backdropFilter = 'none';
    }
});

// Smooth scrolling pour les liens d'ancrage
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

// Animation d'apparition des éléments au scroll
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

// Observer les éléments à animer
document.addEventListener('DOMContentLoaded', function() {
    const elementsToAnimate = document.querySelectorAll('.produit, .service-box, .categorie');
    
    elementsToAnimate.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Fonction pour filtrer les produits par catégorie (pour la page shop)
function filterProducts(category) {
    const allSections = document.querySelectorAll('.lunette');
    const allTitles = document.querySelectorAll('.titre-categories');
    
    if (category === 'all') {
        allSections.forEach(section => section.style.display = 'grid');
        allTitles.forEach(title => title.style.display = 'block');
    } else {
        allSections.forEach((section, index) => {
            allTitles.forEach((title, titleIndex) => {
                if (index === parseInt(category) - 1) {
                    section.style.display = 'grid';
                    title.style.display = 'block';
                } else {
                    section.style.display = 'none';
                    if (titleIndex === index) {
                        title.style.display = 'none';
                    }
                }
            });
        });
    }
}

// Gestion des paramètres URL pour le filtrage
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category');
    
    if (category && window.location.pathname.includes('shop.php')) {
        filterProducts(category);
        
        // Scroll vers la catégorie
        const targetSection = document.getElementById(`cat-${category}`);
        if (targetSection) {
            setTimeout(() => {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }
    }
});

// Validation basique pour les formulaires
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#e74c3c';
            isValid = false;
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    return isValid;
}

// Animation du bouton hero
document.addEventListener('DOMContentLoaded', function() {
    const heroBtn = document.querySelector('.btn-hero');
    if (heroBtn) {
        heroBtn.addEventListener('mouseenter', function() {
            this.style.animationPlayState = 'paused';
        });
        
        heroBtn.addEventListener('mouseleave', function() {
            this.style.animationPlayState = 'running';
        });
    }
});

// Lazy loading pour les images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});