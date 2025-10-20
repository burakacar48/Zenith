        </div>
    </main>
    </div><!-- End app-container -->

    <script>
        lucide.createIcons();

        const menuBtn = document.getElementById('menuBtn');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        if(menuBtn) {
            menuBtn.addEventListener('click', () => {
                overlay.classList.remove('hidden');
            });
        }

        if(closeSidebar) {
            closeSidebar.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
            });
        }

        if(overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                });
        }

        // License blur toggle functionality
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('license-blur')) {
                e.target.classList.toggle('revealed');
            }
        });

        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');

        if(themeToggle && sunIcon && moonIcon) {
            // Check for saved theme preference or default to 'light'
            const currentTheme = localStorage.getItem('theme') || 'light';
            html.classList.add(currentTheme);

            if (currentTheme === 'dark') {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }

            themeToggle.addEventListener('click', () => {
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    html.classList.add('light');
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.remove('light');
                    html.classList.add('dark');
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                    localStorage.setItem('theme', 'dark');
                }
                
                // Recreate icons after theme change
                setTimeout(() => lucide.createIcons(), 10);
            });
        }
    </script>
</body>
</html>
