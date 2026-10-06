// ============================================================
// THE_LEGACY_HOUSE — main.js
// ============================================================

'use strict';

// ── Toggle favori (AJAX) ──────────────────────────────────────
async function toggleFav(event, annonceId, btn) {
  event.preventDefault();
  event.stopPropagation();

  const isActive = btn.classList.contains('active');
  const action   = isActive ? 'remove' : 'add';

  try {
    const fd = new FormData();
    fd.append('annonce_id', annonceId);
    fd.append('action', action);

    const resp = await fetch(BASE_URL + '/favoris.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    });

    if (resp.ok) {
      btn.classList.toggle('active');
      const icon = btn.querySelector('i');
      if (icon) {
        icon.className = btn.classList.contains('active')
          ? 'bi bi-heart-fill'
          : 'bi bi-heart';
      }
      // Micro-animation
      btn.style.transform = 'scale(1.3)';
      setTimeout(() => btn.style.transform = '', 200);
    }
  } catch (err) {
    console.error('Erreur favoris', err);
  }
}

// ── BASE_URL depuis meta ou window ────────────────────────────
window.BASE_URL = window.BASE_URL || '';

// ── Auto-dismiss flash messages ───────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .5s ease';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 500);
  }, 4500);
});

// ── Scroll fluide vers le bas du chat ─────────────────────────
const chatEl = document.getElementById('chatMessages');
if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;

// ── Input autofocus messagerie ────────────────────────────────
const msgInput = document.getElementById('msgInput');
if (msgInput) msgInput.focus();

// ── Animation fade-up au scroll (Intersection Observer) ───────
const fadeEls = document.querySelectorAll('.annonce-card');
if ('IntersectionObserver' in window && fadeEls.length) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        entry.target.style.animationDelay = (i * 0.04) + 's';
        entry.target.classList.add('fade-up');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  fadeEls.forEach(el => observer.observe(el));
}

// ── Confirmation suppression ──────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});

// ── Preview image annonce ─────────────────────────────────────
function previewImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    let preview = document.getElementById('imgPreviewEl');
    if (!preview) return;
    preview.src = e.target.result;
    document.getElementById('imgPreview').style.display = 'block';
  };
  reader.readAsDataURL(file);
}
