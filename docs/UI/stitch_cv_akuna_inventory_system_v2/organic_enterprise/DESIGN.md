---
name: Organic Enterprise
colors:
  surface: '#fcf9f2'
  surface-dim: '#dddad3'
  surface-bright: '#fcf9f2'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3ec'
  surface-container: '#f1eee7'
  surface-container-high: '#ebe8e1'
  surface-container-highest: '#e5e2db'
  on-surface: '#1c1c18'
  on-surface-variant: '#424843'
  inverse-surface: '#31312c'
  inverse-on-surface: '#f4f0e9'
  outline: '#727972'
  outline-variant: '#c2c8c1'
  surface-tint: '#476551'
  primary: '#274432'
  on-primary: '#ffffff'
  primary-container: '#3e5c48'
  on-primary-container: '#b1d3ba'
  inverse-primary: '#adcfb5'
  secondary: '#56615a'
  on-secondary: '#ffffff'
  secondary-container: '#d9e5dc'
  on-secondary-container: '#5c6760'
  tertiary: '#573701'
  on-tertiary: '#ffffff'
  tertiary-container: '#724e17'
  on-tertiary-container: '#f4c280'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#c9ebd1'
  primary-fixed-dim: '#adcfb5'
  on-primary-fixed: '#032111'
  on-primary-fixed-variant: '#2f4d3a'
  secondary-fixed: '#d9e5dc'
  secondary-fixed-dim: '#bdc9c1'
  on-secondary-fixed: '#131e18'
  on-secondary-fixed-variant: '#3e4943'
  tertiary-fixed: '#ffddb4'
  tertiary-fixed-dim: '#f0be7c'
  on-tertiary-fixed: '#291800'
  on-tertiary-fixed-variant: '#614008'
  background: '#fcf9f2'
  on-background: '#1c1c18'
  surface-variant: '#e5e2db'
  page-bg: '#EDEAE3'
  card-surface: '#FFFFFF'
  text-primary: '#2A2A26'
  text-secondary: '#8B8880'
  border-divider: '#E7E4DC'
  negative-rose: '#C9697A'
  negative-bg: '#F7DEE2'
  danger-red: '#B3413F'
  warning-amber: '#C98A3B'
  accent-tan-light: '#F3E4C9'
typography:
  display-kpi:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  tabular-nums:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 24px
  margin: 32px
---

## Brand & Style

This design system reimagines inventory management through the lens of wellness and sophisticated industrialism. By departing from the cold, high-contrast aesthetics typical of ERP software, it fosters a **calm, professional, and spacious** environment that reduces cognitive load during intensive data operations.

The style is a blend of **Minimalism** and **Tactile Softness**. It prioritizes high-quality white space, a warm organic palette, and a "Pill-first" geometry. The interface feels light and approachable yet retains the structural integrity required for complex logistics. Key visual drivers include:
- **Floating Architecture:** Components feel unanchored and airy, utilizing depth through layering rather than aggressive shadows.
- **Organic Precision:** A rigorous 4px grid system ensures data density, while "extreme rounding" softens the overall user experience.
- **Operational Serenity:** The use of Sage and Greige tones creates a neutral workspace that highlights critical data without inducing fatigue.

## Colors

The palette is anchored by **Deep Sage Green**, symbolizing growth and stability, balanced against a **Warm Greige** background that provides a softer canvas than pure white.

