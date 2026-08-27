---
name: growth-loop-design-style
description: Apply the Growth Loop visual identity to Laravel/Tailwind interfaces and Markdown documentation. Use for branded components, tokens, logo placement, or visual consistency within this workspace; do not use to overwrite an unrelated product brand.
---

# Growth Loop Design Style

Use this skill when a screen, Laravel view, React/Inertia interface, README, or study guide is intended to share the Growth Loop identity. The canonical logo is [`assets/growth-loop-logo.png`](assets/growth-loop-logo.png), a transparent PNG concept asset.

## Brand character

Growth Loop represents deliberate practice, reflection, and compounding progress. The interface should feel clear, encouraging, and technically capable: use a calm light surface, strong hierarchy, practical spacing, and color with a clear purpose. Avoid a school-like, overly playful, or generic startup appearance.

## Tokens

Use solid colors for UI. The logo itself may retain its generated tonal variation; do not add gradients to ordinary components merely to imitate it.

| Role | Token | Value | Use |
| --- | --- | --- | --- |
| Primary | `growth-plum` | `#4C1D95` | Navigation, primary actions, important headings |
| Accent | `growth-magenta` | `#C026D3` | Active controls and highlights; never use for body text on white |
| Warm signal | `growth-apricot` | `#FB923C` | Badges, callouts, progress, and limited emphasis |
| Soft support | `growth-lilac` | `#DDD6FE` | Subtle panels, selected rows, focus surroundings |
| Ink | `growth-ink` | `#24103D` | Long-form and UI text |
| Surface | `growth-surface` | `#FCFAFF` | Page background |

For Tailwind CSS 4, define the color variables in the main stylesheet:

```css
@theme {
  --color-growth-plum: #4c1d95;
  --color-growth-magenta: #c026d3;
  --color-growth-apricot: #fb923c;
  --color-growth-lilac: #ddd6fe;
  --color-growth-ink: #24103d;
  --color-growth-surface: #fcfaff;
}
```

For Tailwind CSS 3, extend `theme.colors` with the same six names and values. Do not replace existing semantic tokens or a project’s established palette wholesale; map Growth Loop colors to its existing primary, accent, and surface roles where appropriate.

## Component recommendations

- Pages: use `bg-growth-surface text-growth-ink`, a `max-w-7xl` container, and a 24–32px desktop rhythm. Use 16px horizontal padding on small screens.
- Primary buttons: `bg-growth-plum text-white hover:bg-violet-900 focus-visible:outline-growth-magenta`. Keep labels action-first and preserve a 44px minimum touch target.
- Secondary buttons: white or lilac surface, plum border and text. Do not compete with the primary action.
- Cards: white background, a restrained `border-growth-lilac`, `rounded-2xl`, and `shadow-sm`. Use a 4px plum top border only for featured or active content.
- Forms: visible labels, `focus-visible:ring-2 focus-visible:ring-growth-magenta`, and explicit error text. Do not use color as the only validation signal.
- Status and data: reserve apricot for attention or progress. Use the application’s existing semantic success, warning, and danger colors for states; the brand colors are not a substitute for status meaning.
- Charts: use plum as the anchor series, magenta as the comparison series, apricot as the highlight, and accessible non-brand colors where more series are needed. Always provide labels or a table alternative.

## Logo application

- Keep the supplied PNG on a white, surface, or deep-plum background with generous clear space equal to at least one small orbit-dot width around the mark.
- Use the full mark at 72px or larger in document mastheads and landing-page hero areas. Use 24–40px in navigation only after checking that the smaller details remain legible.
- Pair it with a plain text wordmark in the project’s existing sans-serif UI font; do not recreate, stretch, recolor, crop, outline, or place text inside the mark.
- In Markdown, use a centered HTML image only where the renderer allows it. In ordinary README files, a linked image near the title is enough. Use descriptive alt text such as `Growth Loop logo`; decorative repetition should use empty alt text.
- Store a local copy of the logo inside each branded project’s `public/images/` or `docs/images/` directory and reference it by a relative path. Never point project documentation at the private source path in this repository.

## Applying the identity safely

1. Inspect the existing Tailwind version, component patterns, and documentation renderer first.
2. Add or map tokens before changing views. Reuse existing layout, form, table, and alert primitives.
3. Apply logo and color changes to the user-facing surface only. Keep APIs, operational dashboards, external-service UIs, and unrelated product brands unchanged unless explicitly requested.
4. Validate responsive states, keyboard focus, contrast, and the production front-end build. Check Markdown image links from the rendered repository root.

The PNG is a concept asset. For a public or production identity, have the selected mark redrawn as a clean, licensed SVG before using it as a favicon, app icon, or high-resolution print asset.
