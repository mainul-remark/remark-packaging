<script>
    (function () {

        const body = document.body;
        const toggleBtn = document.getElementById('themeToggleBtn');

        const topbar = document.querySelector('.topbar');
        const topNavbar = document.querySelector('.top-navbar');
        const navbarContent = document.getElementById('navbarSupportedContent');
        const navbarHeader = document.querySelector('.navbar-header');
        const sidebar = document.querySelector('.left-sidebar');

        let savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();

                let currentTheme = body.getAttribute('data-theme') || 'light';
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }

        function applyTheme(theme) {
            body.setAttribute('data-theme', theme);
            updateThemeIcon(theme);

            if (theme === 'dark') {
                setDarkHeader();
            } else {
                setLightHeader();
            }
        }

        function setDarkHeader() {

            if (topbar) {
                topbar.style.backgroundColor = '';
            }

            if (topNavbar) {
                topNavbar.style.backgroundColor = '';
            }

            if (navbarContent) {
                navbarContent.style.backgroundColor = '';
                navbarContent.setAttribute('data-navbarbg', 'skin5');
            }

            if (navbarHeader) {
                navbarHeader.style.backgroundColor = '';
                navbarHeader.setAttribute('data-logobg', 'skin5');
            }

            if (sidebar) {
                sidebar.style.backgroundColor = '';
            }
        }

        function setLightHeader() {

            if (topbar) {
                topbar.style.backgroundColor = '';
            }

            if (topNavbar) {
                topNavbar.style.backgroundColor = '';
            }

            if (navbarContent) {
                navbarContent.style.backgroundColor = '';
                navbarContent.setAttribute('data-navbarbg', 'skin1');
            }

            if (navbarHeader) {
                navbarHeader.style.backgroundColor = '';
                navbarHeader.setAttribute('data-logobg', 'skin5');
            }

            if (sidebar) {
                sidebar.style.backgroundColor = '';
            }
        }

        function updateThemeIcon(theme) {

            const icon = document.getElementById('themeToggleIcon');
            if (!icon) return;

            if (theme === 'dark') {

                // sun icon
                icon.className = 'mdi mdi-white-balance-sunny font-22 text-warning';

            } else {

                // premium moon icon
                icon.className = 'mdi mdi-brightness-6 font-22';

            }
        }
        document.querySelector('.navbar-brand').classList.add('border-white');

    })();

</script>
