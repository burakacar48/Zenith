
        </div> <!-- admin-wrapper -->
    </div> <!-- admin-container -->
    
    <!-- Footer Section -->
    <footer class="admin-footer">
        <div class="admin-wrapper">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="footer-brand">
                        <i data-lucide="shield-check" class="w-5 h-5 text-red-500"></i>
                        <span class="font-semibold">Zenith License Panel</span>
                    </div>
                    <p class="footer-text">
                        Gelişmiş lisans yönetim sistemi v2.0
                    </p>
                </div>
                
                <div class="footer-center">
                    <div class="footer-stats">
                        <div class="footer-stat">
                            <i data-lucide="server" class="w-4 h-4"></i>
                            <span>Sistem Çalışıyor</span>
                        </div>
                        <div class="footer-stat">
                            <i data-lucide="wifi" class="w-4 h-4"></i>
                            <span>Bağlantı Aktif</span>
                        </div>
                        <div class="footer-stat" id="system-uptime">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <span>Uptime: Hesaplanıyor...</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-right">
                    <div class="footer-actions">
                        <button onclick="showSystemInfo()" class="footer-btn" title="Sistem bilgilerini görüntüle">
                            <i data-lucide="info" class="w-4 h-4"></i>
                        </button>
                        <button onclick="showHelp()" class="footer-btn" title="Yardım ve dokümantasyon">
                            <i data-lucide="help-circle" class="w-4 h-4"></i>
                        </button>
                        <button onclick="toggleTheme()" class="footer-btn" title="Tema değiştir">
                            <i data-lucide="palette" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="footer-copyright">
                        <span>&copy; <?php echo date('Y'); ?> Zenith Development</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- System Information Modal -->
    <div id="systemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sistem Bilgileri</h3>
                <button onclick="closeModal('systemModal')" class="modal-close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="system-info-grid">
                    <div class="system-info-item">
                        <strong>PHP Sürümü:</strong>
                        <span><?php echo phpversion(); ?></span>
                    </div>
                    <div class="system-info-item">
                        <strong>Sunucu:</strong>
                        <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></span>
                    </div>
                    <div class="system-info-item">
                        <strong>Veritabanı:</strong>
                        <span>MySQL <?php echo $mysqli->server_info ?? 'Bağlantı yok'; ?></span>
                    </div>
                    <div class="system-info-item">
                        <strong>Zaman Dilimi:</strong>
                        <span><?php echo date_default_timezone_get(); ?></span>
                    </div>
                    <div class="system-info-item">
                        <strong>Bellek Kullanımı:</strong>
                        <span><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
                    </div>
                    <div class="system-info-item">
                        <strong>Oturum Süresi:</strong>
                        <span id="session-duration">Hesaplanıyor...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yardım ve Kılavuz</h3>
                <button onclick="closeModal('helpModal')" class="modal-close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="help-sections">
                    <div class="help-section">
                        <h4><i data-lucide="users" class="w-4 h-4"></i> Müşteri Yönetimi</h4>
                        <ul>
                            <li>Yeni müşteri eklemek için "Yeni Müşteri" butonunu kullanın</li>
                            <li>Arama çubuğu ile müşteri adı, email veya telefon ile arama yapabilirsiniz</li>
                            <li>Durum filtrelerini kullanarak lisansları kategorize edebilirsiniz</li>
                        </ul>
                    </div>
                    <div class="help-section">
                        <h4><i data-lucide="key" class="w-4 h-4"></i> Lisans Yönetimi</h4>
                        <ul>
                            <li>Her lisansın benzersiz bir HWID'si vardır</li>
                            <li>Lisans süresi dolmadan önce uyarı bildirimleri alırsınız</li>
                            <li>Lisansları manuel olarak yenileyebilir veya iptal edebilirsiniz</li>
                        </ul>
                    </div>
                    <div class="help-section">
                        <h4><i data-lucide="shield" class="w-4 h-4"></i> Güvenlik</h4>
                        <ul>
                            <li>Tüm admin aktiviteleri loglanır</li>
                            <li>Güçlü şifre kullanın ve düzenli olarak değiştirin</li>
                            <li>Sistem otomatik olarak güvenlik kontrolü yapar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Footer Styles */
        .admin-footer {
            margin-top: 3rem;
            padding: 2rem 1.5rem;
            background: var(--gradient-card);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-primary);
            position: relative;
        }
        
        .admin-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-primary);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 2rem;
        }
        
        .footer-left {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .footer-text {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .footer-center {
            display: flex;
            justify-content: center;
        }
        
        .footer-stats {
            display: flex;
            gap: 2rem;
        }
        
        .footer-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.75rem;
        }
        
        .footer-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .footer-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .footer-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            color: var(--text-secondary);
            transition: all var(--transition-normal);
            cursor: pointer;
        }
        
        .footer-btn:hover {
            background: var(--border-primary);
            color: var(--text-primary);
            transform: translateY(-1px);
        }
        
        .footer-copyright {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--gradient-card);
            border: 1px solid var(--border-primary);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-primary);
        }
        
        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .modal-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-normal);
        }
        
        .modal-close:hover {
            background: var(--error-bg);
            color: var(--error);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .system-info-grid {
            display: grid;
            gap: 1rem;
        }
        
        .system-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--bg-tertiary);
            border-radius: 8px;
            border: 1px solid var(--border-primary);
        }
        
        .help-sections {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .help-section h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .help-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .help-section li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-primary);
        }
        
        .help-section li:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1rem;
            }
            
            .footer-right {
                align-items: center;
            }
            
            .footer-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
    
    <script>
        // Footer functionality
        function showSystemInfo() {
            document.getElementById('systemModal').classList.add('show');
            updateSessionDuration();
        }
        
        function showHelp() {
            document.getElementById('helpModal').classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function toggleTheme() {
            // Theme toggle functionality
            alert('Tema değiştirme özelliği yakında eklenecek!');
        }
        
        function updateSessionDuration() {
            const sessionStart = new Date().getTime() - (<?php echo time() - ($_SESSION['login_time'] ?? time()); ?> * 1000);
            const now = new Date().getTime();
            const duration = now - sessionStart;
            
            const hours = Math.floor(duration / (1000 * 60 * 60));
            const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
            
            document.getElementById('session-duration').textContent = `${hours}s ${minutes}d`;
        }
        
        function updateSystemUptime() {
            // Simple uptime calculation (you can enhance this)
            const uptimeElement = document.getElementById('system-uptime');
            if (uptimeElement) {
                const uptime = new Date().toLocaleTimeString('tr-TR');
                uptimeElement.innerHTML = `<i data-lucide="clock" class="w-4 h-4"></i><span>Son güncelleme: ${uptime}</span>`;
            }
        }
        
        // Modal close on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
        
        // Keyboard shortcuts for modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
        
        // Update uptime every minute
        setInterval(updateSystemUptime, 60000);
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSystemUptime();
            
            // Re-initialize Lucide icons for footer
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>