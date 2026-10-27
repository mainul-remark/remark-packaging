<style>
    :root {
        /* Project Gradient */
        --project-color-start: #C92E78;
        --project-color-end: #4449E9;

        --project-gradient: linear-gradient(
            135deg,
            #C92E78 0%,
            #8B45C1 50%,
            #4449E9 100%
        );

        --project-primary: var(--project-color-start);
        --project-secondary: var(--project-color-end);

        /* Sidebar */
        --sidebar-bg: #24204A;

        --sidebar-overlay: rgba(255, 255, 255, 0.08);
        --sidebar-active: rgba(255, 255, 255, 0.15);
        --sidebar-text: rgba(255, 255, 255, 0.85);
        --sidebar-muted: rgba(255, 255, 255, 0.65);
    }


    /* =========================================================
       LIGHT THEME
    ========================================================= */

    body[data-theme="light"] {
        background-color: #ffffff;
        color: #5f6b7a;
    }

    body[data-theme="light"] .page-wrapper {
        background: #f4f6f9 !important;
    }


    /* =========================================================
       DARK THEME - BODY
    ========================================================= */

    body[data-theme="dark"] {
        background-color: #2c3e50 !important;
        color: #b2b9bf !important;
    }

    body[data-theme="dark"] .page-wrapper {
        background: #33485f !important;
    }


    /* =========================================================
       HEADER - FULL GRADIENT
    ========================================================= */

    .app-header,
    .topbar,
    .top-navbar,
    .navbar-header,
    #navbarSupportedContent {
        background: var(--project-gradient) !important;
    }


    /* Make all header theme states transparent
       so parent .topbar gradient remains visible */

    body[data-theme="light"] .topbar,
    body[data-theme="dark"] .topbar {
        background: var(--project-gradient) !important;
    }

    body[data-theme="light"] .top-navbar,
    body[data-theme="dark"] .top-navbar {
        background: transparent !important;
    }

    body[data-theme="light"] .navbar-header,
    body[data-theme="dark"] .navbar-header {
        background: transparent !important;
    }

    body[data-theme="light"] #navbarSupportedContent,
    body[data-theme="dark"] #navbarSupportedContent {
        background: transparent !important;
    }


    /* =========================================================
       HEADER BORDER
    ========================================================= */

    .topbar {
        border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important;
    }


    /* =========================================================
       LOGO AREA
    ========================================================= */

    .navbar-brand {
        background: var(--sidebar-bg) !important;
        color: #ffffff !important;
    }

    body[data-theme="light"] .navbar-brand,
    body[data-theme="dark"] .navbar-brand {
        background: var(--sidebar-bg) !important;
    }

    .logo-border {
        background: var(--sidebar-bg) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    body[data-theme="light"] .logo-border,
    body[data-theme="dark"] .logo-border {
        background: var(--sidebar-bg) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
    }


    /* =========================================================
       HEADER NAVIGATION
    ========================================================= */

    .topbar .nav-link,
    .topbar .nav-link i,
    .navbar-nav .nav-link,
    .navbar-dark .navbar-nav .nav-link {
        color: #ffffff !important;
    }

    .topbar .nav-link:hover,
    .topbar .nav-link:focus {
        color: #ffffff !important;
        opacity: 0.85;
    }


    /* =========================================================
       THEME TOGGLE ICON
    ========================================================= */

    #themeToggleBtn {
        color: #ffffff !important;
    }

    #themeToggleBtn i {
        color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR - DARK NAVY
    ========================================================= */

    .left-sidebar {
        background: var(--sidebar-bg) !important;
    }


    /* All sidebar wrappers transparent */

    .left-sidebar .scroll-sidebar,
    .left-sidebar .sidebar-nav,
    .left-sidebar .sidebar-nav > ul,
    .left-sidebar .sidebar-nav ul,
    .left-sidebar .sidebar-nav .collapse,
    .left-sidebar .sidebar-nav .first-level,
    .left-sidebar .sidebar-nav .second-level {
        background: transparent !important;
    }


    /* Important for template generated pseudo/background */

    .left-sidebar::before,
    .left-sidebar::after,
    .left-sidebar .scroll-sidebar::before,
    .left-sidebar .scroll-sidebar::after {
        background: transparent !important;
    }


    /* =========================================================
       SIDEBAR MENU ITEM
    ========================================================= */

    .left-sidebar .sidebar-nav ul .sidebar-item {
        background: transparent !important;
    }

    .left-sidebar .sidebar-nav ul .sidebar-item .sidebar-link {
        background: transparent !important;
        color: var(--sidebar-text) !important;
    }


    /* Sidebar icons */

    .left-sidebar .sidebar-nav ul .sidebar-item .sidebar-link i {
        color: var(--sidebar-text) !important;
    }


    /* Sidebar text */

    .left-sidebar .sidebar-nav ul .sidebar-item .sidebar-link .hide-menu {
        color: var(--sidebar-text) !important;
    }


    /* =========================================================
       SIDEBAR MENU HOVER
    ========================================================= */

    .left-sidebar .sidebar-nav ul .sidebar-item > .sidebar-link:hover {
        background: var(--sidebar-overlay) !important;
        color: #ffffff !important;
    }

    .left-sidebar .sidebar-nav ul .sidebar-item > .sidebar-link:hover i,
    .left-sidebar .sidebar-nav ul .sidebar-item > .sidebar-link:hover .hide-menu {
        color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR PARENT ACTIVE / SELECTED
       ---------------------------------------------------------
       Active menu = Project Gradient
    ========================================================= */

    .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link,
    .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link {
        background: var(--project-gradient) !important;
        color: #ffffff !important;

        border-left: 0 !important;
        border-radius: 0 30px 30px 0 !important;
    }


    /* Parent selected icon */

    .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link i,
    .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link i {
        color: #ffffff !important;
    }


    /* Parent selected text */

    .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link .hide-menu,
    .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link .hide-menu {
        color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR ARROW
    ========================================================= */

    .left-sidebar .sidebar-nav .has-arrow:after {
        border-color: rgba(255, 255, 255, 0.75) !important;
    }

    .left-sidebar .sidebar-nav .sidebar-item.selected > .has-arrow:after,
    .left-sidebar .sidebar-nav .sidebar-item.active > .has-arrow:after {
        border-color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR CHILD MENU
    ========================================================= */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item {
        background: transparent !important;
    }

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link {
        background: transparent !important;
        color: var(--sidebar-muted) !important;

        border-left: 0 !important;
    }


    /* Child icon */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link i {
        color: var(--sidebar-muted) !important;
    }


    /* Child text */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link .hide-menu {
        color: var(--sidebar-muted) !important;
    }


    /* =========================================================
       SIDEBAR CHILD HOVER
    ========================================================= */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.10) !important;
        color: #ffffff !important;
    }

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link:hover i,
    .left-sidebar .sidebar-nav ul .first-level .sidebar-item .sidebar-link:hover .hide-menu {
        color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR CHILD ACTIVE / SELECTED
    ========================================================= */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.active > .sidebar-link,
    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.selected > .sidebar-link {
        background: rgba(255, 255, 255, 0.15) !important;

        color: #ffffff !important;

        border-left: 3px solid #ffffff !important;

        border-radius: 0 !important;
    }


    /* Child active icon */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.active > .sidebar-link i,
    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.selected > .sidebar-link i {
        color: #ffffff !important;
    }


    /* Child active text */

    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.active > .sidebar-link .hide-menu,
    .left-sidebar .sidebar-nav ul .first-level .sidebar-item.selected > .sidebar-link .hide-menu {
        color: #ffffff !important;
    }


    /* =========================================================
       SIDEBAR LOGOUT
    ========================================================= */

    .left-sidebar .sidebar-nav .sidebar-item:last-child > .sidebar-link {
        background: transparent !important;
        color: var(--sidebar-text) !important;
    }

    .left-sidebar .sidebar-nav .sidebar-item:last-child > .sidebar-link i {
        color: var(--sidebar-text) !important;
    }

    .left-sidebar .sidebar-nav .sidebar-item:last-child > .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }


    /* =========================================================
       DARK THEME - CARD
    ========================================================= */

    body[data-theme="dark"] .card {
        background-color: #243242 !important;
        border: 1px solid #415466 !important;
    }

    body[data-theme="dark"] .card-header {
        background-color: #243242 !important;
        border-bottom: 1px solid #415466 !important;
        color: #d7dde3 !important;
    }

    body[data-theme="dark"] .card-body {
        background-color: #243242 !important;
        color: #b2b9bf !important;
    }


    /* =========================================================
       DARK TABLE BORDER
    ========================================================= */

    body[data-theme="dark"] .table-bordered th,
    body[data-theme="dark"] .table-bordered td {
        border-color: #3b4f63 !important;
    }


    /* =========================================================
       DARK DATATABLE
    ========================================================= */

    body[data-theme="dark"] .custom-table,
    body[data-theme="dark"] .dataTables_wrapper,
    body[data-theme="dark"] div.dataTables_wrapper div.dataTables_filter input,
    body[data-theme="dark"] div.dataTables_wrapper div.dataTables_length select {
        background-color: #243242 !important;
        color: #b2b9bf !important;
        border-color: #3a4b5c !important;
    }

    body[data-theme="dark"] .table,
    body[data-theme="dark"] table.dataTable tbody tr,
    body[data-theme="dark"] table.dataTable thead th,
    body[data-theme="dark"] table.dataTable tbody td {
        background-color: #243242 !important;
        color: #b2b9bf !important;
        border-color: #314355 !important;
    }

    body[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
        background-color: #22303f !important;
    }


    /* =========================================================
       DARK FORM CONTROL
    ========================================================= */

    body[data-theme="dark"] .form-control,
    body[data-theme="dark"] .custom-select,
    body[data-theme="dark"] input,
    body[data-theme="dark"] select,
    body[data-theme="dark"] textarea {
        background-color: #243242 !important;
        color: #d7dde3 !important;
        border: 1px solid #415466 !important;
    }

    body[data-theme="dark"] .form-control:focus,
    body[data-theme="dark"] .custom-select:focus,
    body[data-theme="dark"] input:focus,
    body[data-theme="dark"] select:focus,
    body[data-theme="dark"] textarea:focus {
        background-color: #243242 !important;
        color: #ffffff !important;

        border-color: var(--project-color-start) !important;

        box-shadow: none !important;
    }


    /* =========================================================
       DARK TEXT
    ========================================================= */

    body[data-theme="dark"] .page-breadcrumb,
    body[data-theme="dark"] .breadcrumb,
    body[data-theme="dark"] .breadcrumb-item,
    body[data-theme="dark"] .breadcrumb-item a,
    body[data-theme="dark"] h1,
    body[data-theme="dark"] h2,
    body[data-theme="dark"] h3,
    body[data-theme="dark"] h4,
    body[data-theme="dark"] h5,
    body[data-theme="dark"] h6,
    body[data-theme="dark"] label,
    body[data-theme="dark"] p,
    body[data-theme="dark"] span,
    body[data-theme="dark"] td,
    body[data-theme="dark"] th {
        color: #bfc8d0 !important;
    }


    /* =========================================================
       DATATABLE INFO / PAGINATION TEXT
    ========================================================= */

    body[data-theme="dark"] .dataTables_info,
    body[data-theme="dark"] .dataTables_paginate,
    body[data-theme="dark"] .paginate_button,
    body[data-theme="dark"] .dataTables_length label,
    body[data-theme="dark"] .dataTables_filter label {
        color: #b2b9bf !important;
    }


    /* =========================================================
       PAGINATION ACTIVE - GRADIENT
    ========================================================= */

    .paginate_button.current,
    .page-item.active .page-link {
        background: var(--project-gradient) !important;
        border-color: transparent !important;
        color: #ffffff !important;
    }


    /* =========================================================
       PAGINATION HOVER
    ========================================================= */

    .paginate_button:hover {
        background: var(--project-gradient) !important;
        border-color: transparent !important;
        color: #ffffff !important;
    }


    /* =========================================================
       SELECT2 DARK THEME
    ========================================================= */

    body[data-theme="dark"] select.select2-hidden-accessible,
    body[data-theme="dark"] select.select_role {
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    body[data-theme="dark"] .select2-container--bootstrap-5 {
        width: 100% !important;
    }


    /* Select2 selection */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single,
    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection--multiple {
        background-color: #243242 !important;
        border: 1px solid #415466 !important;
        color: #d7dde3 !important;
        min-height: 38px !important;
        box-shadow: none !important;
    }


    /* Select2 rendered */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
        color: #d7dde3 !important;
    }


    /* Select2 search */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-search,
    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-search--inline,
    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-search__field {
        border: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-search--inline .select2-search__field {
        color: #ffffff !important;
        margin-top: 0 !important;
        padding: 0 !important;
    }

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-search__field::placeholder {
        color: #9fb0c0 !important;
    }


    /* Select2 tags */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__choice {
        background-color: #33485f !important;
        border: 1px solid #4d657b !important;
        color: #ffffff !important;
        padding: 2px 8px !important;
    }

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__choice__remove {
        color: #ffffff !important;
        margin-right: 6px !important;
    }


    /* Select2 dropdown */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown {
        background-color: #243242 !important;
        border: 1px solid #415466 !important;
    }

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
        background-color: #243242 !important;
        color: #d7dde3 !important;
    }


    /* Select2 highlighted */

    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted,
    body[data-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--selected {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
    }


    /* Select2 focus */

    body[data-theme="dark"] .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    body[data-theme="dark"] .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: var(--project-color-start) !important;
        box-shadow: none !important;
    }


    /* =========================================================
       BREADCRUMB
    ========================================================= */

    .breadcrumb {
        background: transparent !important;
        padding: 0 !important;
        margin-bottom: 0 !important;
    }

    .page-breadcrumb {
        background: transparent !important;
    }


    /* Dark breadcrumb */

    body[data-theme="dark"] .breadcrumb-item a {
        color: #9fb0c0 !important;
    }

    body[data-theme="dark"] .breadcrumb-item.active {
        color: #d7dde3 !important;
    }


    /* Breadcrumb separator */

    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 15px;
        color: #8fa3b5;
    }


    /* =========================================================
       PROJECT GRADIENT BUTTON
    ========================================================= */

    .btn-project {
        background: var(--project-gradient) !important;
        border: none !important;
        color: #ffffff !important;
    }

    .btn-project:hover,
    .btn-project:focus,
    .btn-project:active {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
        opacity: 0.90;
    }


    /* =========================================================
       PROJECT GRADIENT TEXT
    ========================================================= */

    .text-project {
        background: var(--project-gradient) !important;

        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }


    /* =========================================================
       PROJECT GRADIENT BORDER
    ========================================================= */

    .border-project {
        border-color: var(--project-color-start) !important;
    }


    /* =========================================================
       PROJECT BADGE
    ========================================================= */

    .badge-project {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
    }


    /* =========================================================
       ACTIVE TABS
    ========================================================= */

    .nav-tabs .nav-link.active {
        background: var(--project-gradient) !important;
        border-color: transparent !important;
        color: #ffffff !important;
    }


    /* =========================================================
       THEME GRADIENT ICON
    ========================================================= */

    .theme-gradient-icon {
        background: linear-gradient(
            135deg,
            #ffffff 0%,
            #ffe4ec 100%
        ) !important;

        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }


    /* =========================================================
       PROFILE DROPDOWN
    ========================================================= */

    body[data-theme="dark"] .topbar .user-dd {
        background-color: #243242 !important;
        border-color: #415466 !important;
    }

    body[data-theme="dark"] .topbar .user-dd .dropdown-item {
        color: #d7dde3 !important;
    }

    body[data-theme="dark"] .topbar .user-dd .dropdown-item:hover {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
    }


    /* =========================================================
       PROFILE DROPDOWN - LIGHT
    ========================================================= */

    body[data-theme="light"] .topbar .user-dd .dropdown-item:hover {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
    }


    /* =========================================================
       MOBILE
    ========================================================= */

    @media (max-width: 767.98px) {

        .topbar,
        .top-navbar,
        .navbar-header,
        #navbarSupportedContent {
            background: var(--project-gradient) !important;
        }

        .left-sidebar,
        .left-sidebar .scroll-sidebar,
        .left-sidebar .sidebar-nav {
            background: var(--sidebar-bg) !important;
        }

        .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link,
        .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link {
            background: var(--project-gradient) !important;
        }
    }


    /* =========================================================
       FINAL OVERRIDE
       ---------------------------------------------------------
       Valex/Bootstrap template-er existing background যেন
       sidebar-er dark background overwrite korte na পারে
    ========================================================= */

    body[data-theme="light"] .left-sidebar,
    body[data-theme="dark"] .left-sidebar,
    body[data-theme="light"] .left-sidebar .scroll-sidebar,
    body[data-theme="dark"] .left-sidebar .scroll-sidebar,
    body[data-theme="light"] .left-sidebar .sidebar-nav,
    body[data-theme="dark"] .left-sidebar .sidebar-nav,
    body[data-theme="light"] .left-sidebar .sidebar-nav > ul,
    body[data-theme="dark"] .left-sidebar .sidebar-nav > ul {
        background: var(--sidebar-bg) !important;
    }


    /* Keep menu items transparent */

    body[data-theme="light"] .left-sidebar .sidebar-nav ul .sidebar-item .sidebar-link,
    body[data-theme="dark"] .left-sidebar .sidebar-nav ul .sidebar-item .sidebar-link {
        background-color: transparent !important;
    }


    /* Active/selected menu = Project Gradient */

    body[data-theme="light"] .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link,
    body[data-theme="dark"] .left-sidebar .sidebar-nav ul .sidebar-item.selected > .sidebar-link,
    body[data-theme="light"] .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link,
    body[data-theme="dark"] .left-sidebar .sidebar-nav ul .sidebar-item.active > .sidebar-link {
        background: var(--project-gradient) !important;
        color: #ffffff !important;

        border-left: 0 !important;
        border-radius: 0 30px 30px 0 !important;
    }


    /* Child selected */

    body[data-theme="light"] .left-sidebar .sidebar-nav ul .first-level .sidebar-item.selected > .sidebar-link,
    body[data-theme="dark"] .left-sidebar .sidebar-nav ul .first-level .sidebar-item.selected > .sidebar-link,
    body[data-theme="light"] .left-sidebar .sidebar-nav ul .first-level .sidebar-item.active > .sidebar-link,
    body[data-theme="dark"] .left-sidebar .sidebar-nav ul .first-level .sidebar-item.active > .sidebar-link {
        background: rgba(255, 255, 255, 0.18) !important;
        color: #ffffff !important;
    }


    /* =========================================================
       PROFILE DROPDOWN - PROJECT GRADIENT
    ========================================================= */

    .topbar .user-dd {
        border: 0 !important;
        overflow: hidden !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }


    /* Dropdown top profile section */

    .topbar .user-dd > .d-flex.bg-secondary {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
        margin-bottom: 0 !important;
    }


    /* Email */

    .topbar .user-dd > .d-flex.bg-secondary h4 {
        color: #ffffff !important;
    }


    /* Dropdown items */

    .topbar .user-dd .dropdown-item {
        color: #343a40 !important;
        transition: all 0.2s ease;
    }


    /* Hover */

    .topbar .user-dd .dropdown-item:hover {
        background: var(--project-gradient) !important;
        color: #ffffff !important;
    }


    /* Icons */

    .topbar .user-dd .dropdown-item i {
        color: var(--project-color-start) !important;
    }

    .topbar .user-dd .dropdown-item:hover i {
        color: #ffffff !important;
    }


    /* Divider */

    .topbar .user-dd .dropdown-divider {
        border-top: 1px solid #e9ecef !important;
    }


    /* =========================================================
       DATATABLE PAGINATION - CLEAR NUMBER COLORS
    ========================================================= */


    /* Normal pagination numbers */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #ffffff !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        font-weight: 500 !important;
        box-shadow: none !important;
    }

    /* Normal number hover */
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        color: #ffffff !important;
        background: var(--project-gradient) !important;
        border: 1px solid transparent !important;
        box-shadow: none !important;
    }

    /* Active / current page */
    /* Active / current page */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:focus {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        background: var(--project-gradient) !important;
        border: 1px solid transparent !important;
        font-weight: 600 !important;
        box-shadow: none !important;
    }

    /* Previous / Next */
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next {
        color: #ffffff !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        box-shadow: none !important;
    }

    /* Previous / Next hover */
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next:hover {
        color: #ffffff !important;
        background: var(--project-gradient) !important;
        border: 1px solid transparent !important;
        box-shadow: none !important;
    }

    /* Disabled Previous / Next */
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: #ffffff !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        box-shadow: none !important;
    }


    /* =========================================================
       BOOTSTRAP PAGINATION - IF USED
    ========================================================= */

    .pagination .page-link {
        color: #ffffff !important;
        background-color: transparent !important;
        border-color: transparent !important;
    }

    /* Bootstrap pagination hover */
    .pagination .page-link:hover {
        color: #ffffff !important;
        background: var(--project-gradient) !important;
        border-color: transparent !important;
    }

    /* Bootstrap active page */
    .pagination .page-item.active .page-link {
        color: #ffffff !important;
        background: var(--project-gradient) !important;
        border-color: transparent !important;
    }





</style>
