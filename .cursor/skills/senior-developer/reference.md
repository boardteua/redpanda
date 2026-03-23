# Senior developer — reference

Laravel / Livewire / FluxUI patterns and luxury-style CSS samples. Adjust naming, namespaces, and paths to **this** project.

## Optional agency layout

If the repo contains them, use internal indexes before guessing APIs:

- Component index: `ai/system/component-library.md` (if present)
- Style patterns: `ai/system/premium-style-guide.md`, `ai/system/advanced-tech-patterns.md` (if present)
- Extended dev playbook: `ai/agents/dev.md` (if present)

## Livewire component (PHP)

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class PremiumNavigation extends Component
{
    public bool $mobileMenuOpen = false;

    public function render()
    {
        return view('livewire.premium-navigation');
    }
}
```

## FluxUI composition (HTML / Blade)

Illustrative; verify prop names and slots against current Flux docs.

```html
<flux:card class="luxury-glass hover:scale-105 transition-all duration-300">
    <flux:heading size="lg" class="gradient-text">Premium Content</flux:heading>
    <flux:text class="opacity-80">With sophisticated styling</flux:text>
</flux:card>
```

Docs: [Flux UI components](https://fluxui.dev/docs)

## Premium-style CSS (illustrative)

```css
.luxury-glass {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(30px) saturate(200%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
}

.magnetic-element {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.magnetic-element:hover {
    transform: scale(1.05) translateY(-2px);
}
```

Pair with `@media (prefers-reduced-motion: reduce)` to simplify or disable motion when appropriate.

## Three.js and advanced visuals

Use when the spec asks for depth or immersion and perf budget allows:

- Hero particles or subtle WebGL accents
- Product or feature 3D where it clarifies value
- Respect reduced motion and low-power devices; offer static fallback

## Targets (tune to product)

Original rule suggested:

- **~1.5s** meaningful load for marketing-style pages on a defined network profile (state assumption)
- **Smooth** animations — profile with DevTools; avoid layout thrash and huge composited layers
- **Responsive** coverage for agreed breakpoints

These are **goals**, not universal law; align with project SLOs and measurement setup.
