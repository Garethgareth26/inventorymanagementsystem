---
name: Akuna Operational Interface
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#434654'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#737685'
  outline-variant: '#c3c6d6'
  surface-tint: '#0c56d0'
  primary: '#003d9b'
  on-primary: '#ffffff'
  primary-container: '#0052cc'
  on-primary-container: '#c4d2ff'
  inverse-primary: '#b2c5ff'
  secondary: '#585f6c'
  on-secondary: '#ffffff'
  secondary-container: '#dce2f3'
  on-secondary-container: '#5e6572'
  tertiary: '#7b2600'
  on-tertiary: '#ffffff'
  tertiary-container: '#a33500'
  on-tertiary-container: '#ffc6b2'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2ff'
  primary-fixed-dim: '#b2c5ff'
  on-primary-fixed: '#001848'
  on-primary-fixed-variant: '#0040a2'
  secondary-fixed: '#dce2f3'
  secondary-fixed-dim: '#c0c7d6'
  on-secondary-fixed: '#151c27'
  on-secondary-fixed-variant: '#404754'
  tertiary-fixed: '#ffdbcf'
  tertiary-fixed-dim: '#ffb59b'
  on-tertiary-fixed: '#380d00'
  on-tertiary-fixed-variant: '#812800'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
  success: '#10B981'
  warning: '#F59E0B'
  danger: '#EF4444'
  border-subtle: '#E5E7EB'
  text-main: '#111827'
  text-muted: '#4B5563'
  class-a: '#E0E7FF'
  class-b: '#F3F4F6'
  class-c: '#FFFFFF'
typography:
  display-kpi:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '600'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  table-data:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 16px
  label-caps:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  sidebar-expanded: 240px
  sidebar-collapsed: 64px
  header-height: 64px
---

## Brand & Style

The design system is engineered for high-stakes operational environments where accuracy and speed are paramount. It adopts a **Corporate / Modern** aesthetic, heavily influenced by the "Linear" and "Vercel" schools of UI design—characterized by high density, extreme clarity, and a technical finish.

The brand personality is **Professional, Technical, and Reliable**. It avoids playful flourishes in favor of a "tools-first" approach. The interface should feel like a precision instrument: responsive, understated, and systematic. This is achieved through:
- **Minimalist Surface Strategy:** Using whitespace and subtle borders rather than heavy shadows to define structure.
- **Functional Density:** Optimizing for expert users who need to see large amounts of inventory data without vertical fatigue.
- **Monochromatic Foundations:** Using a scale of cool grays to recede into the background, allowing the Deep Blue primary actions and semantic status colors to command immediate attention.

## Colors

The palette is optimized for a **Light Mode** primary experience, focusing on professional utility and long-term legibility.

- **Primary (Deep Blue):** Reserved for intent-driven actions, primary buttons, and active navigational states.
- **Neutral Scale:** Uses a cool-gray spectrum. `#F9FAFB` serves as the canvas, while `#E5E7EB` is the standard for hairline borders and dividers.
- **Semantic Logic:** Success, Warning, and Danger colors are calibrated for high contrast against white backgrounds. 
- **ABC Inventory Priority:** Specific tints are utilized for the ABC analysis module to provide a secondary visual layer of item prioritization without overwhelming the data table.

## Typography

This design system uses **Inter** exclusively to leverage its exceptional legibility and technical feel. 

**Tabular Figures Requirement:**
All numeric data (Stock levels, PO amounts, SKU numbers, Prices) must use `font-variant-numeric: tabular-nums`. This ensures that columns of numbers align perfectly in tables, facilitating rapid scanning and comparison by warehouse staff.

**Hierarchy Rules:**
- **KPIs:** Large, semi-bold weights for dashboard summaries.
- **Data Density:** The standard body size is 14px, but table rows may drop to 13px to increase information density.
- **Labels:** Uppercase labels with slight letter spacing are used for table headers and secondary metadata.

## Layout & Spacing

The system utilizes a **Fixed-Fluid Hybrid Grid** based on a 4px rhythm.

- **Desktop:** A fixed-width sidebar (240px) with a fluid content area. Content is housed in a "Main Container" with a max-width of 1440px for dashboard views to prevent line-lengths from becoming unreadable.
- **Data Grids:** Follow a strict 12-column system within the content area. 
- **Internal Spacing:**
    - `16px (md)` is the standard padding for cards and containers.
    - `8px (sm)` is the standard gap between related form elements.
- **Breakpoints:**
    - **Desktop (1280px+):** Full sidebar, 4-column KPI grids.
    - **Tablet (768px - 1279px):** Collapsed sidebar (icon-only), 2-column KPI grids.
    - **Mobile (<767px):** Bottom navigation or hamburger menu; stacked data cards instead of wide tables.

## Elevation & Depth

To maintain a "SaaS Dashboard" look, depth is communicated primarily through **Tonal Layers** and **Low-Contrast Outlines** rather than shadows.

- **Level 0 (Background):** `#F9FAFB` (Neutral Gray).
- **Level 1 (Cards/Tables):** White background with a `1px` border of `#E5E7EB`. No shadow.
- **Level 2 (Hover States):** Subtle tint change or a very soft, diffused shadow (`0 1px 3px rgba(0,0,0,0.05)`).
- **Level 3 (Modals/Overlays):** These are the only elements allowed significant elevation. Use a medium-diffusion shadow and a 20% opacity black backdrop blur to pull focus.

This approach ensures the UI feels "flat" and efficient, preventing "shadow-creep" which can make data-heavy interfaces feel muddy.

## Shapes

The design system uses a **Soft (Level 1)** roundedness profile. This specific radius (`4px` to `6px`) strikes a balance between the rigid "industrial" feel of sharp corners and the "consumer" feel of pill-shaped buttons.

- **Small elements (Buttons, Inputs, Badges):** 4px radius.
- **Large elements (Cards, Modals):** 6px or 8px radius.
- **Strict Rule:** Never use fully rounded "pill" shapes for buttons or inputs, as they waste horizontal space in high-density tables.

## Components

### Buttons
- **Primary:** Deep Blue background, white text. No gradient. 4px radius.
- **Secondary:** White background, Gray-300 border, Gray-900 text.
- **Destructive:** Red background for critical deletions.

### Tables (The Core Component)
- **Header:** Light gray background (`#F3F4F6`), uppercase 11px bold text.
- **Rows:** 48px height, 1px bottom border. Hover state uses a very faint blue tint to highlight the active row.
- **Numbers:** Always right-aligned with tabular figures.

### Status Badges
- Used for "Stock Levels" or "Order Status."
- **Visual:** Small, subtle background tint with high-contrast text. Always paired with a status label; never use icons alone to communicate state.

### Input Fields
- **Default:** White background, 1px border.
- **Focus State:** Primary Blue border with a 2px "glow" (soft outer shadow) to indicate active entry.
- **Monospace Inputs:** Use for SKU entry or serial numbers to ensure character clarity (e.g., distinguishing '0' from 'O').

### Cards
- Used for Dashboard KPIs. 
- **Structure:** Title (Label-caps) top-left, Value (Display-kpi) center, and a small trend indicator or sparkline at the bottom.