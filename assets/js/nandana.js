/**
 * NANDANA THEME SCRIPT - RINGVITATION LUXURY WEDDING
 * Enhanced 3D WebGL Rings, Ambient Gold Particles, 3D Card Parallax Tilt,
 * MySQL Database Comments & Dynamic Settings Integration from /admin
 */

(function () {
  'use strict';

  // Global settings state
  let weddingSettings = null;
  let targetWeddingTimestamp = new Date('2026-06-15T08:00:00+07:00').getTime();

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
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
      });

      elements.forEach(el => observer.observe(el));
    } else {
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
     5. THREE.JS 3D WEDDING RINGS SHOWCASE (ENHANCED 3D DEPTH)
     =================================================================== */
  function init3DRings() {
    const canvas = document.getElementById('ringsCanvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const container = canvas.parentElement;
    const width = container ? container.clientWidth : 340;
    const height = 270;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(42, width / height, 0.1, 1000);
    camera.position.z = 8.2;

    const renderer = new THREE.WebGLRenderer({
      canvas: canvas,
      alpha: true,
      antialias: true,
      powerPreference: 'high-performance'
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Ambient Warm Light
    const ambientLight = new THREE.AmbientLight(0xfff7ed, 1.4);
    scene.add(ambientLight);

    // Interactive Key Point Light (Follows Cursor)
    const keyLight = new THREE.PointLight(0xffdfa8, 3.2, 35);
    keyLight.position.set(4, 5, 5);
    scene.add(keyLight);

    // Fill Cool Light for Specular Facets
    const fillLight = new THREE.PointLight(0xf5e6cc, 2.0, 35);
    fillLight.position.set(-5, -3, 3);
    scene.add(fillLight);

    // Rings Group
    const ringsGroup = new THREE.Group();
    scene.add(ringsGroup);

    // Material 1: Polished Yellow Gold
    const goldMaterial = new THREE.MeshStandardMaterial({
      color: 0xdfb746,
      metalness: 0.92,
      roughness: 0.12,
      wireframe: false
    });

    // Material 2: Romantic Rose Gold
    const roseGoldMaterial = new THREE.MeshStandardMaterial({
      color: 0xe5a38b,
      metalness: 0.94,
      roughness: 0.14
    });

    // Ring 1 (Groom's Classic Gold Band)
    const ringGeo1 = new THREE.TorusGeometry(1.65, 0.28, 32, 80);
    const ring1 = new THREE.Mesh(ringGeo1, goldMaterial);
    ring1.rotation.x = Math.PI / 3;
    ring1.position.set(-0.7, 0, 0);
    ringsGroup.add(ring1);

    // Ring 2 (Bride's Intertwined Rose Gold Band)
    const ringGeo2 = new THREE.TorusGeometry(1.55, 0.24, 32, 80);
    const ring2 = new THREE.Mesh(ringGeo2, roseGoldMaterial);
    ring2.rotation.x = -Math.PI / 3.4;
    ring2.rotation.y = Math.PI / 5.2;
    ring2.position.set(0.7, 0, 0);
    ringsGroup.add(ring2);

    // Diamond Gem on Ring 2
    const diamondGeo = new THREE.OctahedronGeometry(0.42, 1);
    const diamondMat = new THREE.MeshStandardMaterial({
      color: 0xffffff,
      metalness: 0.15,
      roughness: 0.04,
      transparent: true,
      opacity: 0.96
    });
    const diamond = new THREE.Mesh(diamondGeo, diamondMat);
    diamond.position.set(0.7, 1.58, 0.38);
    diamond.scale.set(0.95, 1.3, 0.95);
    ringsGroup.add(diamond);

    // Diamond Crown Setting Prongs
    const prongGeo = new THREE.CylinderGeometry(0.04, 0.04, 0.35, 8);
    const prongMat = roseGoldMaterial;
    for (let i = 0; i < 4; i++) {
      const angle = (i * Math.PI) / 2;
      const prong = new THREE.Mesh(prongGeo, prongMat);
      prong.position.set(
        0.7 + Math.cos(angle) * 0.22,
        1.45,
        0.38 + Math.sin(angle) * 0.22
      );
      ringsGroup.add(prong);
    }

    // Swirling Golden Dust Particles (Sparkles Cloud)
    const particleCount = 280;
    const particleGeo = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    const particleSpeeds = [];

    for (let i = 0; i < particleCount; i++) {
      const r = 2.2 + Math.random() * 2.5;
      const theta = Math.random() * Math.PI * 2;
      const phi = (Math.random() - 0.5) * Math.PI;

      particlePositions[i * 3] = r * Math.cos(theta) * Math.cos(phi);
      particlePositions[i * 3 + 1] = r * Math.sin(phi);
      particlePositions[i * 3 + 2] = r * Math.sin(theta) * Math.cos(phi);

      particleSpeeds.push({
        r: r,
        theta: theta,
        speed: 0.003 + Math.random() * 0.008,
        yOffset: particlePositions[i * 3 + 1]
      });
    }

    particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particleMat = new THREE.PointsMaterial({
      color: 0xf3d692,
      size: 0.065,
      transparent: true,
      opacity: 0.85,
      blending: THREE.AdditiveBlending
    });
    const particleSystem = new THREE.Points(particleGeo, particleMat);
    scene.add(particleSystem);

    // Interactive pointer drag & mouse movement
    let isDragging = false;
    let prevMouseX = 0;
    let prevMouseY = 0;
    let velocityX = 0;
    let velocityY = 0;

    canvas.addEventListener('mousedown', (e) => {
      isDragging = true;
      prevMouseX = e.clientX;
      prevMouseY = e.clientY;
      velocityX = 0;
      velocityY = 0;
    });

    window.addEventListener('mouseup', () => { isDragging = false; });

    window.addEventListener('mousemove', (e) => {
      if (isDragging) {
        const deltaX = e.clientX - prevMouseX;
        const deltaY = e.clientY - prevMouseY;
        velocityX = deltaX * 0.01;
        velocityY = deltaY * 0.01;
        ringsGroup.rotation.y += velocityX;
        ringsGroup.rotation.x += velocityY;
        prevMouseX = e.clientX;
        prevMouseY = e.clientY;
      }

      // Move keyLight according to cursor
      const rect = canvas.getBoundingClientRect();
      const normX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      const normY = -(((e.clientY - rect.top) / rect.height) * 2 - 1);
      keyLight.position.x = normX * 6;
      keyLight.position.y = normY * 6;
    });

    // Touch interaction
    canvas.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        isDragging = true;
        prevMouseX = e.touches[0].clientX;
        prevMouseY = e.touches[0].clientY;
        velocityX = 0;
        velocityY = 0;
      }
    }, { passive: true });

    window.addEventListener('touchend', () => { isDragging = false; });

    window.addEventListener('touchmove', (e) => {
      if (!isDragging || e.touches.length !== 1) return;
      const deltaX = e.touches[0].clientX - prevMouseX;
      const deltaY = e.touches[0].clientY - prevMouseY;
      velocityX = deltaX * 0.012;
      velocityY = deltaY * 0.012;
      ringsGroup.rotation.y += velocityX;
      ringsGroup.rotation.x += velocityY;
      prevMouseX = e.touches[0].clientX;
      prevMouseY = e.touches[0].clientY;
    }, { passive: true });

    // Responsive resize
    window.addEventListener('resize', () => {
      const newW = container ? container.clientWidth : 340;
      camera.aspect = newW / height;
      camera.updateProjectionMatrix();
      renderer.setSize(newW, height);
    });

    // Render animation loop
    function animateRings() {
      requestAnimationFrame(animateRings);

      if (!isDragging) {
        ringsGroup.rotation.y += 0.008 + velocityX;
        ringsGroup.rotation.x = Math.sin(Date.now() * 0.0012) * 0.16 + velocityY;
        velocityX *= 0.94;
        velocityY *= 0.94;
      }

      // Animate swirling sparkles
      const positions = particleGeo.attributes.position.array;
      for (let i = 0; i < particleCount; i++) {
        const p = particleSpeeds[i];
        p.theta += p.speed;
        positions[i * 3] = p.r * Math.cos(p.theta);
        positions[i * 3 + 2] = p.r * Math.sin(p.theta);
        positions[i * 3 + 1] = p.yOffset + Math.sin(Date.now() * 0.0015 + i) * 0.2;
      }
      particleGeo.attributes.position.needsUpdate = true;
      particleSystem.rotation.y += 0.002;

      renderer.render(scene, camera);
    }
    animateRings();
  }

  /* ===================================================================
     6. AMBIENT 3D PARTICLES CANVAS (GOLD DUST & FLOATING PETALS)
     =================================================================== */
  function initAmbientParticlesCanvas() {
    const canvas = document.getElementById('ambientParticlesCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = (canvas.width = window.innerWidth);
    let height = (canvas.height = window.innerHeight);

    window.addEventListener('resize', () => {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
    });

    // Generate Petal & Gold Sparkle Items
    const totalItems = Math.min(Math.floor(width / 30), 45);
    const particles = [];

    for (let i = 0; i < totalItems; i++) {
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 5 + 3,
        speedY: Math.random() * 0.65 + 0.35,
        speedX: Math.random() * 0.4 - 0.2,
        oscillation: Math.random() * Math.PI * 2,
        oscillationSpeed: Math.random() * 0.02 + 0.01,
        rotation: Math.random() * Math.PI * 2,
        rotSpeed: (Math.random() - 0.5) * 0.03,
        opacity: Math.random() * 0.45 + 0.25,
        isPetal: Math.random() > 0.45 // 55% soft petals, 45% gold flakes
      });
    }

    let mouseX = width / 2;
    window.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
    });

    function renderParticles() {
      ctx.clearRect(0, 0, width, height);

      particles.forEach(p => {
        p.oscillation += p.oscillationSpeed;
        p.y += p.speedY;
        p.x += Math.sin(p.oscillation) * 0.6 + p.speedX;
        p.rotation += p.rotSpeed;

        // Subtle mouse wind drift
        const dx = mouseX - p.x;
        p.x += dx * 0.0003;

        // Recycle if below canvas
        if (p.y > height + 20) {
          p.y = -20;
          p.x = Math.random() * width;
        }
        if (p.x < -20) p.x = width + 20;
        if (p.x > width + 20) p.x = -20;

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rotation);

        if (p.isPetal) {
          // Soft Warm Rose Petal
          ctx.beginPath();
          ctx.ellipse(0, 0, p.size * 1.5, p.size * 0.9, 0, 0, Math.PI * 2);
          ctx.fillStyle = `rgba(197, 160, 89, ${p.opacity * 0.55})`;
          ctx.fill();
        } else {
          // Sparkle Gold Flake
          ctx.beginPath();
          ctx.arc(0, 0, p.size * 0.55, 0, Math.PI * 2);
          ctx.fillStyle = `rgba(224, 185, 110, ${p.opacity})`;
          ctx.shadowBlur = 8;
          ctx.shadowColor = 'rgba(212, 175, 55, 0.6)';
          ctx.fill();
        }

        ctx.restore();
      });

      requestAnimationFrame(renderParticles);
    }
    renderParticles();
  }

  /* ===================================================================
     7. INTERACTIVE 3D CARD PARALLAX TILT EFFECT
     =================================================================== */
  function initCard3DTilt() {
    const cards = document.querySelectorAll('.nandana-card, .hero-arch-wrapper');
    if (!cards.length) return;

    cards.forEach(card => {
      // Add dynamic specular glare overlay
      const glare = document.createElement('div');
      glare.className = 'card-specular-glare';
      card.appendChild(glare);

      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -6; // max 6 deg
        const rotateY = ((x - centerX) / centerX) * 6;

        card.style.transform = `perspective(900px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.015, 1.015, 1.015)`;

        // Position glare highlight
        const percentX = (x / rect.width) * 100;
        const percentY = (y / rect.height) * 100;
        glare.style.opacity = '1';
        glare.style.background = `radial-gradient(circle at ${percentX}% ${percentY}%, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0) 65%)`;
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(900px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
        glare.style.opacity = '0';
      });
    });
  }

  /* ===================================================================
     8. DYNAMIC SETTINGS LOADER FROM API (/admin)
     =================================================================== */
  function loadWeddingSettings() {
    fetch('api/settings.php')
      .then(res => res.json())
      .then(result => {
        if (!result.success || !result.data) return;
        weddingSettings = result.data;
        applySettingsToDOM(weddingSettings);
      })
      .catch(err => {
        console.log('Menggunakan pengaturan default lokal:', err);
      });
  }

  function applySettingsToDOM(s) {
    if (!s) return;

    // 1. General & Dates
    if (s.general) {
      if (s.general.wedding_date) {
        targetWeddingTimestamp = new Date(s.general.wedding_date).getTime();
      }
      if (s.general.wedding_date_formatted) {
        const heroDate = document.getElementById('heroDateText');
        const desktopDate = document.getElementById('desktopDateText');
        if (heroDate) heroDate.textContent = s.general.wedding_date_formatted;
        if (desktopDate) desktopDate.textContent = `✦ ${s.general.wedding_date_formatted} • ${s.events?.akad?.venue || 'Masjid Istiqlal Jakarta'} ✦`;
      }
      if (s.general.greeting_title) {
        const el = document.getElementById('greetingTitle');
        if (el) el.textContent = s.general.greeting_title;
      }
      if (s.general.greeting_message) {
        const el = document.getElementById('greetingMessage');
        if (el) el.textContent = s.general.greeting_message;
      }
      if (s.general.love_quote) {
        const el = document.getElementById('loveQuoteBody');
        const deskQuote = document.getElementById('desktopQuoteText');
        if (el) el.textContent = `"${s.general.love_quote}"`;
        if (deskQuote) deskQuote.textContent = `"${s.general.love_quote}"`;
      }
      if (s.general.quote_author) {
        const el = document.getElementById('quoteAuthor');
        if (el) el.textContent = `— ${s.general.quote_author} —`;
      }
    }

    // 2. Groom Details
    if (s.groom) {
      if (s.groom.fullname) {
        const el = document.getElementById('groomFullName');
        if (el) el.textContent = s.groom.fullname;
      }
      if (s.groom.nickname) {
        const el = document.getElementById('groomNickName');
        if (el) el.textContent = s.groom.nickname;
      }
      if (s.groom.parents) {
        const el = document.getElementById('groomParents');
        if (el) el.innerHTML = `${s.groom.parents}`;
      }
      if (s.groom.instagram) {
        const link = document.getElementById('groomIgLink');
        const handle = document.getElementById('groomIgHandle');
        if (link) link.href = `https://instagram.com/${s.groom.instagram}`;
        if (handle) handle.textContent = `@${s.groom.instagram}`;
      }
      if (s.groom.photo) {
        const img = document.getElementById('groomPhotoImg');
        if (img) img.src = s.groom.photo;
      }
    }

    // 3. Bride Details
    if (s.bride) {
      if (s.bride.fullname) {
        const el = document.getElementById('brideFullName');
        if (el) el.textContent = s.bride.fullname;
      }
      if (s.bride.nickname) {
        const el = document.getElementById('brideNickName');
        if (el) el.textContent = s.bride.nickname;
      }
      if (s.bride.parents) {
        const el = document.getElementById('brideParents');
        if (el) el.innerHTML = `${s.bride.parents}`;
      }
      if (s.bride.instagram) {
        const link = document.getElementById('brideIgLink');
        const handle = document.getElementById('brideIgHandle');
        if (link) link.href = `https://instagram.com/${s.bride.instagram}`;
        if (handle) handle.textContent = `@${s.bride.instagram}`;
      }
      if (s.bride.photo) {
        const img = document.getElementById('bridePhotoImg');
        if (img) img.src = s.bride.photo;
      }
    }

    // 4. Photos (Cover & Gallery)
    if (s.cover && s.cover.photo) {
      const heroPhoto = document.getElementById('heroPhotoImg');
      if (heroPhoto) heroPhoto.src = s.cover.photo;
    }

    if (Array.isArray(s.gallery) && s.gallery.length) {
      s.gallery.forEach((url, i) => {
        if (galleryItems[i]) galleryItems[i].src = url;
      });
      // Update gallery grid thumbnails in DOM
      const thumbs = document.querySelectorAll('.gallery-thumb-item img');
      thumbs.forEach((img, i) => {
        if (s.gallery[i]) img.src = s.gallery[i];
      });
    }

    // 5. Events (Akad & Resepsi)
    if (s.events) {
      if (s.events.akad) {
        const a = s.events.akad;
        const title = document.getElementById('eventAkadTitle');
        const sub = document.getElementById('eventAkadSubtitle');
        if (title && a.title) title.textContent = a.title;
        if (sub && a.subtitle) sub.textContent = a.subtitle;
      }
      if (s.events.resepsi) {
        const r = s.events.resepsi;
        const title = document.getElementById('eventResepsiTitle');
        const sub = document.getElementById('eventResepsiSubtitle');
        if (title && r.title) title.textContent = r.title;
        if (sub && r.subtitle) sub.textContent = r.subtitle;
      }
    }

    // 6. Gift Accounts
    if (s.gift) {
      const bcaNum = document.getElementById('bcaNumberText');
      const bcaName = document.getElementById('bcaHolderText');
      const danaNum = document.getElementById('danaNumberText');
      if (bcaNum && s.gift.bca_no) bcaNum.textContent = s.gift.bca_no;
      if (bcaName && s.gift.bca_name) bcaName.textContent = `a.n. ${s.gift.bca_name}`;
      if (danaNum && s.gift.dana_no) danaNum.textContent = s.gift.dana_no;
    }
  }

  /* ===================================================================
     9. COUNTDOWN TIMER (SAVE THE DATE)
     =================================================================== */
  function initCountdown() {
    const dEl = document.getElementById('countDays');
    const hEl = document.getElementById('countHours');
    const mEl = document.getElementById('countMinutes');
    const sEl = document.getElementById('countSeconds');

    if (!dEl || !hEl || !mEl || !sEl) return;

    function update() {
      const now = new Date().getTime();
      const diff = targetWeddingTimestamp - now;

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
     10. PHOTO GALLERY LIGHTBOX
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
     11. TOAST NOTIFICATION & COPY TO CLIPBOARD
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
     12. WEDDING GIFT TAB SWITCHER
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
     13. FRIENDS WISHES & RSVP (DATABASE MYSQL INTEGRATION)
     =================================================================== */
  const wishesListContainer = document.getElementById('wishesListFeed');
  const wishesCounterBadge = document.getElementById('wishesCounterBadge');
  const wishForm = document.getElementById('wishesForm');

  // Quick casual wish insertion
  window.insertQuickDoa = function (text) {
    const input = document.getElementById('wishKomentar');
    if (!input) return;
    input.value = text;
    input.focus();
    window.showToast('Ucapan berhasil ditempel ke formulir!');
  };

  // Fetch comments from MySQL database via api/comments.php
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

  // Submit comment to MySQL database
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
        submitBtn.innerHTML = '<span>Menyimpan ke database...</span>';
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
            window.showToast('✨ Doa restu Anda berhasil disimpan di database!');
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
          window.showToast('Terjadi kesalahan saat menghubungkan ke database.');
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
     14. FLOATING NAVIGATION DOCK OBSERVER
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
    loadWeddingSettings();
    initScrollReveal();
    initCountdown();
    init3DRings();
    initAmbientParticlesCanvas();
    initCard3DTilt();
    loadCommentsFromDatabase();
    initNavObserver();
  });

})();
