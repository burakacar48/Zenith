window.addEventListener('DOMContentLoaded', () => {
    // --- GENİŞLETME DÜZENLEMESİ ---
    // Hoşgeldiniz modalının içindeki içerik kutusunu bulup genişliğini artırıyoruz.
    const welcomeModalContent = document.querySelector('#welcome-modal .modal-content');
    if (welcomeModalContent) {
        welcomeModalContent.style.maxWidth = '640px'; // Genişliği 640px olarak ayarlıyoruz.
    }

    // DÜZELTME: SERVER_URL değişkeni kaldırıldı.
    // Uygulama artık doğrudan sunucudan yüklendiği için, fetch istekleri otomatik olarak doğru adrese (örn: /api/games) gidecektir.
    let authToken = null;
    let currentUser = null;
    let allGames = [];
    let allCategories = [];
    let userRatings = {};
    let userFavorites = new Set();
    let userSaves = new Set();
    let userRecentlyPlayed = [];
    let currentFilter = 'all';
    let currentSort = 'newest';
    let sliderInterval;
    let currentSlide = 0;
    let lastPlayedGame = null; // Son oynanan oyunu takip etmek için
    let slides = [];

    // === Arayüz Elementleri ===
    const gamesGrid = document.getElementById('gamesGrid');
    const searchInput = document.getElementById('searchInput');
    const userActions = document.getElementById('user-actions');
    const categoryListSidebar = document.getElementById('category-list-sidebar');
    const heroSection = document.getElementById('hero-section');
    const welcomeModal = document.getElementById('welcome-modal');
    const welcomeLoginBtn = document.getElementById('welcome-login-btn');
    const welcomeRegisterBtn = document.getElementById('welcome-register-btn');
    const welcomeText = document.getElementById('welcome-text');
    const loginModal = document.getElementById('login-modal');
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const modalTitle = document.getElementById('modal-title');
    const modalButton = document.getElementById('modal-button');
    const modalSwitchToRegister = document.getElementById('to-register');
    const modalSwitchToLogin = document.getElementById('to-login');
    const gameDetailModal = document.getElementById('game-detail-modal');
    const detailTitle = document.getElementById('detail-title');
    const detailDescription = document.getElementById('detail-description');
    const detailMeta = document.getElementById('detail-meta');
    const mainMedia = document.getElementById('detail-main-media');
    const thumbnailStrip = document.getElementById('detail-thumbnail-strip');
    const primaryActionsContainer = document.getElementById('primary-actions-container');
    const cloudActionsContainer = document.getElementById('cloud-actions-container');
    const userRatingStars = document.getElementById('user-rating-stars');
    const userRatingInner = userRatingStars ? userRatingStars.querySelector('.stars-inner') : null;
    const averageRatingSummary = document.getElementById('average-rating-summary');
    const favoriteButton = document.getElementById('favorite-button');
    const similarGamesSection = document.querySelector('.similar-games-section');
    const similarGamesGrid = document.getElementById('similar-games-grid');

    const openWelcomeModal = () => { if (welcomeModal) welcomeModal.style.display = 'flex'; };
    const closeWelcomeModal = () => { if (welcomeModal) welcomeModal.style.display = 'none'; };

    const fetchAndApplySettings = () => {
        fetch(`/api/settings`)
            .then(res => res.json())
            .then(settings => {
                const root = document.documentElement;
                root.style.setProperty('--primary-start', settings.primary_color_start || '#667eea');
                root.style.setProperty('--primary-end', settings.primary_color_end || '#764ba2');
                const logoContainer = document.getElementById('logo-image-container');
                const cafeLogoText = document.getElementById('cafe-logo');
                const cafeTagline = document.getElementById('cafe-tagline');
                if (cafeTagline) cafeTagline.innerText = settings.slogan || '';
                if (settings.logo_file) { // DÜZELTME: Admin paneli static yolunu kullan
                    logoContainer.innerHTML = `<img src="/admin_static/images/logos/${settings.logo_file}" alt="Kafe Logosu">`;
                    logoContainer.classList.remove('hidden');
                    cafeLogoText.classList.add('hidden');
                } else {
                    if (cafeLogoText) cafeLogoText.innerText = settings.cafe_name || 'Kafe Adı';
                    logoContainer.classList.add('hidden');
                    cafeLogoText.classList.remove('hidden');
                }
                if (settings.welcome_modal_enabled === '1') {
                    if (welcomeText) welcomeText.textContent = settings.welcome_modal_text;
                    if (!sessionStorage.getItem('welcomeShown')) {
                        openWelcomeModal();
                        sessionStorage.setItem('welcomeShown', 'true');
                    }
                }
                const socialLinks = [
                    { id: 'social-google', url: settings.social_google },
                    { id: 'social-instagram', url: settings.social_instagram },
                    { id: 'social-facebook', url: settings.social_facebook },
                    { id: 'social-youtube', url: settings.social_youtube }
                ];
                socialLinks.forEach(link => {
                    const element = document.getElementById(link.id);
                    if (element) {
                        if (link.url && link.url.trim()) {
                            element.href = link.url;
                            element.style.display = 'flex';
                        } else {
                            element.style.display = 'none';
                        }
                    }
                });
            })
            .catch(error => console.error('Ayarlar çekilirken hata oluştu:', error));
    };

    const renderGames = (filter = 'all', searchTerm = '') => {
        let filtered = [];
    
        if (filter === 'recent') {
            if (!authToken) {
                gamesGrid.innerHTML = `<p style="color: #aaa; grid-column: 1 / -1; text-align: center;">Son oynanan oyunları görmek için lütfen giriş yapın.</p>`;
                updateSectionTitle(filter);
                return;
            }
            filtered = userRecentlyPlayed;
        } else {
            filtered = [...allGames];
        }
    
        if (filter !== 'recent') {
            // Ana sayfa (filter='all') ise sadece öne çıkanları göster
            if (filter === 'all' && !searchTerm) {
                filtered = allGames.filter(g => g.is_featured);
                // Sunucudan zaten sıralı geldiği için tekrar sıralamaya gerek yok.
            } else if (filter === 'newest') {
                // Yeni Oyunlar filtresi: Tüm oyunları ID'ye göre büyükten küçüğe sırala
                filtered.sort((a, b) => b.id - a.id);
                // Bu sıralamada öne çıkanlar yine de en başta olsun istenirse aşağıdaki blok kullanılabilir.
                // Ama "Yeni Oyunlar" için saf tarih sıralaması daha mantıklıdır.
            } else {
                // Diğer tüm filtreler için (Tüm Oyunlar, Kategoriler vb.)
                const featuredGames = filtered.filter(g => g.is_featured);
                const nonFeaturedGames = filtered.filter(g => !g.is_featured);
    
                switch (currentSort) {
                    case 'newest': nonFeaturedGames.sort((a, b) => b.id - a.id); break;
                    case 'popular': nonFeaturedGames.sort((a, b) => b.click_count - a.click_count); break;
                    case 'name': nonFeaturedGames.sort((a, b) => a.oyun_adi.localeCompare(b.oyun_adi, 'tr')); break;
                    case 'rating': nonFeaturedGames.sort((a, b) => b.average_rating - a.average_rating); break;
                }
                // Öne çıkanları her zaman başa alarak listeyi birleştir
                filtered = [...featuredGames, ...nonFeaturedGames];
            }
        }
    
        heroSection.style.display = (filter === 'all' && !searchTerm) ? 'block' : 'none';
    
        if (filter !== 'all' && filter !== 'favorites' && filter !== 'recent' && filter !== 'discover' && filter !== 'newest') {
            filtered = filtered.filter(g => g.kategoriler && g.kategoriler.includes(filter));
        } else if (filter === 'favorites') {
             // ... (favoriler mantığı aynı kalıyor)
            filtered = filtered.filter(g => userFavorites.has(g.id));
        }
    
        if (searchTerm) {
            filtered = filtered.filter(g => g.oyun_adi.toLowerCase().includes(searchTerm.toLowerCase()));
        }
    
        gamesGrid.innerHTML = '';
        if (filtered.length > 0) {
            filtered.forEach(game => {
                // DÜZELTME: Kategori listesinin varlığı ve dolu olup olmadığı daha güvenli bir şekilde kontrol ediliyor.
                const categoriesText = game.kategoriler && game.kategoriler.length > 0 ? game.kategoriler.join(', ') : 'Kategorisiz';
                gamesGrid.innerHTML += `
                    <div class="game-card" data-game-id="${game.id}">
                        <div class="game-image-wrapper"> 
                            <img src="/admin_static/images/covers/${game.cover_image}" alt="${game.oyun_adi}" class="game-image" onerror="this.src='https://via.placeholder.com/400x500/1a1a1a/ef4444?text=${encodeURIComponent(game.oyun_adi)}'">
                            <div class="game-overlay"><div class="play-btn">ℹ️</div></div>
                        </div>
                        <div class="game-info">
                            <div class="game-title">${game.oyun_adi}</div>
                            <div class="game-category">${categoriesText}</div>
                        </div>
                    </div>`;
            });
        } else {
            let message = 'Bu filtreye uygun oyun bulunamadı.';
            if (filter === 'favorites' && authToken) message = 'Henüz favori oyununuz bulunmuyor.';
            if (filter === 'recent' && authToken) message = 'Son oynanan oyun bulunmuyor.';
            gamesGrid.innerHTML = `<p style="color: #aaa; grid-column: 1 / -1; text-align: center;">${message}</p>`;
        }
        updateSectionTitle(filter);
    };
    
    const showGameDetail = (game) => {
        detailTitle.textContent = game.oyun_adi;
        detailDescription.innerHTML = game.aciklama || "Açıklama bulunmuyor.";
        
        // DÜZELTME: Kategori listesinin varlığı ve dolu olup olmadığı daha güvenli bir şekilde kontrol ediliyor.
        const categoriesText = game.kategoriler && game.kategoriler.length > 0 ? game.kategoriler.join(', ') : 'N/A';
        detailMeta.innerHTML = ` 
            <div class="meta-item"><span><i class="fas fa-tags"></i> Kategori:</span><span>${categoriesText}</span></div>
            <div class="meta-item"><span><i class="fas fa-calendar-alt"></i> Çıkış Yılı:</span><span>${game.cikis_yili || 'N/A'}</span></div>
            <div class="meta-item"><span><i class="fas fa-shield-alt"></i> PEGI:</span><span>${game.pegi || 'N/A'}</span></div>
            <div class="meta-item"><span><i class="fas fa-language"></i> Oyun Dili:</span><span>${game.oyun_dili || 'Belirtilmemiş'}</span></div>`;
        updateRatingDisplay(game);
        updateFavoriteDisplay(game.id);
        let galleryItems = [];
        if (game.youtube_id) galleryItems.push({ type: 'video', id: game.youtube_id });
        if (game.galeri) game.galeri.forEach(img => galleryItems.push({ type: 'image', src: `/admin_static/images/gallery/${img}` }));
        let currentMediaIndex = 0;
        let isGalleryTransitioning = false;
        const updateMainMedia = (index) => {
            if (isGalleryTransitioning) return;
            isGalleryTransitioning = true;
            const item = galleryItems[index];
            mainMedia.innerHTML = '';
            if (item.type === 'video') {
                mainMedia.innerHTML = `<div class="video-thumb" style="background-image: url(https://img.youtube.com/vi/${item.id}/mqdefault.jpg)"><i class="fab fa-youtube"></i></div>`;
                mainMedia.querySelector('.video-thumb').onclick = () => { mainMedia.innerHTML = `<iframe src="https://www.youtube.com/embed/${item.id}?autoplay=1" allow="autoplay; fullscreen"></iframe>`; };
            } else {
                mainMedia.innerHTML = `<img src="${item.src}">`;
                const prev = document.createElement('div'); prev.className = 'media-nav prev'; prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
                const next = document.createElement('div'); next.className = 'media-nav next'; next.innerHTML = '<i class="fas fa-chevron-right"></i>';
                prev.onclick = () => { currentMediaIndex = (currentMediaIndex - 1 + galleryItems.length) % galleryItems.length; updateMainMedia(currentMediaIndex); };
                next.onclick = () => { currentMediaIndex = (currentMediaIndex + 1) % galleryItems.length; updateMainMedia(currentMediaIndex); };
                mainMedia.append(prev, next);
            }
            document.querySelectorAll('.thumbnail-item').forEach((thumb, i) => thumb.classList.toggle('active', i === index));
            currentMediaIndex = index;
            thumbnailStrip.querySelector('.thumbnail-item.active')?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            setTimeout(() => { isGalleryTransitioning = false; }, 200);
        };

        thumbnailStrip.innerHTML = '';
        galleryItems.forEach((item, index) => {
            const thumb = document.createElement('div');
            thumb.className = 'thumbnail-item';
            thumb.innerHTML = item.type === 'video' ? `<div class="video-thumb" style="background-image: url(https://img.youtube.com/vi/${item.id}/mqdefault.jpg)"><i class="fab fa-youtube"></i></div>` : `<img src="${item.src}">`;
            thumb.onclick = () => updateMainMedia(index);
            thumbnailStrip.appendChild(thumb);
        });

        if (galleryItems.length > 0) updateMainMedia(0);

        // --- YENİ BUTON DÜZENİ ---
        primaryActionsContainer.innerHTML = '';
        cloudActionsContainer.innerHTML = '';

        // "Şimdi Oyna" butonu
        const playBtn = document.createElement('button');
        playBtn.className = 'hero-btn primary';
        playBtn.innerHTML = '▶ Şimdi Oyna';
        playBtn.dataset.gameId = game.id;
        playBtn.onclick = () => { closeGameDetail(); syncAndLaunch(game); };
        primaryActionsContainer.appendChild(playBtn);

        // "%100 Save" butonu
        if (game.yuzde_yuz_save_path) {
            const saveBtn = document.createElement('button');
            saveBtn.className = 'hero-btn secondary';
            saveBtn.innerHTML = '<i class="fas fa-medal"></i> %100 Save Yükle';
            saveBtn.onclick = () => handle100Save(game.id, game.save_yolu);
            primaryActionsContainer.appendChild(saveBtn);
        }

        // Manuel save butonları
        renderSaveButtons(game, cloudActionsContainer);

        // --- YENİ BUTON DÜZENİ SONU ---

        if (gameDetailModal) gameDetailModal.style.display = 'flex';
        const similarGames = allGames.filter(g => g.id !== game.id && g.kategoriler && g.kategoriler.some(cat => game.kategoriler.includes(cat))).slice(0, 5);
        renderSimilarGames(similarGames);
    };

    const closeGameDetail = () => {
        if (gameDetailModal) gameDetailModal.style.display = 'none';
        mainMedia.innerHTML = '';
        if (similarGamesSection) similarGamesSection.classList.add('hidden');
    };

    const renderSimilarGames = (similarGames) => {
        if (!similarGamesGrid) return;
        similarGamesGrid.innerHTML = '';
        if (similarGames && similarGames.length > 0) {
            similarGames.forEach(game => {
                similarGamesGrid.innerHTML += `
                <div class="game-card similar" data-game-id="${game.id}"> 
                    <div class="game-image-wrapper"><img src="/admin_static/images/covers/${game.cover_image}" alt="${game.oyun_adi}" class="game-image"></div>
                    <div class="game-info"><div class="game-title">${game.oyun_adi}</div></div>
                </div>`;
            });
            if (similarGamesSection) similarGamesSection.classList.remove('hidden');
        } else {
            if (similarGamesSection) similarGamesSection.classList.add('hidden');
        }
    };
    
    // %100 Save yükleme fonksiyonu
    const handle100Save = async (gameId, savePath) => {
        if (!authToken) {
            alert("Bu özelliği kullanmak için giriş yapmalısınız.");
            return;
        }
        if (!confirm("%100 save dosyasını yüklemek, mevcut kayıtlarınızın üzerine yazacaktır. Devam etmek istediğinize emin misiniz?")) {
            return;
        }
        try {
            const response = await fetch(`/api/games/${gameId}/100save`, { headers: { 'Authorization': `Bearer ${authToken}` } });
            if (!response.ok) throw new Error('Sunucudan %100 save dosyası indirilemedi.');
            const saveDataBuffer = await response.arrayBuffer();
            await window.electronAPI.unzipSave({ saveDataBuffer, savePath: savePath });
            alert('Oyunun %100 save dosyası başarıyla yüklendi!');
        } catch (error) {
            alert(`Hata: ${error.message}`);
        }
    };

    // --- YENİ: MANUEL SAVE YÖNETİMİ ---
    const renderSaveButtons = (game, container) => {
        // Eski save alanını temizle (artık kullanılmıyor ama tedbiren)
        document.getElementById('save-actions-area')?.remove();

        // Sadece giriş yapılmışsa ve oyunun save yolu varsa butonları göster
        if (!authToken || !game.save_yolu) {
            return;
        }

        const hasCloudSave = userSaves.has(game.id);
        const cloudIcon = hasCloudSave 
            ? `<i class="fas fa-cloud-check" style="color: #34d399;"></i>` 
            : `<i class="fas fa-cloud-slash" style="color: #94a3b8;"></i>`;

        // Butonları ve durumu barındıracak yeni bir div oluştur
        const saveActionsWrapper = document.createElement('div');
        saveActionsWrapper.className = 'save-actions';
        saveActionsWrapper.id = 'save-actions-area';
        saveActionsWrapper.innerHTML = `
            <div class="save-status">
                <span id="cloud-status-icon">${cloudIcon}</span>
            </div>
            <div class="save-button-group">
                <button class="hero-btn secondary save-btn" id="backup-save-btn">
                    <i class="fas fa-cloud-upload-alt"></i> Buluta Yedekle
                </button>
                <button class="hero-btn secondary save-btn" id="restore-save-btn" ${!hasCloudSave ? 'disabled' : ''}>
                    <i class="fas fa-cloud-download-alt"></i> Geri Yükle
                </button>
            </div>
        `;

        // Bu yeni wrapper'ı ana buton konteynerine ekle
        container.appendChild(saveActionsWrapper);

        saveActionsWrapper.querySelector('#backup-save-btn').onclick = () => handleSaveAction(game, 'backup');
        saveActionsWrapper.querySelector('#restore-save-btn').onclick = () => handleSaveAction(game, 'restore');
    };

    const handleSaveAction = async (game, action) => {
        const button = document.getElementById(action === 'backup' ? 'backup-save-btn' : 'restore-save-btn');
        const originalText = button.innerHTML;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> İşleniyor...`;
        button.disabled = true;

        try {
            if (action === 'backup') {
                console.log(`'${game.oyun_adi}' için manuel yedekleme başlatıldı...`);
                const result = await window.electronAPI.zipSave(game.save_yolu);
                if (!result.success) throw new Error(result.error);

                const formData = new FormData();
                formData.append('save_file', new Blob([result.data]), `${game.id}.zip`);

                const response = await fetch(`/api/user/save/${game.id}`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${authToken}` },
                    body: formData
                });
                if (!response.ok) throw new Error('Sunucuya yüklenemedi.');
                
                userSaves.add(game.id); // Set'e ekle
                alert(`'${game.oyun_adi}' kayıt dosyaları başarıyla buluta yedeklendi!`);

            } else if (action === 'restore') {
                console.log(`'${game.oyun_adi}' için manuel geri yükleme başlatıldı...`);
                const response = await fetch(`/api/user/save/${game.id}`, { headers: { 'Authorization': `Bearer ${authToken}` } });
                if (!response.ok) throw new Error('Sunucudan yedek indirilemedi.');

                const saveDataBuffer = await response.arrayBuffer();
                await window.electronAPI.unzipSave({ saveDataBuffer, savePath: game.save_yolu });
                alert(`'${game.oyun_adi}' kayıt dosyaları başarıyla geri yüklendi!`);
            }
        } catch (error) {
            console.error(`Save '${action}' hatası:`, error);
            alert(`İşlem başarısız oldu: ${error.message}`);
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
            renderSaveButtons(game, cloudActionsContainer); // Buton durumlarını ve ikonu güncelle
        }
    };

    const updateRatingDisplay = (game) => {
        const avgRating = game.average_rating ? game.average_rating.toFixed(1) : 'N/A';
        if (averageRatingSummary) averageRatingSummary.textContent = `Ortalama Puan: ${avgRating} (${game.rating_count || 0} oy)`;
        if (userRatingInner) userRatingInner.style.width = `${((userRatings[game.id] || 0) / 5) * 100}%`;
    };

    const updateFavoriteDisplay = (gameId) => {
        if (favoriteButton) {
            favoriteButton.innerHTML = `<i class="fas fa-heart"></i>`;
            favoriteButton.classList.toggle('is-favorite', userFavorites.has(gameId));
        }
    };

    const updateSectionTitle = (filter) => {
        const titles = {'all': 'Öne Çıkan Oyunlar', 'newest': 'Yeni Eklenen Oyunlar', 'favorites': 'Favori Oyunlarım', 'recent': 'Son Oynanan Oyunlar', 'discover': 'Tüm Oyunları Keşfet'};
        const sectionHeader = document.querySelector('.section-header');
        if (filter === 'discover') {
            sectionHeader.classList.add('discover-header');
            sectionHeader.innerHTML = `
                <h2 class="section-title">Tüm Oyunları Keşfet</h2>
                <div class="sort-actions">
                    <button class="sort-btn ${currentSort === 'newest' ? 'active' : ''}" data-sort="newest">En Yeni</button>
                    <button class="sort-btn ${currentSort === 'popular' ? 'active' : ''}" data-sort="popular">Popüler</button>
                    <button class="sort-btn ${currentSort === 'name' ? 'active' : ''}" data-sort="name">A-Z</button>
                    <button class="sort-btn ${currentSort === 'rating' ? 'active' : ''}" data-sort="rating">En Yüksek Puan</button>
                </div>`;
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelector('.sort-btn.active')?.classList.remove('active');
                    this.classList.add('active');
                    currentSort = this.dataset.sort;
                    renderGames('discover', searchInput.value);
                });
            });
        } else {
            sectionHeader.classList.remove('discover-header');
            sectionHeader.innerHTML = `<h2 class="section-title" id="sectionTitle"></h2><span class="view-all">Tümünü Gör →</span>`;
            document.getElementById('sectionTitle').textContent = titles[filter] || `${filter} Oyunları`;
            addViewAllListener();
        }
    };

    const fetchGameAndCategories = () => {
        Promise.all([
            fetch(`/api/games`).then(res => res.json()),
            fetch(`/api/categories`).then(res => res.json())
        ]).then(([games, categories]) => {
            allGames = games.map(g => ({ ...g, id: Number(g.id) }));
            allCategories = categories;
            const gameCountSection = document.getElementById('game-count-section');
            if (gameCountSection) gameCountSection.innerHTML = `<div class="game-count-value">${allGames.length}</div><div class="game-count-label">Toplam Oyun</div>`;
            renderCategories();
            addViewAllListener();
            renderGames(currentFilter);
            updateHeroSection();
        }).catch(error => console.error('Veri çekme hatası:', error));
    };
    
    const updateHeroSection = () => {
        fetch(`/api/slider`).then(res => res.json()).then(sliderData => {
            if (!sliderData || sliderData.length === 0) { heroSection.style.display = 'none'; return; }
            heroSection.style.display = 'block';
            heroSection.innerHTML = `<div class="slider-nav prev" id="slider-prev"><i class="fas fa-chevron-left"></i></div><div class="slider-nav next" id="slider-next"><i class="fas fa-chevron-right"></i></div>`;
            sliderData.forEach((slide, index) => {
                const slideEl = document.createElement('div');
                slideEl.className = 'hero-slide';
                if (index === 0) slideEl.classList.add('active');
                slideEl.innerHTML = `
                    <img src="/admin_static/images/slider/${slide.background_image}" class="hero-bg" alt="Featured">
                    <div class="hero-overlay"></div>
                    <div class="hero-content" style="max-width: 720px;">
                        <div class="hero-badge">${slide.badge_text}</div>
                        <h1 class="hero-title">${slide.title}</h1>
                        <p class="hero-description">${slide.description}</p>
                        <div class="hero-actions">
                            <button class="hero-btn primary" data-game-id="${slide.game_id}">▶ Şimdi Oyna</button>
                            <button class="hero-btn secondary" data-game-id="${slide.game_id}">ℹ️ Detaylar</button>
                        </div>
                    </div>`;
                heroSection.insertBefore(slideEl, document.getElementById('slider-prev'));
            });
            document.querySelectorAll('.hero-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const game = allGames.find(g => g.id == this.dataset.gameId);
                    if (game) this.classList.contains('primary') ? syncAndLaunch(game) : showGameDetail(game);
                });
            });
            startSlider();
        }).catch(error => { console.error('Slider verisi alınırken hata:', error); heroSection.style.display = 'none'; });
    };

    function showSlide(index) { slides.forEach((s, i) => s.classList.toggle('active', i === index)); currentSlide = index; }
    function nextSlide() { if (slides.length > 0) showSlide((currentSlide + 1) % slides.length); }
    function prevSlide() { if (slides.length > 0) showSlide((currentSlide - 1 + slides.length) % slides.length); }
    function startSlider() {
        slides = document.querySelectorAll('.hero-slide');
        const prevBtn = document.getElementById('slider-prev'), nextBtn = document.getElementById('slider-next');
        if (slides.length <= 1) { if(prevBtn) prevBtn.style.display = 'none'; if(nextBtn) nextBtn.style.display = 'none'; return; }
        clearInterval(sliderInterval);
        sliderInterval = setInterval(nextSlide, 7000);
        const manualSlide = (func) => { func(); clearInterval(sliderInterval); sliderInterval = setInterval(nextSlide, 7000); };
        if(prevBtn) prevBtn.onclick = () => manualSlide(prevSlide);
        if(nextBtn) nextBtn.onclick = () => manualSlide(nextSlide);
    }

    const renderCategories = () => {
        categoryListSidebar.innerHTML = '';
        allCategories.forEach(cat => {
            categoryListSidebar.innerHTML += `<div class="nav-item" data-category="${cat.name}"><span class="nav-icon">${cat.icon || '🎮'}</span> ${cat.name}</div>`;
        });
        addCategoryEventListeners();
    };

    const addCategoryEventListeners = () => {
        document.querySelectorAll('.nav-item[data-category]').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item.active').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.category; 
                if (currentFilter === 'all' || currentFilter === 'discover' || currentFilter === 'recent') { 
                    currentSort = 'newest'; 
                    searchInput.value = ''; 
                }
                renderGames(currentFilter, searchInput.value);
            });
        });
    };
    
    const addViewAllListener = () => {
        const btn = document.querySelector('.view-all'); 
        if (btn) {
            btn.addEventListener('click', function() {
                currentFilter = 'discover'; currentSort = 'newest'; searchInput.value = ''; 
                document.querySelectorAll('.nav-item.active').forEach(i => i.classList.remove('active'));
                document.querySelector('.nav-item[data-category="all"]')?.classList.add('active'); 
                renderGames(currentFilter, ''); 
            });
        }
    };

    const updateUserUI = () => {
        if (authToken) {
            userActions.innerHTML = `<button class="action-btn">Kayıtlı Oyunlar</button><button class="action-btn primary" id="logout-button">Çıkış Yap</button>`;
            document.getElementById('logout-button').addEventListener('click', handleLogout);
        } else {
            userActions.innerHTML = `<button class="action-btn primary" id="login-show-button">Giriş Yap / Kayıt ol</button>`;
            document.getElementById('login-show-button').addEventListener('click', openLoginModal);
        }
    };
    
    const handleLogout = () => {
        authToken = null; currentUser = null; 
        userFavorites.clear();
        userRatings = {};
        userRecentlyPlayed = [];
        updateUserUI();
        if (currentFilter === 'favorites' || currentFilter === 'recent') {
            document.querySelector('.nav-item[data-category="all"]').click();
        } else {
            renderGames(currentFilter, searchInput.value);
        }
    };
    
    const openLoginModal = () => { setModalMode('login'); if (loginModal) loginModal.style.display = 'flex'; document.getElementById('username')?.focus(); };
    const closeLoginModal = () => { if (loginModal) loginModal.style.display = 'none'; };
    
    const setModalMode = (mode) => {
        if (loginForm && loginError && modalTitle && modalButton && modalSwitchToRegister && modalSwitchToLogin) {
            loginForm.reset(); loginError.textContent = ''; loginForm.dataset.mode = mode;
            modalTitle.textContent = mode === 'login' ? 'Giriş Yap' : 'Kayıt Ol';
            modalButton.textContent = mode === 'login' ? 'Giriş' : 'Kayıt Ol';
            modalSwitchToRegister.classList.toggle('hidden', mode !== 'login');
            modalSwitchToLogin.classList.toggle('hidden', mode === 'login');
        }
    };
    
    const handleLogin = (event) => {
        event.preventDefault();
        const { mode } = loginForm.dataset;
        const username = document.getElementById('username').value, password = document.getElementById('password').value;
        fetch((mode === 'login' ? '/api/login' : '/api/register'), {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username, password })
        }).then(async res => { const data = await res.json(); if (!res.ok) throw new Error(data.mesaj); return data; })
        .then(data => {
            if (mode === 'login') {
                authToken = data.token; currentUser = username; closeLoginModal(); updateUserUI(); fetchUserSpecificData();
            } else {
                if (loginError) loginError.textContent = data.mesaj;
                setTimeout(() => setModalMode('login'), 2000);
            }
        }).catch(error => { if (loginError) loginError.textContent = error.message; });
    };
    
    const fetchUserSpecificData = () => {
        if (!authToken) return;
        const headers = { 'Authorization': `Bearer ${authToken}` };
        Promise.all([
            fetch(`/api/user/favorites`, { headers }).then(res => res.json()),
            fetch(`/api/user/ratings`, { headers }).then(res => res.json()),
            fetch(`/api/user/recently_played`, { headers }).then(res => res.json()),
            fetch(`/api/user/saves`, { headers }).then(res => res.json()) // YENİ: Kullanıcının save'lerini çek
        ]).then(([favorites, ratings, recentlyPlayed, saves]) => {
            userFavorites = new Set(favorites); 
            userRatings = ratings;
            userRecentlyPlayed = recentlyPlayed;
            userSaves = new Set(saves); // YENİ: Save listesini set'e ata
            renderGames(currentFilter, searchInput.value);
        });
    };

    // 3. Adım: Tüm adımları birleştiren ana fonksiyon
    const syncAndLaunch = async (game) => { 
        // Tıklama ve oynanma sayısını güncelle
        try { await fetch(`/api/games/${game.id}/click`, { method: 'POST' }); }
        catch (err) { console.error(`Tıklama kaydedilemedi: ${err}`); }
        
        if (authToken) {
            try {
                await fetch(`/api/games/${game.id}/played`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${authToken}` }
                });
                userRecentlyPlayed = userRecentlyPlayed.filter(g => g.id !== game.id);
                userRecentlyPlayed.unshift(game);
                if (currentFilter === 'recent') {
                    renderGames('recent');
                }
            } catch (err) {
                console.error(`Oynanma kaydı gönderilemedi: ${err}`);
            }
        }
        
        // Son oynanan oyunu güncelle
        lastPlayedGame = game;

        if (window.electronAPI) {
            window.electronAPI.launchGame(game); 
        } else {
            console.log("Electron API bulunamadı, oyun başlatılamıyor.");
        }
    };

    searchInput.addEventListener('input', (e) => renderGames(currentFilter, e.target.value));
    gamesGrid.addEventListener('click', (e) => {
        const card = e.target.closest('.game-card');
        if (card) { const game = allGames.find(g => g.id == card.dataset.gameId); if (game) showGameDetail(game); }
    });
    if (similarGamesGrid) similarGamesGrid.addEventListener('click', (e) => {
        const card = e.target.closest('.game-card.similar');
        if(card){ const game = allGames.find(g => g.id == card.dataset.gameId); if(game) { closeGameDetail(); setTimeout(() => showGameDetail(game), 100); } }
    });
    document.querySelectorAll('.close-button').forEach(btn => btn.addEventListener('click', () => { closeLoginModal(); closeGameDetail(); closeWelcomeModal(); }));
    modalSwitchToRegister?.querySelector('a').addEventListener('click', (e) => { e.preventDefault(); setModalMode('register'); });
    modalSwitchToLogin?.querySelector('a').addEventListener('click', (e) => { e.preventDefault(); setModalMode('login'); });
    loginForm?.addEventListener('submit', handleLogin);
    if (userRatingStars && userRatingInner) {
        userRatingStars.addEventListener('mousemove', e => {
            if (!authToken) return;
            const rect = userRatingStars.getBoundingClientRect();
            const rating = Math.max(0.5, Math.ceil(((e.clientX - rect.left) / rect.width) * 10) / 2);
            if (userRatingInner) userRatingInner.style.width = `${(rating / 5) * 100}%`;
        });
        userRatingStars.addEventListener('mouseleave', () => {
            const gameId = primaryActionsContainer.querySelector('.hero-btn.primary')?.dataset.gameId;
            if (gameId) { const game = allGames.find(g => g.id == gameId); if (game) updateRatingDisplay(game); }
        });
        userRatingStars.addEventListener('click', async e => {
            if (!authToken) { closeGameDetail(); openLoginModal(); return; }
            const gameId = primaryActionsContainer.querySelector('.hero-btn.primary')?.dataset.gameId;
            if(!gameId) { console.warn("Puanlama için gameId bulunamadı."); return; }
            const rect = userRatingStars.getBoundingClientRect();
            const rating = Math.max(0.5, Math.ceil(((e.clientX - rect.left) / rect.width) * 10) / 2);
            try {
                const res = await fetch(`/api/games/${gameId}/rate`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authToken}` }, body: JSON.stringify({ rating })
                });
                const result = await res.json();
                if (!res.ok) throw new Error(result.mesaj);
                userRatings[gameId] = rating;
                const game = allGames.find(g => g.id == gameId);
                game.average_rating = result.average_rating; game.rating_count = result.rating_count;
                updateRatingDisplay(game);
            } catch (error) { console.error(`Puan kaydedilirken hata: ${error.message}`); }
        });
    }
    if (favoriteButton) favoriteButton.addEventListener('click', async () => {
        if (!authToken) { closeGameDetail(); openLoginModal(); return; }
        const gameId = parseInt(primaryActionsContainer.querySelector('.hero-btn.primary')?.dataset.gameId, 10);
        if(!gameId) { console.warn("Favori işlemi için gameId bulunamadı."); return; }
        try {
            const res = await fetch(`/api/games/${gameId}/favorite`, { method: 'POST', headers: { 'Authorization': `Bearer ${authToken}` } });
            const result = await res.json();
            if (!res.ok) throw new Error(result.mesaj);
            result.is_favorite ? userFavorites.add(gameId) : userFavorites.delete(gameId);
            updateFavoriteDisplay(gameId);
            if (currentFilter === 'favorites') renderGames('favorites'); // Favoriler listesini anında güncelle
        } catch (error) { console.error(`Favori hatası: ${error.message}`); }
    });
    welcomeLoginBtn?.addEventListener('click', () => { closeWelcomeModal(); openLoginModal(); setModalMode('login'); });
    welcomeRegisterBtn?.addEventListener('click', () => { closeWelcomeModal(); openLoginModal(); setModalMode('register'); });
    window.addEventListener('click', (e) => { if (e.target == loginModal || e.target == gameDetailModal || e.target == welcomeModal) { closeLoginModal(); closeGameDetail(); closeWelcomeModal(); } });

    fetchAndApplySettings();
    fetchGameAndCategories();
    updateUserUI();
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}