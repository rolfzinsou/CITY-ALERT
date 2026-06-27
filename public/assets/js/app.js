/* ===== CityAlert - Interactions ===== */

// Mode sombre — persistance via localStorage
(function () {
  const saved = localStorage.getItem('cityalert-theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initial = saved || (prefersDark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', initial);
})();

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('cityalert-theme', next);
  updateThemeIcon();
}

function updateThemeIcon() {
  const btn = document.getElementById('themeToggle');
  if (!btn) return;
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  btn.innerHTML = isDark ? sunIcon() : moonIcon();
}

function sunIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>';
}
function moonIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
}

// Init au chargement
document.addEventListener('DOMContentLoaded', () => {
  updateThemeIcon();

  // Filtres : activation/désactivation au clic
  document.querySelectorAll('.filters').forEach(group => {
    group.querySelectorAll('.chip').forEach(chip => {
      chip.addEventListener('click', () => {
        // Si c'est un groupe exclusif (un seul actif), décocher les autres
        if (group.dataset.exclusive === 'true') {
          group.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        }
        chip.classList.toggle('active');
      });
    });
  });

  // Catégorie picker
  document.querySelectorAll('.cat-picker').forEach(picker => {
    picker.querySelectorAll('.cat-option').forEach(opt => {
      opt.addEventListener('click', () => {
        picker.querySelectorAll('.cat-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
      });
    });
  });

  // Like (card-action)
  document.querySelectorAll('.card-action.like').forEach(btn => {
    btn.addEventListener('click', () => btn.classList.toggle('liked'));
  });

  // Onglets auth
  document.querySelectorAll('.auth-tabs').forEach(tabs => {
    tabs.querySelectorAll('.auth-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
      });
    });
  });

  // Raccourci clavier "/" pour focus sur la search
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
      const search = document.querySelector('.search');
      if (search) { e.preventDefault(); search.focus(); }
    }
  });
});

// ===== Animation des compteurs (hero stats) =====
function animerCompteur(el, cible, duree = 1500) {
  const debut    = parseInt(el.textContent || '0', 10);
  const startTs  = performance.now();
  function frame(now) {
    const t       = Math.min((now - startTs) / duree, 1);
    const eased   = 1 - Math.pow(1 - t, 3);
    const courant = Math.floor(debut + (cible - debut) * eased);
    el.textContent = courant.toLocaleString('fr-FR');
    if (t < 1) requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
}

document.addEventListener('DOMContentLoaded', () => {
  // Démarre l'animation des compteurs au scroll
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const cible = parseInt(entry.target.dataset.target || '0', 10);
        animerCompteur(entry.target, cible);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('.hero-stat .n[data-target]').forEach((el) => observer.observe(el));

  // Disparition automatique des flash messages après 5s
  setTimeout(() => {
    document.querySelectorAll('.flash').forEach((el) => {
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateX(20px)';
      setTimeout(() => el.remove(), 400);
    });
  }, 5000);
});

// ===== Menu mobile (hamburger) =====
function toggleMobileMenu() {
  const menu = document.getElementById('mobileMenu');
  if (menu) menu.classList.toggle('open');
}

// ===== Lightbox photos =====
(function () {
  let images = [];
  let currentIndex = 0;

  function openLightbox(index) {
    images = Array.from(document.querySelectorAll('[data-lightbox]')).map(el => el.src || el.dataset.src);
    currentIndex = index;
    showImage();
    document.getElementById('lightbox')?.classList.add('open');
  }
  function showImage() {
    const img = document.getElementById('lightboxImg');
    if (img && images[currentIndex]) img.src = images[currentIndex];
  }
  function closeLightbox() { document.getElementById('lightbox')?.classList.remove('open'); }
  function nextImage() { currentIndex = (currentIndex + 1) % images.length; showImage(); }
  function prevImage() { currentIndex = (currentIndex - 1 + images.length) % images.length; showImage(); }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lightbox]').forEach((el, i) => {
      el.addEventListener('click', () => openLightbox(i));
    });
    document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
    document.getElementById('lightboxNext')?.addEventListener('click', nextImage);
    document.getElementById('lightboxPrev')?.addEventListener('click', prevImage);
    document.getElementById('lightbox')?.addEventListener('click', (e) => {
      if (e.target.id === 'lightbox') closeLightbox();
    });
    document.addEventListener('keydown', (e) => {
      if (!document.getElementById('lightbox')?.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') nextImage();
      if (e.key === 'ArrowLeft') prevImage();
    });
  });

  window.openLightbox = openLightbox;
})();

// ===== Prévisualisation des photos avant upload (multi) =====
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('photosInput');
  const preview = document.getElementById('uploadPreview');
  if (!input || !preview) return;

  const dataTransfer = new DataTransfer();

  input.addEventListener('change', () => {
    Array.from(input.files).forEach(file => {
      if (!file.type.startsWith('image/')) return;
      dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    renderPreviews();
  });

  function renderPreviews() {
    preview.innerHTML = '';
    Array.from(dataTransfer.files).forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const item = document.createElement('div');
        item.className = 'upload-preview-item';
        item.innerHTML = `
          <img src="${e.target.result}" alt="">
          <span class="upload-preview-remove" data-index="${index}">&times;</span>
        `;
        item.querySelector('.upload-preview-remove').addEventListener('click', () => {
          dataTransfer.items.remove(index);
          input.files = dataTransfer.files;
          renderPreviews();
        });
        preview.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
  }
});

window.toggleMobileMenu = toggleMobileMenu;
