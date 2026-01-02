/* ========================================
   CARRUSEL DE SANATORIOS
   ======================================== */

/**
 * Carrusel responsive con navegación manual
 */

function Carousel(element) {
  this.container = element;
  this.track = element.querySelector('.carousel-track');
  this.prevBtn = element.parentElement.querySelector('.carousel-btn-prev');
  this.nextBtn = element.parentElement.querySelector('.carousel-btn-next');
  this.items = Array.from(this.track.children);

  this.currentIndex = 0;
  this.itemsPerView = this.getItemsPerView();
  this.totalItems = this.items.length;

  this.init();
}

Carousel.prototype.getItemsPerView = function() {
  const width = window.innerWidth;
  if (width <= 480) return 1;
  if (width <= 768) return 2;
  if (width <= 1024) return 3;
  return 4;
};

Carousel.prototype.init = function() {
  var self = this;

  // Event listeners para botones
  this.prevBtn.addEventListener('click', function() {
    self.prev();
  });

  this.nextBtn.addEventListener('click', function() {
    self.next();
  });

  // Responsive
  window.addEventListener('resize', function() {
    self.itemsPerView = self.getItemsPerView();
    self.updatePosition();
    self.updateButtons();
  });

  // Inicializar estado
  this.updateButtons();

  // Touch/swipe support
  this.initTouch();
};

Carousel.prototype.prev = function() {
  if (this.currentIndex > 0) {
    this.currentIndex--;
    this.updatePosition();
    this.updateButtons();

    if (typeof gtag === 'function') {
      gtag('event', 'carousel_navigate', {
        'event_category': 'sanatorios',
        'event_label': 'prev',
        'carousel_index': this.currentIndex
      });
    }
  }
};

Carousel.prototype.next = function() {
  const maxIndex = this.totalItems - this.itemsPerView;
  if (this.currentIndex < maxIndex) {
    this.currentIndex++;
    this.updatePosition();
    this.updateButtons();

    if (typeof gtag === 'function') {
      gtag('event', 'carousel_navigate', {
        'event_category': 'sanatorios',
        'event_label': 'next',
        'carousel_index': this.currentIndex
      });
    }
  }
};

Carousel.prototype.updatePosition = function() {
  const itemWidth = this.items[0].offsetWidth;
  const gap = 20; // Debe coincidir con CSS
  const offset = -(this.currentIndex * (itemWidth + gap));
  this.track.style.transform = 'translateX(' + offset + 'px)';
};

Carousel.prototype.updateButtons = function() {
  const maxIndex = this.totalItems - this.itemsPerView;

  // Deshabilitar botón prev si está al inicio
  this.prevBtn.disabled = this.currentIndex === 0;

  // Deshabilitar botón next si está al final
  this.nextBtn.disabled = this.currentIndex >= maxIndex;
};

Carousel.prototype.initTouch = function() {
  var self = this;
  var startX = 0;
  var currentX = 0;
  var isDragging = false;

  this.track.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    isDragging = true;
  });

  this.track.addEventListener('touchmove', function(e) {
    if (!isDragging) return;
    currentX = e.touches[0].clientX;
  });

  this.track.addEventListener('touchend', function() {
    if (!isDragging) return;

    const diff = startX - currentX;
    if (Math.abs(diff) > 50) { // Threshold de 50px
      if (diff > 0) {
        self.next();
      } else {
        self.prev();
      }
    }

    isDragging = false;
  });
};

// ========================================
// INICIALIZAR CARRUSEL
// ========================================

document.addEventListener('DOMContentLoaded', function() {
  const carouselElement = document.querySelector('.carousel');
  if (carouselElement) {
    new Carousel(carouselElement);
    console.log('[Carousel] Sanatorios carousel initialized');
  }
});
