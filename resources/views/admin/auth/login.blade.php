<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Packaging, distribution, and traceability operations.">
    <title>Remark Packaging Operations</title>
    <link rel="icon" href="{{ asset('images/remark-icon.png') }}" type="image/png">

    <style>
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
            src: url('{{ asset('fonts/outfit-latin.woff2') }}') format('woff2');
        }

        :root {
            --brand-navy: #00416b;
            --brand-navy-dark: #002e4d;
            --brand-navy-light: #0a5588;
            --brand-red: #d71921;
            --brand-red-dark: #b3141b;
            --paper: #f8f5f2;
            --text: #565656;
        }

        * { box-sizing: border-box; }
        html { min-height: 100%; }

        body {
            min-height: 100%;
            margin: 0;
            background: var(--paper);
            color: var(--text);
            font-family: 'Outfit', Arial, sans-serif;
            font-feature-settings: "cv11", "ss01";
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        button, input { font: inherit; }
        .login-layout { display: grid; min-height: 100vh; }
        .process-panel { display: none; }

        .process-content {
            display: flex;
            height: 100%;
            flex-direction: column;
            justify-content: space-between;
            gap: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--brand-navy-dark), var(--brand-navy), var(--brand-navy-light));
        }

        .eyebrow {
            margin: 0;
            color: rgba(255, 255, 255, .7);
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .2em;
            line-height: 1rem;
            text-transform: uppercase;
        }

        .process-title {
            margin: .5rem 0 0;
            color: #fff;
            font-size: 1.875rem;
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.25;
        }

        .process-copy {
            max-width: 28rem;
            margin: .75rem 0 0;
            color: rgba(255, 255, 255, .8);
            font-size: .875rem;
            line-height: 1.5rem;
        }

        .scene-frame {
            padding: .75rem;
            overflow: hidden;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, .1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35);
            outline: 1px solid rgba(255, 255, 255, .15);
        }

        .scene-frame svg { display: block; width: 100%; height: auto; }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.25rem;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .process-step { display: flex; align-items: flex-start; gap: .625rem; }

        .step-number {
            display: flex;
            width: 1.75rem;
            height: 1.75rem;
            flex: 0 0 1.75rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: var(--brand-red);
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
        }

        .step-label {
            padding-top: .125rem;
            color: rgba(255, 255, 255, .9);
            font-size: .75rem;
            font-weight: 500;
            line-height: 1.375;
        }

        .tagline { margin: 0; color: rgba(255, 255, 255, .9); font-size: .875rem; font-weight: 600; }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
        }

        .login-content { width: 100%; max-width: 24rem; }

        .brand-logo {
            display: block;
            width: 11rem;
            height: auto;
            margin: 0 0 2.5rem;
        }

        .login-kicker {
            margin: 0;
            color: var(--brand-navy);
            font-size: .875rem;
            font-weight: 600;
            letter-spacing: .16em;
            line-height: 1.25rem;
            text-transform: uppercase;
        }

        .login-title {
            margin: .5rem 0 0;
            color: #161c25;
            font-size: 1.875rem;
            font-weight: 600;
            letter-spacing: -.025em;
            line-height: 2.25rem;
        }

        .login-copy {
            margin: .5rem 0 2rem;
            color: #6f6f6f;
            font-size: .875rem;
            line-height: 1.5rem;
        }

        .login-form { display: flex; flex-direction: column; gap: 1.25rem; }

        .field-label {
            display: block;
            color: #344054;
            font-size: .875rem;
            font-weight: 500;
            line-height: 1.25rem;
        }

        .field-input {
            display: block;
            width: 100%;
            height: 2.75rem;
            margin-top: .5rem;
            padding: .625rem 1rem;
            border: 1px solid #d0d5dd;
            border-radius: .5rem;
            outline: none;
            background: transparent;
            color: #1d2939;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
            font-size: 1rem;
            line-height: 1.5rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field-input:focus { border-color: #7fa7c4; box-shadow: 0 0 0 3px rgba(0, 65, 107, .2); }
        .field-input[aria-invalid="true"] { border-color: #f04438; }

        .field-error {
            display: block;
            margin-top: .4rem;
            color: #b42318;
            font-size: .8125rem;
            line-height: 1.125rem;
        }

        .alert {
            margin: 0;
            padding: .5rem .75rem;
            border-radius: .5rem;
            background: #fef3f2;
            color: #9f1239;
            font-size: .875rem;
            line-height: 1.25rem;
        }

        .sign-in-button {
            display: inline-flex;
            width: 100%;
            height: 2.75rem;
            align-items: center;
            justify-content: center;
            padding: 0 1.25rem;
            border: 1px solid transparent;
            border-radius: .5rem;
            background: var(--brand-red);
            color: #fff;
            cursor: pointer;
            font-size: .875rem;
            font-weight: 500;
            line-height: 1.25rem;
            transition: background-color .15s ease, transform .05s ease, opacity .15s ease;
        }

        .sign-in-button:hover { background: var(--brand-red-dark); }
        .sign-in-button:active { transform: translateY(1px); }
        .sign-in-button:focus-visible { outline: 2px solid var(--brand-navy); outline-offset: 2px; }
        .sign-in-button:disabled { cursor: not-allowed; opacity: .5; }

        @media (min-width: 1024px) {
            .login-layout { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); }
            .process-panel { display: block; }
        }

        @media (min-width: 1280px) {
            .process-content { padding: 3rem; }
            .process-title { font-size: 2.25rem; line-height: 2.5rem; }
            .process-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body>
<main class="login-layout">
    <aside class="process-panel" aria-hidden="true">
        <div class="process-content">
            <div>
                <p class="eyebrow">Packaging process</p>
                <h2 class="process-title">Step by step,<br>carton by carton.</h2>
                <p class="process-copy">From the imported order to the confirmed carton, every scan is checked against the order and recorded.</p>
            </div>

            <div class="scene-frame">
                <svg viewBox="0 0 640 260" role="img" aria-label="Remark truck leaving the warehouse on a dispatch route">
                    <defs>
                        <linearGradient id="sky" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#dbe9f4"/>
                            <stop offset="100%" stop-color="#f6f9fc"/>
                        </linearGradient>
                        <linearGradient id="hill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#9fc3d9"/>
                            <stop offset="100%" stop-color="#c9dfec"/>
                        </linearGradient>
                    </defs>
                    <rect width="640" height="260" rx="22" fill="url(#sky)"/>
                    <path d="M0 190 C90 140 170 170 250 150 C340 128 420 168 520 140 C570 128 610 140 640 150 V260 H0Z" fill="url(#hill)"/>
                    <circle cx="540" cy="52" r="26" fill="#fff" opacity=".9"/>
                    <ellipse cx="120" cy="50" rx="46" ry="12" fill="#fff" opacity=".85"/>
                    <ellipse cx="150" cy="42" rx="30" ry="10" fill="#fff" opacity=".85"/>

                    <g>
                        <rect x="26" y="112" width="190" height="86" fill="#fff" stroke="#c9d6e0"/>
                        <path d="M18 114 L121 82 L224 114Z" fill="#00416b"/>
                        <rect x="46" y="140" width="62" height="58" fill="#e9eff4" stroke="#c9d6e0"/>
                        <line x1="46" x2="108" y1="146" y2="146" stroke="#c9d6e0"/>
                        <line x1="46" x2="108" y1="160" y2="160" stroke="#c9d6e0"/>
                        <line x1="46" x2="108" y1="174" y2="174" stroke="#c9d6e0"/>
                        <line x1="46" x2="108" y1="188" y2="188" stroke="#c9d6e0"/>
                        <rect x="126" y="140" width="72" height="58" fill="#e9eff4" stroke="#c9d6e0"/>
                        <line x1="126" x2="198" y1="146" y2="146" stroke="#c9d6e0"/>
                        <line x1="126" x2="198" y1="160" y2="160" stroke="#c9d6e0"/>
                        <line x1="126" x2="198" y1="174" y2="174" stroke="#c9d6e0"/>
                        <line x1="126" x2="198" y1="188" y2="188" stroke="#c9d6e0"/>
                        <rect x="88" y="94" width="66" height="16" rx="3" fill="#fff"/>
                        <text x="121" y="106" text-anchor="middle" font-size="10" font-weight="700" fill="#00416b">REMARK HB</text>
                        <g>
                            <rect x="134" y="172" width="30" height="26" rx="3" fill="#d9a766"/>
                            <rect x="134" y="172" width="30" height="7.28" rx="3" fill="#c28f4f"/>
                            <rect x="144" y="172" width="10" height="26" fill="#f3e3c3" opacity=".85"/>
                            <rect x="142" y="184" width="14" height="7" rx="1.5" fill="#fff" opacity=".8"/>
                        </g>
                        <g>
                            <rect x="166" y="176" width="28" height="22" rx="3" fill="#d9a766"/>
                            <rect x="166" y="176" width="28" height="6.16" rx="3" fill="#c28f4f"/>
                            <rect x="175" y="176" width="10" height="22" fill="#f3e3c3" opacity=".85"/>
                            <rect x="174" y="184" width="14" height="7" rx="1.5" fill="#fff" opacity=".8"/>
                        </g>
                    </g>

                    <rect x="0" y="206" width="640" height="38" fill="#4a5568"/>
                    <line x1="0" x2="640" y1="225" y2="225" stroke="#fff" stroke-width="3" stroke-dasharray="22 16" opacity=".85"/>
                    <path d="M240 196 C320 160 380 170 430 150 C490 126 560 132 610 104" fill="none" stroke="#d71921" stroke-width="3" stroke-dasharray="2 9" stroke-linecap="round"/>
                    <circle cx="610" cy="104" r="9" fill="#d71921"/>
                    <circle cx="610" cy="104" r="3.5" fill="#fff"/>

                    <g>
                        <rect x="250" y="130" width="214" height="84" rx="8" fill="#fff" stroke="#c9d6e0"/>
                        <rect x="250" y="196" width="214" height="9" fill="#00416b"/>
                        <image href="{{ asset('images/remark-icon.png') }}" x="268" y="146" width="42" height="42"/>
                        <text x="322" y="165" font-size="21" font-weight="800" fill="#00416b">REMARK</text>
                        <text x="322" y="183" font-size="11" font-weight="700" fill="#d71921">LLC, USA</text>
                        <path d="M464 156 H508 Q518 156 524 166 L540 190 Q544 198 544 206 V214 H464Z" fill="#fff" stroke="#c9d6e0"/>
                        <path d="M472 164 H504 Q510 164 514 171 L524 188 H472Z" fill="#9fc3d9"/>
                        <rect x="464" y="196" width="80" height="9" fill="#d71921"/>
                        <g><circle cx="300" cy="216" r="15" fill="#1f2937"/><circle cx="300" cy="216" r="6.5" fill="#cbd5e1"/></g>
                        <g><circle cx="372" cy="216" r="15" fill="#1f2937"/><circle cx="372" cy="216" r="6.5" fill="#cbd5e1"/></g>
                        <g><circle cx="504" cy="216" r="15" fill="#1f2937"/><circle cx="504" cy="216" r="6.5" fill="#cbd5e1"/></g>
                    </g>
                </svg>
            </div>

            <ol class="process-steps">
                @foreach([
                    'Order imported from JSON, original kept',
                    'Work order created and released',
                    'Cartons packed by barcode scan',
                    'Live quantity check, no overpacking',
                    'Carton contents confirmed',
                    'Full carton history and audit trail',
                ] as $step)
                    <li class="process-step">
                        <span class="step-number">{{ $loop->iteration }}</span>
                        <span class="step-label">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>

            <p class="tagline">Together Delivering Better</p>
        </div>
    </aside>

    <section class="login-panel">
        <div class="login-content">
            <img class="brand-logo" src="{{ asset('images/remark-logo.png') }}" alt="Remark" width="400" height="83">
            <p class="login-kicker">Remark Operations</p>
            <h1 class="login-title">Sign in to your workspace</h1>
            <p class="login-copy">Use the account assigned by your system administrator.</p>

            <form class="login-form" method="POST" action="{{ route('login') }}" data-login-form>
                @csrf

                <label class="field-label" for="email">
                    Email address
                    <input
                        class="field-input"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <span class="field-error" id="email-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="field-label" for="password">
                    Password
                    <input
                        class="field-input"
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <span class="field-error" id="password-error">{{ $message }}</span>
                    @enderror
                </label>

                @if (session('status'))
                    <p class="alert" role="status">{{ session('status') }}</p>
                @endif

                <button class="sign-in-button" type="submit" data-submit-button>Sign in</button>
            </form>
        </div>
    </section>
</main>

<script>
    document.querySelector('[data-login-form]')?.addEventListener('submit', function () {
        const button = this.querySelector('[data-submit-button]');

        if (button) {
            button.disabled = true;
            button.textContent = 'Signing in…';
        }
    });
</script>
</body>
</html>
