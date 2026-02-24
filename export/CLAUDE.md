# Croissant

A Statamic starter kit based on [Peak](https://github.com/studio1902/statamic-peak) with CUBE CSS methodology replacing Peak's original CSS architecture.

## Stack

- Statamic 6 (Laravel 12)
- Tailwind CSS 4 — utilities only, no preflight (uses Piccalilli reset instead)
- CUBE CSS methodology
- AlpineJS, Vite 7
- Peak plugins: `statamic-peak-seo`, `statamic-peak-tools`

## CUBE CSS Architecture

CUBE = Composition, Utility, Block, Exception. CSS is organized in four layers:

```
@layer theme    — design tokens (auto-generated from JSON)
@layer base     — reset, fonts, variables, global styles
@layer components — compositions + blocks
@layer utilities — Tailwind utilities + custom utilities
```

Entry point: `resources/css/site.css`

### No Tailwind Preflight

This project deliberately excludes Tailwind's preflight. It imports only `tailwindcss/theme.css` and `tailwindcss/utilities.css`. Base styles come from the Piccalilli reset (`resources/css/global/reset.css`) and global styles.

### No `@apply`

Compositions and blocks use vanilla CSS with custom properties. Never use `@apply` in composition or block files. Tailwind utility classes are used directly in templates.

## Design Tokens

Tokens are stored as JSON in `resources/design-tokens/`:

| File | Contents |
|---|---|
| `colors.json` | dark, light, primary, neutral-50..950, error, success, warning, info |
| `spacing.json` | 3xs, 2xs, xs, s, m, l, xl, 2xl, 3xl, 4xl + fluid combos (e.g. s-l) |
| `text-sizes.json` | step--3 through step-8 (fluid clamp values) |
| `text-leading.json` | flat, fine, standard, loose |
| `text-weights.json` | normal/400, medium/500, bold/700 |
| `fonts.json` | base (sans), serif, mono |
| `viewports.json` | min/max clamp range (330–1280), breakpoints |

### Token Pipeline

1. Edit JSON files in `resources/design-tokens/`
2. `scripts/css-utils/create-theme.ts` generates `resources/css/theme.css`
3. Vite plugin (`plugins/theme.ts`) watches token files and auto-regenerates during `npm run dev`
4. Manual regeneration: `npm run theme`

### Token Naming in CSS / Tailwind

Tokens are registered as Tailwind 4 theme variables using `--` namespaces:

- Spacing: `--spacing-s`, `--spacing-m-l` → utility classes `p-s`, `gap-m-l`
- Text size: `--text-step-0`, `--text-step--2` → utility class `text-step-0`, `text-step--2` (double dash for negatives)
- Colors: `--color-primary`, `--color-neutral-200` → `text-primary`, `bg-neutral-200`
- Leading: `--leading-fine` → `leading-fine`
- Weights: `--font-weight-bold` → `font-bold`
- Fonts: `--font-base` → `font-base`

## CSS Directories

### Global (`resources/css/global/`)
- `reset.css` — Piccalilli modern reset (no Tailwind preflight)
- `fonts.css` — font-face declarations
- `variables.css` — CSS custom properties (gutter, strokes, radii, focus styles, transitions)
- `global-styles.css` — low-specificity element styles (headings, links, forms, tables, etc.)

### Compositions (`resources/css/compositions/`)
Layout primitives that control spatial relationships:
- `flow` — vertical rhythm via `> * + *` with `--flow-space`
- `cluster` — horizontal grouping with flex-wrap and gap
- `repel` — space-between flex layout
- `wrapper` — max-width container with inline padding
- `grid` — CSS grid with named layouts (`data-layout="thirds"`)
- `sidebar` — sidebar + main content layout
- `switcher` — horizontal-to-vertical at threshold
- `fluid-grid` — auto-fill grid with `span-full`/`span-content` utilities

### Blocks (`resources/css/blocks/`)
Styled components:
- `page-builder.css` — page builder section spacing
- `buttons.css` — `.button` and `.button-inline` styles
- `prose.css` — rich text content styling (sets `--flow-space` contextually)
- `skip-links.css` — accessibility skip navigation

### Utilities (`resources/css/utilities/`)
- `region.css` — vertical padding sections
- `hyphens.css` — hyphenation control
- `wrap.css` — text-wrap utilities
- `visually-hidden.css` — screen-reader-only

## Flow & Prose Pattern

The **flow composition** applies `margin-block-start: var(--flow-space, 1em)` to direct children via `> * + *`. It never sets `--flow-space` itself.

The **prose block** sets `--flow-space` contextually for different elements (headings get more space, list items get less). Prose never applies margin directly — it relies on flow.

## Compositions in Templates

Templates use CUBE compositions instead of raw Tailwind utilities where appropriate:
- `cluster` — nav links, button groups, footer links
- `repel` — header, footer outer wrapper
- `grid[data-layout="thirds"]` — card layouts
- `flow` — vertical rhythm in page-builder and prose content
- `wrapper` — max-width + inline padding container

## Ejected Vendor Views

These Peak plugin views have been ejected and restyled with CUBE tokens:
- `resources/views/vendor/statamic-peak-tools/components/_pagination.antlers.html`
- `resources/views/vendor/statamic-peak-tools/components/_toolbar.antlers.html`
- `resources/views/vendor/statamic-peak-tools/navigation/_skip_links.antlers.html`
- `resources/views/vendor/statamic-peak-tools/snippets/_noscript.antlers.html`
- `resources/views/vendor/statamic-peak-seo/components/_consent_banner.antlers.html`

## Important Technical Notes

- `@utility` directives CANNOT be nested inside CSS `@layer` imports — they must stay at the top level in `site.css`
- `@media screen(md)` doesn't work in Tailwind 4 — use `@media (width >= theme(--breakpoint-md))`
- Custom spacing values used in utilities (e.g. `--spacing-fluid-grid-gap`) must be declared in the `@theme` block, not just `:root`
- `npm run theme` regenerates theme.css from tokens (automatic during `npm run dev`)
- The `@tailwindcss/forms` plugin is active — it styles form inputs but does NOT style `<button>` elements

## Coding Preferences

- Prefer CUBE CSS compositions over raw Tailwind utility chains for layout
- Keep templates clean and readable — avoid arbitrary property hacks like `[&>*]:mb-4`
- Use design token values (e.g. `p-s`, `gap-m`, `text-step-1`) instead of raw numbers
- Consult before any bigger refactoring
