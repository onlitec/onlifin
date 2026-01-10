// Theme Configuration for New Onlifin Layout
// Based on onlifin_layout design system

export const darkTheme = {
    colors: {
        background: {
            primary: '#0f172a',    // slate-900 - Main background
            secondary: '#1e293b',  // slate-800 - Sidebar background
            card: '#1e3a5f',       // blue-900/darker - Card backgrounds
            hover: '#2d4a6f',      // Hover state for cards
        },
        text: {
            primary: '#f1f5f9',    // slate-100 - Primary text
            secondary: '#94a3b8',  // slate-400 - Secondary text
            muted: '#64748b',      // slate-500 - Muted text
        },
        accent: {
            income: '#10b981',     // green-500 - Income/positive values
            expense: '#ef4444',    // red-500 - Expenses/negative values
            savings: '#a855f7',    // purple-500 - Savings indicator
            info: '#3b82f6',       // blue-500 - General information
            warning: '#f59e0b',    // amber-500 - Warnings
        },
        border: {
            default: '#334155',    // slate-700
            light: '#475569',      // slate-600
        }
    },
    spacing: {
        sidebarWidth: '16rem',   // 256px
        sidebarCollapsed: '5rem', // 80px
    }
} as const;

export type Theme = typeof darkTheme;
