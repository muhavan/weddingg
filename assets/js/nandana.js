/**
 * NANDANA THEME SCRIPT - RINGVITATION LUXURY WEDDING
 * Animations, 3D WebGL Rings, Scroll Reveals, & MySQL Database Wishes Integration
 */

(function () {
  'use strict';

  /* ===================================================================
     1. GUEST NAME PERSONALIZATION (URL PARAMETER)
     =================================================================== */
  function initGuestPersonalization() {
    const urlParams = new URLSearchParams(window.location.search);
    const guestParam = urlParams.get('to') || urlParams.get('guest') || urlParams.get('u');

    const coverGuestEl = document.getElementById('coverGuestName');
    const desktopGuestEl = document.getElementById('desktopGuestName');
    const inputNameEl = document.getElementById('wishNama');

    if (guestParam) {
      const cleanName = guestParam.trim();
      if (coverGuestEl) coverGuestEl.textContent = cleanName;
      if (desktopGuestEl) desktopGuestEl.textContent = cleanName;
      if (inputNameEl) inputNameEl.value = cleanName;
    } else {
      if (coverGuestEl) coverGuestEl.textContent = 'Bapak/Ibu/Saudara/i';
      if (desktopGuestEl) desktopGuestEl.textContent = 'Bapak/Ibu/Saudara/i';
    }
  }

  /* ===================================================================
     2. AUDIO CONTROLLER & BACKGROUND MUSIC
     =================================================================== */
  const weddingAudio = document.getElementById('weddingAudio');
  const floatingMusicBtn = document.getElementById('floatingMusicBtn');
  let isPlaying = false;

  function playAudioMusic() {
    if (!weddingAudio) return;
    weddingAudio.play().then(() => {
      isPlaying = true;
      if (floatingMusicBtn) floatingMusicBtn.classList.add('playing');
    }).catch(err => {
      console.log('Autoplay restriction:', err);
      isPlaying = false;
    });
  }

  function toggleAudioMusic() {
    if (!weddingAudio) return;
    if (isPlaying) {
      weddingAudio.pause();
      isPlaying = false;
      if (floatingMusicBtn) floatingMusicBtn.classList.remove('playing');
    } else {
      playAudioMusic();
    }
  }

  if (floatingMusicBtn) {
    floatingMusicBtn.addEventListener('click', toggleAudioMusic);
  }

  /* ===================================================================
     3. COVER OPENING ANIMATION SEQUENCE (#toot / BUKA UNDANGAN)
     =================================================================== */
  const tootBtn = document.getElementById('toot');
  const popupCover = document.getElementById('elementor-popup-trihod');
  const coverCenterBox = document.getElementById('pembuka');

  if (tootBtn && popupCover) {
    tootBtn.addEventListener('click', function (e) {
      e.preventDefault();

      // Start music
      playAudioMusic();

      // Trigger cover animations
      if (coverCenterBox) coverCenterBox.classList.add('naek');
      document.querySelectorAll('.opw').forEach(el => el.classList.add('lengitkiri'));
      document.querySelectorAll('.opg').forEach(el => el.classList.add('lengitkanan'));

      // Remove body scroll lock
      document.body.classList.remove('modal-open');

      // Trigger main stream reveal
      setTimeout(() => {
        document.querySelectorAll('.opx').forEach(el => el.classList.add('active'));
      }, 300);

      // Fade out and remove cover
      setTimeout(() => {
        popupCover.classList.add('hidden');
        setTimeout(() => {
          popupCover.style.display = 'none';
        }, 1000);
      }, 700);
    });
  }

  /* ===================================================================
     4. SCROLL REVEAL OBSERVER (MUNCL, FADEKIRI, FADEKANAN, FADEZOOM)
     =================================================================== */
  function initScrollReveal() {
    const revealSelectors = '.muncul, .muncul-kiri, .muncul-kanan, .munculAtas, .fadeKiri, .fadeKanan, .fadeZoom, .fadeZoomOut, .fadeBawah, .fadeAtas';
    const elements = document.querySelectorAll(revealSelectors);

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active');
          }
        });
      }, {
        threshold: 0.15,
        rootMargin: '0px 0px -40px 0px'
      });

      elements.forEach(el => observer.observe(el));
    } else {
      // Fallback
      function checkReveal() {
        const triggerBottom = window.innerHeight * 0.88;
        elements.forEach(el => {
          const top = el.getBoundingClientRect().top;
          if (top < triggerBottom) {
            el.classList.add('active');
          }
        });
      }
      window.addEventListener('scroll', checkReveal);
      checkReveal();
    }
  }

  /* ===================================================================
     5. THREE.JS 3D WEDDING RINGS SHOWCASE
     =================================================================== */
  function init3DRings() {
    const canvas = document.getElementById('ringsCanvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const width = canvas.clientWidth || 320;
    const height = canvas.clientHeight || 280;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.z = 8;

    const renderer = new THREE.WebGLRenderer({
      canvas: canvas,
      alpha: true,
      antialias: true
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xfff5e6, 1.2);
    scene.add(ambientLight);

    const pointLight1 = new THREE.PointLight(0xffdfa8, 2.5, 50);
    pointLight1.position.set(5, 5, 5);
    scene.add(pointLight1);

    const pointLight2 = new THREE.PointLight(0xffeedd, 1.5, 50);
    pointLight2.position.set(-5, -3, 3);
    scene.add(pointLight2);

    // Group for both rings
    const ringsGroup = new THREE.Group();
    scene.add(ringsGroup);

    // Material: Shiny Yellow Gold
    const goldMaterial = new THREE.MeshStandardMaterial({
      color: 0xd4af37,
      metalness: 0.88,
      roughness: 0.18,
      wireframe: false
    });

    // Material: Rose Gold / Diamond Accent
    const roseGoldMaterial = new THREE.MeshStandardMaterial({
      color: 0xe0a98b,
      metalness: 0.9,
      roughness: 0.15
    });

    // Ring 1 (Groom's Classic Gold Band)
    const ringGeo1 = new THREE.TorusGeometry(1.6, 0.28, 28, 64);
    const ring1 = new THREE.Mesh(ringGeo1, goldMaterial);
    ring1.rotation.x = Math.PI / 3;
    ring1.position.set(-0.7, 0, 0);
    ringsGroup.add(ring1);

    // Ring 2 (Bride's Intertwined Rose Gold Band)
    const ringGeo2 = new THREE.TorusGeometry(1.5, 0.24, 28, 64);
    const ring2 = new THREE.Mesh(ringGeo2, roseGoldMaterial);
    ring2.rotation.x = -Math.PI / 3.5;
    ring2.rotation.y = Math.PI / 5;
    ring2.position.set(0.7, 0, 0);
    ringsGroup.add(ring2);

    // Diamond gem on Ring 2
    const diamondGeo = new THREE.OctahedronGeometry(0.38, 1);
    const diamondMat = new THREE.MeshStandardMaterial({
      color: 0xffffff,
      metalness: 0.1,
      roughness: 0.05,
      transparent: true,
      opacity: 0.95
    });
    const diamond = new THREE.Mesh(diamondGeo, diamondMat);
    diamond.position.set(0.7, 1.5, 0.4);
    diamond.scale.set(0.9, 1.2, 0.9);
    ringsGroup.add(diamond);

    // Interactive pointer rotation
    let isDragging = false;
    let prevMouseX = 0;
    let prevMouseY = 0;

    canvas.addEventListener('mousedown', (e) => {
      isDragging = true;
      prevMouseX = e.clientX;
      prevMouseY = e.clientY;
    });

    window.addEventListener('mouseup', () => { isDragging = false; });

    window.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      const deltaX = e.clientX - prevMouseX;
      const deltaY = e.clientY - prevMouseY;
      ringsGroup.rotation.y += deltaX * 0.01;
      ringsGroup.rotation.x += deltaY * 0.01;
      prevMouseX = e.clientX;
      prevMouseY = e.clientY;
    });

    // Touch interaction
    canvas.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        isDragging = true;
        prevMouseX = e.touches[0].clientX;
        prevMouseY = e.touches[0].clientY;
      }
    }, { passive: true });

    window.addEventListener('touchend', () => { isDragging = false; });

    window.addEventListener('touchmove', (e) => {
      if (!isDragging || e.touches.length !== 1) return;
      const deltaX = e.touches[0].clientX - prevMouseX;
      const deltaY = e.touches[0].clientY - prevMouseY;
      ringsGroup.rotation.y += deltaX * 0.012;
      ringsGroup.rotation.x += deltaY * 0.012;
      prevMouseX = e.touches[0].clientX;
      prevMouseY = e.touches[0].clientY;
    }, { passive: true });

    // Window resize
    window.addEventListener('resize', () => {
      const newW = canvas.clientWidth || 320;
      const newH = canvas.clientHeight || 280;
      camera.aspect = newW / newH;
      camera.updateProjectionMatrix();
      renderer.setSize(newW, newH);
    });

    // Render loop
    function animate() {
      requestAnimationFrame(animate);
      if (!isDragging) {
        ringsGroup.rotation.y += 0.007;
        ringsGroup.rotation.x = Math.sin(Date.now() * 0.001) * 0.15;
      }
      renderer.render(scene, camera);
    }
    animate();
  }

  /* ===================================================================
     6. COUNTDOWN TIMER (SAVE THE DATE)
     =================================================================== */
  function initCountdown() {
    const targetDate = new Date('2026-06-15T08:00:00+07:00').getTime();

    const dEl = document.getElementById('countDays');
    const hEl = document.getElementById('countHours');
    const mEl = document.getElementById('countMinutes');
    const sEl = document.getElementById('countSeconds');

    if (!dEl || !hEl || !mEl || !sEl) return;

    function update() {
      const now = new Date().getTime();
      const diff = targetDate - now;

      if (diff <= 0) {
        dEl.textContent = '00';
        hEl.textContent = '00';
        mEl.textContent = '00';
        sEl.textContent = '00';
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const secs = Math.floor((diff % (1000 * 60)) / 1000);

      dEl.textContent = String(days).padStart(2, '0');
      hEl.textContent = String(hours).padStart(2, '0');
      mEl.textContent = String(mins).padStart(2, '0');
      sEl.textContent = String(secs).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
  }

  /* ===================================================================
     7. PHOTO GALLERY LIGHTBOX
     =================================================================== */
  const lightboxModal = document.getElementById('lightboxModal');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxCaption = document.getElementById('lightboxCaption');
  const galleryItems = [
    { src: 'assets/images/savan.jpg', caption: 'Evan & Salwa Prewedding' },
    { src: 'assets/images/img1.jpeg', caption: 'Momen Manis Bersama' },
    { src: 'assets/images/img2.jpeg', caption: 'Langkah Awal Cinta Kami' },
    { src: 'assets/images/img3.jpeg', caption: 'Saling Menjaga dan Mendampingi' },
    { src: 'assets/images/img4.jpeg', caption: 'Menatap Masa Depan Berdua' }
  ];
  let currentPhotoIdx = 0;

  window.openLightbox = function (idx) {
    if (!lightboxModal || !lightboxImg) return;
    currentPhotoIdx = idx;
    updateLightbox();
    lightboxModal.classList.add('active');
  };

  window.closeLightbox = function () {
    if (!lightboxModal) return;
    lightboxModal.classList.remove('active');
  };

  window.nextPhoto = function () {
    currentPhotoIdx = (currentPhotoIdx + 1) % galleryItems.length;
    updateLightbox();
  };

  window.prevPhoto = function () {
    currentPhotoIdx = (currentPhotoIdx - 1 + galleryItems.length) % galleryItems.length;
    updateLightbox();
  };

  function updateLightbox() {
    const item = galleryItems[currentPhotoIdx];
    if (lightboxImg) lightboxImg.src = item.src;
    if (lightboxCaption) lightboxCaption.textContent = item.caption;
  }

  document.addEventListener('keydown', (e) => {
    if (!lightboxModal || !lightboxModal.classList.contains('active')) return;
    if (e.key === 'Escape') window.closeLightbox();
    if (e.key === 'ArrowRight') window.nextPhoto();
    if (e.key === 'ArrowLeft') window.prevPhoto();
  });

  /* ===================================================================
     8. TOAST NOTIFICATION & COPY TO CLIPBOARD
     =================================================================== */
  const toastEl = document.getElementById('toastNotice');
  let toastTimer = null;

  window.showToast = function (msg) {
    if (!toastEl) return;
    const msgEl = document.getElementById('toastMessage');
    if (msgEl) msgEl.textContent = msg;

    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastEl.classList.remove('show');
    }, 3200);
  };

  window.copyText = function (text, successMsg) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(() => {
        window.showToast(successMsg || 'Berhasil disalin ke papan klip!');
      }).catch(() => fallbackCopy(text, successMsg));
    } else {
      fallbackCopy(text, successMsg);
    }
  };

  function fallbackCopy(text, successMsg) {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    window.showToast(successMsg || 'Berhasil disalin ke papan klip!');
  }

  /* ===================================================================
     9. WEDDING GIFT TAB SWITCHER
     =================================================================== */
  window.switchGiftTab = function (tabType, btnEl) {
    document.querySelectorAll('.gift-tab-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    const transferPanel = document.getElementById('giftTransferPanel');
    const physicalPanel = document.getElementById('giftPhysicalPanel');

    if (tabType === 'transfer') {
      if (transferPanel) transferPanel.style.display = 'block';
      if (physicalPanel) physicalPanel.style.display = 'none';
    } else {
      if (transferPanel) transferPanel.style.display = 'none';
      if (physicalPanel) physicalPanel.style.display = 'block';
    }
  };

  /* ===================================================================
     10. FRIENDS WISHES & RSVP (DATABASE MYSQL INTEGRATION)
     =================================================================== */
  const wishesListContainer = document.getElementById('wishesListFeed');
  const wishesCounterBadge = document.getElementById('wishesCounterBadge');
  const wishForm = document.getElementById('wishesForm');

  // Quick prayer insertion
  window.insertQuickDoa = function (text) {
    const input = document.getElementById('wishKomentar');
    if (!input) return;
    input.value = text;
    input.focus();
    window.showToast('Doa berhasil ditempel ke form!');
  };

  // Fetch comments from local MySQL database via api/comments.php
  function loadCommentsFromDatabase() {
    if (!wishesListContainer) return;

    fetch('api/comments.php')
      .then(res => res.json())
      .then(result => {
        if (result.success && Array.isArray(result.data)) {
          renderCommentsList(result.data);
          if (wishesCounterBadge) {
            wishesCounterBadge.textContent = `${result.data.length} Doa & Harapan`;
          }
        }
      })
      .catch(err => {
        console.error('Gagal mengambil ucapan dari database MySQL:', err);
      });
  }

  function renderCommentsList(comments) {
    if (!wishesListContainer) return;

    if (comments.length === 0) {
      wishesListContainer.innerHTML = `
        <div style="text-align: center; padding: 30px; color: var(--color-text-muted); font-size: 0.9rem;">
          Belum ada ucapan doa. Jadilah yang pertama memberikan doa restu!
        </div>
      `;
      return;
    }

    wishesListContainer.innerHTML = comments.map(c => {
      const initial = (c.nama || 'T').trim().charAt(0).toUpperCase();
      const isHadir = Number(c.hadir) === 1;
      const hadirBadge = isHadir
        ? `<span class="wish-hadir-badge hadir">🟢 Hadir</span>`
        : `<span class="wish-hadir-badge absen">🔴 Berhalangan</span>`;

      const dateStr = formatDateTime(c.created_at);

      return `
        <div class="wish-single-card">
          <div class="wish-header-line">
            <div class="wish-user-meta">
              <div class="wish-avatar">${initial}</div>
              <div>
                <div class="wish-name">${escapeHtml(c.nama)}</div>
              </div>
            </div>
            ${hadirBadge}
          </div>
          <div class="wish-comment-text">
            "${escapeHtml(c.komentar)}"
          </div>
          <div class="wish-date-footer">
            🕒 ${dateStr}
          </div>
        </div>
      `;
    }).join('');
  }

  function formatDateTime(dateString) {
    if (!dateString) return 'Baru saja';
    try {
      const d = new Date(dateString.replace(' ', 'T'));
      if (isNaN(d.getTime())) return dateString;
      return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    } catch (e) {
      return dateString;
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function (m) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[m];
    });
  }

  // Submit comment to local MySQL database
  if (wishForm) {
    wishForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const namaInput = document.getElementById('wishNama');
      const komentarInput = document.getElementById('wishKomentar');
      const hadirRadio = document.querySelector('input[name="hadir"]:checked');

      const nama = namaInput ? namaInput.value.trim() : '';
      const komentar = komentarInput ? komentarInput.value.trim() : '';
      const hadir = hadirRadio ? parseInt(hadirRadio.value, 10) : 1;

      if (!nama) {
        window.showToast('Silakan isi nama lengkap Anda.');
        return;
      }

      if (!komentar) {
        window.showToast('Silakan tulis ucapan atau doa restu Anda.');
        return;
      }

      const submitBtn = wishForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Mengirim ke database...</span>';
      }

      const payload = {
        nama: nama,
        hadir: hadir,
        komentar: komentar
      };

      fetch('api/comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(res => res.json())
        .then(result => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
              </svg>
              <span>Kirim Ucapan &amp; Doa Restu</span>
            `;
          }

          if (result.success) {
            window.showToast('Doa restu Anda berhasil disimpan di database!');
            if (komentarInput) komentarInput.value = '';
            // Reload comments list from database
            loadCommentsFromDatabase();
          } else {
            window.showToast('Gagal: ' + (result.message || 'Terjadi kesalahan'));
          }
        })
        .catch(err => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Kirim Ucapan &amp; Doa Restu</span>';
          }
          console.error(err);
          window.showToast('Terjadi kesalahan saat menghubungkan ke server MySQL.');
        });
    });
  }

  // Quick WhatsApp RSVP
  window.sendWhatsAppRsvp = function () {
    const namaInput = document.getElementById('wishNama');
    const komentarInput = document.getElementById('wishKomentar');
    const hadirRadio = document.querySelector('input[name="hadir"]:checked');

    const nama = namaInput ? namaInput.value.trim() : 'Tamu Undangan';
    const isHadir = hadirRadio ? parseInt(hadirRadio.value, 10) === 1 : true;
    const kehadiranText = isHadir ? 'Hadir' : 'Berhalangan Hadir';
    const pesan = komentarInput ? komentarInput.value.trim() : '';

    const text = `Halo Evan & Salwa,\n\nSaya *${nama}* ingin mengonfirmasi kehadiran:\nStatus: *${kehadiranText}*\n\n*Doa Restu:*\n"${pesan || 'Selamat menempuh hidup baru, semoga bahagia selalu!'}"\n\nTerima kasih!`;
    const waUrl = `https://wa.me/6285718945476?text=${encodeURIComponent(text)}`;
    window.open(waUrl, '_blank');
  };

  /* ===================================================================
     11. FLOATING NAVIGATION DOCK OBSERVER
     =================================================================== */
  function initNavObserver() {
    const sections = document.querySelectorAll('section[id], div[id="pembuka"], div[id="hom"]');
    const navItems = document.querySelectorAll('.dock-item-btn');

    if (!('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.getAttribute('id');
          navItems.forEach(item => {
            if (item.getAttribute('href') === `#${id}`) {
              item.classList.add('active');
            } else {
              item.classList.remove('active');
            }
          });
        }
      });
    }, {
      rootMargin: '-30% 0px -50% 0px'
    });

    sections.forEach(sec => observer.observe(sec));
  }

  /* ===================================================================
     INITIALIZATION ON DOM READY
     =================================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    initGuestPersonalization();
    initScrollReveal();
    initCountdown();
    init3DRings();
    loadCommentsFromDatabase();
    initNavObserver();
  });

})();
