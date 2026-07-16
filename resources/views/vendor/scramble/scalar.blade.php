<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ __('docs.api.title') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* Brand accent */
            --brand: #6366f1;
            --brand-strong: #4f46e5;
        }

        html,
        body {
            margin: 0;
            height: 100%;
        }

        /* ---- Scalar light theme customization ---- */
        .light-mode {
            --scalar-color-1: #1e2233;
            --scalar-color-2: #4a5069;
            --scalar-color-3: #8a90a6;
            --scalar-color-accent: var(--brand-strong);

            --scalar-background-1: #ffffff;
            --scalar-background-2: #f6f7fb;
            --scalar-background-3: #eef0f7;
            --scalar-background-accent: #eef0ff;

            --scalar-border-color: #e7e9f2;

            --scalar-font: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            --scalar-font-code: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;

            --scalar-radius: 10px;
            --scalar-radius-lg: 14px;

            /* HTTP method badge colors */
            --scalar-color-green: #16a34a;
            --scalar-color-blue: #2563eb;
            --scalar-color-orange: #ea580c;
            --scalar-color-red: #dc2626;
            --scalar-color-purple: #7c3aed;
        }

        /* Sidebar */
        .light-mode .t-doc__sidebar {
            --scalar-sidebar-background-1: #fafbff;
            --scalar-sidebar-color-1: #2b3048;
            --scalar-sidebar-color-2: #6b7192;
            --scalar-sidebar-border-color: #e7e9f2;
            --scalar-sidebar-item-hover-background: #eef0ff;
            --scalar-sidebar-item-active-background: #eef0ff;
            --scalar-sidebar-color-active: var(--brand-strong);
            --scalar-sidebar-search-background: #ffffff;
            --scalar-sidebar-search-border-color: #e2e5f0;
        }

        /* Soften cards / rounded corners */
        .scalar-card,
        .scalar-api-reference .endpoint-label {
            border-radius: var(--scalar-radius-lg) !important;
        }

        /* Nicer header accent */
        .scalar-api-reference .references-header {
            border-bottom: 1px solid var(--scalar-border-color);
        }

        /* ---- Floating language switcher ---- */
        .docs-lang {
            position: fixed;
            top: 14px;
            right: 18px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px;
            background: #ffffff;
            border: 1px solid var(--brand);
            border-radius: 999px;
            box-shadow: 0 2px 10px rgba(79, 70, 229, .12);
            font-family: var(--scalar-font, 'Instrument Sans', sans-serif);
        }

        .docs-lang__label {
            font-size: 12px;
            font-weight: 600;
            color: #4a5069;
        }

        .docs-lang__btn {
            appearance: none;
            border: 0;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: #4a5069;
            background: transparent;
            transition: background .15s ease, color .15s ease;
        }

        .docs-lang__btn:hover {
            background: #eef0ff;
        }

        .docs-lang__btn.is-active {
            background: var(--brand-strong);
            color: #ffffff;
        }
    </style>
</head>

<body>
    <nav class="docs-lang" aria-label="{{ __('docs.api.labels.language') }}">
        <span class="docs-lang__label">{{ __('docs.api.labels.language') }}</span>
        @foreach (config('app.supported_locales', []) as $locale)
            <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
                class="docs-lang__btn {{ app()->getLocale() === $locale ? 'is-active' : '' }}"
                hreflang="{{ $locale }}">{{ strtoupper($locale) }}</a>
        @endforeach
    </nav>
    <div id="app"></div>
    <script src="{{ $config->renderer()->get('cdn', 'https://cdn.jsdelivr.net/npm/@scalar/api-reference') }}"></script>

    <script>
        const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
        const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
        const getCookieValue = (key) => {
            const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
            return cookie?.split("=")[1];
        };

        Scalar.createApiReference('#app', {
            content: @json($spec),
            ...@json($config->renderer()->all(except: ['cdn', 'credentials'])),
            theme: 'none',
            layout: 'modern',
            hideDarkModeToggle: true,
            defaultOpenAllTags: true,
            onBeforeRequest: ({
                requestBuilder
            }) => {
                const token = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
                if (token) {
                    requestBuilder.headers.set(CSRF_TOKEN_HEADER_KEY, decodeURIComponent(token));
                }
            },
            customFetch: (input, init) => {
                return window.fetch(input, {
                    ...init,
                    credentials: @json($config->renderer()->get('credentials', 'include'))
                })
            }
        })
    </script>
</body>

</html>
