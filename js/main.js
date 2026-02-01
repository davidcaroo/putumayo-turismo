// Toggle del tema
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = themeToggle.querySelector('i');

// Verificar preferencia guardada
const currentTheme = localStorage.getItem('theme') || 'claro';
document.documentElement.setAttribute('data-theme', currentTheme);
updateThemeIcon(currentTheme);

themeToggle.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'claro' ? 'oscuro' : 'claro';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
});

function updateThemeIcon(theme) {
    if (theme === 'oscuro') {
        themeIcon.className = 'fas fa-sun';
    } else {
        themeIcon.className = 'fas fa-moon';
    }
}

// Menú móvil
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');

navToggle.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    navToggle.classList.toggle('active');
});

// Cerrar menú al hacer clic en un enlace
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        navToggle.classList.remove('active');
    });
});

// Animación al hacer scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Observar elementos para animación
document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('.destino-card, .section-title, .actividad-card');
    animatedElements.forEach(el => observer.observe(el));
});

// Carrusel
class Carousel {
    constructor(container) {
        this.container = container;
        this.inner = container.querySelector('.carousel-inner');
        this.items = container.querySelectorAll('.carousel-item');
        this.prevBtn = container.querySelector('.carousel-control.prev');
        this.nextBtn = container.querySelector('.carousel-control.next');
        this.currentIndex = 0;
        
        this.init();
    }
    
    init() {
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());
        
        // Auto avanzar cada 5 segundos
        setInterval(() => this.next(), 5000);
    }
    
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
        this.update();
    }
    
    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
        this.update();
    }
    
    update() {
        this.inner.style.transform = `translateX(-${this.currentIndex * 100}%)`;
    }
}

// Inicializar carruseles
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.carousel').forEach(container => {
        new Carousel(container);
    });
});

// Smooth scroll para enlaces internos
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

// Header con efecto al hacer scroll
let lastScrollY = window.scrollY;
const header = document.querySelector('.header');

window.addEventListener('scroll', () => {
    if (window.scrollY > 100) {
        header.style.background = 'var(--bg-color)';
        header.style.boxShadow = 'var(--shadow)';
    } else {
        header.style.background = 'transparent';
        header.style.boxShadow = 'none';
    }
    
    lastScrollY = window.scrollY;
});

// Formulario de newsletter
document.querySelectorAll('.newsletter-form').forEach(form => {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const email = form.querySelector('input[type="email"]').value;
        
        // Aquí iría la lógica para enviar el email
        alert(`¡Gracias por suscribirte con el email: ${email}!`);
        form.reset();
    });
});