# Croissant

A [CUBE CSS](https://cube.fyi/) starter kit for [Statamic](https://statamic.com/), built on [Peak](https://peak.1902.studio/) foundations.

The name is a play on Peak — *Gipfel* in German — which also happens to be the German word for croissant.

## What is this?

Croissant takes the excellent [Peak starter kit](https://github.com/studio1902/statamic-peak) by [Rob de Kort](https://github.com/robdekort) and replaces its CSS architecture with the [CUBE CSS](https://cube.fyi/) methodology. Tailwind CSS 4 remains as the utility layer, but design decisions are driven by a token-based system with explicit compositions, blocks, and utilities.

The CUBE CSS integration is based on [stvrhm/cube-boilerplate](https://github.com/stvrhm/cube-boilerplate), itself a fork of [Set Creative Studio's cube-boilerplate](https://github.com/Set-Creative-Studio/cube-boilerplate).

## Key differences from Peak

- **Design token pipeline** — JSON tokens for spacing, colors, typography, viewports. A build script generates Tailwind-compatible CSS custom properties.
- **Fluid type scale** — Powered by [Utopia](https://utopia.fyi/), with steps from -3 to 8.
- **CUBE compositions** — Flow, cluster, grid, sidebar, switcher, wrapper, repel, and fluid grid as reusable layout primitives.
- **Piccalilli reset** — Replaces Tailwind's preflight with a modern, opinionated CSS reset.
- **CSS cascade layers** — Explicit `@layer` ordering for theme, base, components, and utilities.
- **No `@apply` in CSS files** — Compositions and blocks use vanilla CSS with custom properties.
- **Ejected vendor views** — Peak Tools and Peak SEO views restyled to use the token system.

## What's kept from Peak

- Page builder blueprints and fieldsets
- Alpine.js patterns (mobile nav, consent banner, toolbar)
- SEO and tools addons (`statamic-peak-seo`, `statamic-peak-tools`)
- Form handling, email templates, and content scaffold
- SVG icon set

## Installation

```bash
statamic new my-site klickreflex/croissant
```

After installation:

```bash
npm install
npm run dev
```

The Vite dev server automatically generates `theme.css` from your design tokens on startup and regenerates it whenever you change a token JSON file. A standalone `npm run theme` script is available for CI or one-off generation outside of Vite.

## Credits

- [Rob de Kort / Studio 1902](https://github.com/robdekort) — Peak starter kit
- [Set Creative Studio](https://github.com/Set-Creative-Studio) — Original CUBE boilerplate
- [Andy Bell / Piccalilli](https://piccalil.li/) — CUBE CSS methodology, Every Layout compositions, modern CSS reset
- [Utopia](https://utopia.fyi/) — Fluid type and space calculator

## License

Croissant is licensed under the [GNU General Public License v3.0](LICENSE). Statamic itself is commercial software and has its [own license](https://statamic.dev/licensing).
