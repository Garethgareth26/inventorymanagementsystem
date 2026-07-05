import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ── Surface Scale ────────────────────────────────────
                'surface':                   '#f8f9fa',
                'surface-dim':               '#d9dadb',
                'surface-bright':            '#f8f9fa',
                'surface-container-lowest':  '#ffffff',
                'surface-container-low':     '#f3f4f5',
                'surface-container':         '#edeeef',
                'surface-container-high':    '#e7e8e9',
                'surface-container-highest': '#e1e3e4',
                'surface-variant':           '#e1e3e4',
                'surface-tint':              '#0c56d0',

                // ── On-Surface ───────────────────────────────────────
                'on-surface':         '#191c1d',
                'on-surface-variant': '#434654',
                'inverse-surface':    '#2e3132',
                'inverse-on-surface': '#f0f1f2',

                // ── Outline ──────────────────────────────────────────
                'outline':         '#737685',
                'outline-variant': '#c3c6d6',
                'border-subtle':   '#E5E7EB',

                // ── Primary ──────────────────────────────────────────
                'primary':                    '#003d9b',
                'on-primary':                 '#ffffff',
                'primary-container':          '#0052cc',
                'on-primary-container':       '#c4d2ff',
                'primary-fixed':              '#dae2ff',
                'primary-fixed-dim':          '#b2c5ff',
                'on-primary-fixed':           '#001848',
                'on-primary-fixed-variant':   '#0040a2',
                'inverse-primary':            '#b2c5ff',

                // ── Secondary ────────────────────────────────────────
                'secondary':                  '#585f6c',
                'on-secondary':               '#ffffff',
                'secondary-container':        '#dce2f3',
                'on-secondary-container':     '#5e6572',
                'secondary-fixed':            '#dce2f3',
                'secondary-fixed-dim':        '#c0c7d6',
                'on-secondary-fixed':         '#151c27',
                'on-secondary-fixed-variant': '#404754',

                // ── Tertiary ─────────────────────────────────────────
                'tertiary':                   '#7b2600',
                'on-tertiary':                '#ffffff',
                'tertiary-container':         '#a33500',
                'on-tertiary-container':      '#ffc6b2',
                'tertiary-fixed':             '#ffdbcf',
                'tertiary-fixed-dim':         '#ffb59b',
                'on-tertiary-fixed':          '#380d00',
                'on-tertiary-fixed-variant':  '#812800',

                // ── Error ────────────────────────────────────────────
                'error':              '#ba1a1a',
                'on-error':           '#ffffff',
                'error-container':    '#ffdad6',
                'on-error-container': '#93000a',

                // ── Background ───────────────────────────────────────
                'background':    '#f8f9fa',
                'on-background': '#191c1d',

                // ── Semantic ─────────────────────────────────────────
                'success': '#10B981',
                'warning': '#F59E0B',
                'danger':  '#EF4444',

                // ── Text Aliases ─────────────────────────────────────
                'text-main':  '#111827',
                'text-muted': '#4B5563',

                // ── ABC Classification Tints ─────────────────────────
                'class-a': '#E0E7FF',
                'class-b': '#F3F4F6',
                'class-c': '#FFFFFF',
            },
            spacing: {
                'xs':                '4px',
                'sm':                '8px',
                'md':                '16px',
                'lg':                '24px',
                'xl':                '32px',
                'base':              '4px',
                'sidebar-expanded':  '240px',
                'sidebar-collapsed': '64px',
                'header-height':     '64px',
            },
            borderRadius: {
                DEFAULT: '0.25rem',   // 4px — buttons, inputs, badges
                'lg':    '0.5rem',    // 8px — cards
                'xl':    '0.75rem',   // 12px — modals/bento cards
                'full':  '9999px',    // pill (avatars only)
            },
            fontSize: {
                'label-caps': ['11px', { lineHeight: '16px', letterSpacing: '0.05em', fontWeight: '600' }],
                'display-kpi': ['36px', { lineHeight: '44px', letterSpacing: '-0.02em', fontWeight: '600' }],
                'body-sm':     ['13px', { lineHeight: '18px', fontWeight: '400' }],
                'body-md':     ['14px', { lineHeight: '20px', fontWeight: '400' }],
                'headline-md': ['18px', { lineHeight: '28px', fontWeight: '600' }],
                'headline-lg': ['24px', { lineHeight: '32px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'table-data':  ['13px', { lineHeight: '16px', fontWeight: '400' }],
            },
            maxWidth: {
                'canvas': '1440px',
            },
        },
    },

    plugins: [forms],
};