### Functional Application
- **Primary Action (#3E5C48):** Reserved for high-priority CTAs, active navigation states, and primary chart metrics.
- **Soft Tints (#DCE8DF):** Used for decorative backgrounds behind icons and progress tracks to maintain a low-stimulus environment.
- **Value Metrics (#C99B5D):** A secondary accent used to differentiate financial or classification data (e.g., ABC Class B) from operational data.
- **Semantic Feedback:** Critical alerts utilize a specialized **Soft Rose** and **Deep Red** to ensure urgency is conveyed without breaking the serene aesthetic.

The interface primarily operates in a **Light Mode** to leverage the natural, paper-like qualities of the Greige/White contrast.

## Typography

This design system uses **Plus Jakarta Sans** as its sole typeface. Its geometric construction and soft terminals perfectly complement the "rounded" shape language of the UI.

### Key Rules
- **KPI Visualization:** Large metrics use `display-kpi` with tight letter spacing to emphasize importance.
- **Data Integrity:** All tables and financial comparisons must use `tabular-nums` settings to ensure vertical alignment of digits across rows.
- **Hierarchy:** Labels use uppercase with slight letter spacing to differentiate metadata from primary body content.
- **Warmth through Weight:** Avoid thin weights (under 400). Use Medium (500) and Semibold (600) to maintain legibility against the warm neutral backgrounds.

## Layout & Spacing

The layout utilizes a **12-column fluid grid** for desktop, reflowing to 4 or 2 columns on mobile. 

### Spacing Philosophy
- **4px Base Unit:** All margins and paddings are multiples of 4px.
- **The "Airy" Card:** Internal card padding is set to a generous `32px` (xl) to create a feeling of luxury and clarity.
- **Floating Sidebar:** A unique, detached navigation rail sits `24px` from the left edge, reinforcing the "floating" aesthetic.
- **Adaptive Density:** While containers are spacious, internal data elements (like table rows) can compress to `8px` or `12px` vertical padding to handle high operational data loads without excessive scrolling.

## Elevation & Depth

Depth is achieved through **Tonal Layering** rather than traditional drop shadows.

- **Surface Levels:** The primary background is `page-bg` (#EDEAE3). Cards and floating panels use `card-surface` (#FFFFFF) to pop forward.
- **Shadow Profile:** Standard cards have zero shadow. A "Soft Ambient Blur" (8% opacity, 12px blur, 4px Y-offset) is reserved exclusively for interactive overlays, dropdowns, and the floating sidebar.
- **Interactive State:** On hover, cards may elevate slightly using the ambient shadow profile to indicate clickability. 
- **Z-Index:** The sidebar and top search bar exist on the highest plane, floating over the scrollable content area.

## Shapes

The shape language is defined by **Pill-shaped (Fully Rounded)** elements for all interactive components and **Softly Rounded** containers for structural elements.

- **Pill Radius:** Applied to buttons, input fields, search bars, badges, and filter chips.
- **Container Radius:** Cards, modals, and the sidebar rail use a consistent `20px` to `24px` radius.
- **Visual Harmony:** Icon containers are strictly circular, ensuring no sharp corners exist within the primary viewport.

## Components

### Buttons & Inputs
- **Primary Button:** Fully rounded pill with `#3E5C48` fill and white text.
- **Secondary/Outline:** Pill shape with `#3E5C48` border and transparent background.
- **Inputs:** Pill-shaped with a 1px `#E7E4DC` border. Focus state uses a 2px Sage border.

### Sidebar (Floating Rail)
- A vertical capsule-shaped white bar detached from the left edge.
- Icons sit within circular hover states. The active item features a Sage background or a vertical indicator with soft rounding.

### Cards & Metrics
- **Metric Cards:** Large `24px` rounded corners. KPI icons are placed in a `#DCE8DF` (Pale Sage) or `#F3E4C9` (Tan) circular badge.
- **The "Hero" Metric:** One featured card per dashboard uses a solid `#3E5C48` background with white text and an organic, smooth spline sparkline.

### Charts
- **Bars:** All bar charts must have fully rounded caps (`radius: 999px`).
- **Donuts:** Thick arcs with rounded ends.
- **Lines:** Use organic splines (monotone cubic interpolation) rather than straight lines to maintain the "calm" aesthetic.

### Tables
- Rows are separated by 1px `#E7E4DC` dividers. 
- Status indicators (e.g., "In Stock", "Low Stock") are pill-shaped badges with soft semantic background tints.