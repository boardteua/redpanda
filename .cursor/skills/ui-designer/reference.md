# UI Designer — reference

Long-form templates and example CSS. Prefer the **project’s** tokens, fonts, and conventions when implementing.

## Example design tokens and base components (CSS)

Illustrative only. Missing steps from the original rule are filled in so hover/border tokens resolve.

```css
/* Design Token System */
:root {
  /* Color Tokens */
  --color-primary-100: #f0f9ff;
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb;
  --color-primary-900: #1e3a8a;

  --color-secondary-100: #f3f4f6;
  --color-secondary-200: #e5e7eb;
  --color-secondary-300: #d1d5db;
  --color-secondary-500: #6b7280;
  --color-secondary-900: #111827;

  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-error: #ef4444;
  --color-info: #3b82f6;

  /* Typography Tokens */
  --font-family-primary: 'Inter', system-ui, sans-serif;
  --font-family-secondary: 'JetBrains Mono', monospace;

  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  --font-size-3xl: 1.875rem;  /* 30px */
  --font-size-4xl: 2.25rem;   /* 36px */

  /* Spacing Tokens */
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */
  --space-12: 3rem;     /* 48px */
  --space-16: 4rem;     /* 64px */

  /* Shadow Tokens */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);

  /* Transition Tokens */
  --transition-fast: 150ms ease;
  --transition-normal: 300ms ease;
  --transition-slow: 500ms ease;
}

/* Dark Theme Tokens */
[data-theme="dark"] {
  --color-primary-100: #1e3a8a;
  --color-primary-500: #60a5fa;
  --color-primary-600: #3b82f6;
  --color-primary-900: #dbeafe;

  --color-secondary-100: #111827;
  --color-secondary-200: #374151;
  --color-secondary-300: #4b5563;
  --color-secondary-500: #9ca3af;
  --color-secondary-900: #f9fafb;
}

/* Base Component Styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-family-primary);
  font-weight: 500;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all var(--transition-fast);
  user-select: none;
}

.btn:focus-visible {
  outline: 2px solid var(--color-primary-500);
  outline-offset: 2px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  pointer-events: none;
}

.btn--primary {
  background-color: var(--color-primary-500);
  color: white;
}

.btn--primary:hover:not(:disabled) {
  background-color: var(--color-primary-600);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.form-input {
  padding: var(--space-3);
  border: 1px solid var(--color-secondary-300);
  border-radius: 0.375rem;
  font-size: var(--font-size-base);
  background-color: white;
  transition: all var(--transition-fast);
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary-500);
  box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
}

.card {
  background-color: white;
  border-radius: 0.5rem;
  border: 1px solid var(--color-secondary-200);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: all var(--transition-normal);
}

.card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
```

## Mobile-first container pattern (CSS)

```css
.container {
  width: 100%;
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--space-4);
  padding-right: var(--space-4);
}

@media (min-width: 640px) {
  .container { max-width: 640px; }
}

@media (min-width: 768px) {
  .container { max-width: 768px; }
}

@media (min-width: 1024px) {
  .container {
    max-width: 1024px;
    padding-left: var(--space-6);
    padding-right: var(--space-6);
  }
}

@media (min-width: 1280px) {
  .container {
    max-width: 1280px;
    padding-left: var(--space-8);
    padding-right: var(--space-8);
  }
}
```

## Design deliverable template (Markdown)

Copy and fill in for handoff documents.

```markdown
# [Project Name] UI Design System

## Design foundations

### Color system
- **Primary colors**: [Brand palette with hex]
- **Secondary colors**: [Supporting variations]
- **Semantic colors**: [Success, warning, error, info]
- **Neutral palette**: [Grayscale for text and surfaces]
- **Accessibility**: [WCAG AA pairings and contrast notes]

### Typography
- **Primary font**: [Headlines and UI]
- **Secondary font**: [Body / mono if needed]
- **Scale**: [e.g. 12 → 14 → 16 → 18 → 24 → 30 → 36]
- **Weights**: [e.g. 400, 500, 600, 700]
- **Line heights**: [Body vs heading]

### Spacing
- **Base unit**: [e.g. 4px]
- **Scale**: [e.g. 4, 8, 12, 16, 24, 32, 48, 64]
- **Usage**: [Margins, padding, gaps between components]

## Component library

### Base components
- **Buttons**: [Variants and sizes]
- **Form elements**: [Inputs, select, checkbox, radio]
- **Navigation**: [Menus, breadcrumbs, pagination]
- **Feedback**: [Alerts, toasts, modals, tooltips]
- **Data display**: [Cards, tables, lists, badges]

### States
- **Interactive**: [Default, hover, active, focus, disabled]
- **Loading**: [Skeleton, spinner, progress]
- **Error**: [Validation and messaging]
- **Empty**: [No-data guidance]

## Responsive design

### Breakpoints
- **Mobile**: 320px–639px (base)
- **Tablet**: 640px–1023px
- **Desktop**: 1024px–1279px
- **Large desktop**: 1280px+

### Layout
- **Grid**: [e.g. 12-column, behavior per breakpoint]
- **Containers**: [Max-widths and horizontal padding]
- **Component behavior**: [How modules reflow]

## Accessibility (WCAG AA)

- **Contrast**: 4.5:1 normal text; 3:1 large text
- **Keyboard**: Full operation without pointer
- **Screen readers**: Semantic structure; ARIA where needed
- **Focus**: Visible focus and logical tab order
- **Touch targets**: ≥44px where applicable
- **Motion**: Honor `prefers-reduced-motion`
- **Zoom**: Usable at ~200% text scaling

---

**Design system date**: [Date]
**Handoff**: Ready for implementation
**QA**: [Review steps — e.g. visual diff, axe, keyboard pass]
```

## Learning focus (optional checklist)

- Component patterns that reduce cognitive load
- Hierarchy that supports task completion
- Spacing and type choices that maximize readability
- When to vary interaction patterns vs stay consistent
