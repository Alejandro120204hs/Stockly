# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Owners and managers of businesses that hold physical inventory, across any industry (e.g. liquor stores, clothing stores, grocery/comestibles, and other retail or trade businesses). Two operating profiles, both in scope from the start:
- The owner of a single small business, operating the system directly.
- Managers/administrators at chains or multi-branch businesses, overseeing inventory across locations.

## Product Purpose

Stockly is a global inventory and business-administration system: it lets any business, regardless of industry, organize its stock by custom categories, automatically detect inventory movements (entries/exits) in real time, record sales and compute real profit (not just gross revenue), and verify received electronic invoices while issuing its own.

## Positioning

Most inventory tools are built around one vertical's assumptions. Stockly's mechanism is industry-agnostic categorization combined with automatic, real-time movement detection and real-profit accounting (net, not gross) plus native electronic invoicing (verify incoming, issue outgoing) — one system a liquor store, a clothing store, or a grocery business can each configure to their own categories rather than adapting to someone else's template.

## Operating Context

Day-to-day use centers on: defining custom inventory categories per business (e.g. aguardiente/whisky/ron for a liquor store; shirts/pants/footwear for a clothing store); the system detecting stock entries and exits automatically as they happen; recording sales and reviewing real profit; and verifying/issuing electronic invoices as part of normal sales and purchasing operations.

## Capabilities and Constraints

Confirmed capabilities:
- Custom, business-defined inventory categorization (not industry-specific presets).
- Automatic, real-time detection of inventory entries and exits.
- Sales recording with real profit calculation (net margin, not just gross income).
- Verification of received electronic invoices.
- Issuance of the business's own electronic invoices.

Constraints / current state:
- Pre-product: only the marketing landing page exists today (Laravel 12 + Blade, vanilla CSS/JS for the landing per explicit project decision). No inventory/sales/invoicing backend, models, or dashboard have been built yet — the only domain model in the codebase is the default `User`.
- The project scaffold is Laravel Breeze (auth scaffolding present: login/register/profile). Tailwind is present in the scaffold's own dependencies but was explicitly excluded from the landing page in favor of hand-written CSS/JS.
- No pricing/plan model is decided. The landing's "Comenzar gratis" CTA is provisional marketing copy, not a confirmed free plan or trial — future work must not treat it as a real pricing fact.

## Brand Commitments

- Name: Stockly.
- Light-theme palette, used with defined roles (do not introduce other tones):
  - Surface (white) `#FFFFFF` — cards, modals, inputs, panels.
  - Background (smoke) `#F2F0ED` — page background, alternating rows.
  - Text (slate) `#1E2D3D` — headings, sidebar, topbar.
  - Action (sage) `#4A7C6F` — buttons, links, active nav.
  - Accent (sand) `#C9B99A` — badges, logo, separators.
  - Muted (mist) `#8C9BAB` — labels, placeholders, metadata.
- Typography on the landing: Fraunces (display/headings) + Work Sans (body), chosen deliberately to avoid a generic/default look.
- Visual identity direction: intentionally avoids generic-AI patterns (no purple-blue gradients, no default glassmorphism, no repeated identical cards, no default Inter font).

## Evidence on Hand

None. The liquor store / clothing store / grocery examples used on the landing page are illustrative use cases, not real customers, testimonials, or case studies. Stockly has no real customers, usage data, or pricing model yet — future work must not fabricate testimonials, logos, benchmarks, or pricing as if they were real.

## Product Principles

1. Industry-agnostic by default: every feature and every piece of copy must hold up for at least a liquor store, a clothing store, and a grocery business simultaneously — never assume one vertical's terms or workflow.
2. Real profit over vanity metrics: surfacing net margin, not just revenue or stock counts, is the product's core value claim and should be treated as load-bearing, not decorative.
3. Automatic over manual: movement detection and record-keeping should happen without requiring the user to re-enter what the system can already observe.
4. Small business and multi-branch chains are both first-class: do not design or write copy that implicitly excludes either operating profile.
5. Don't outrun the truth: this is a pre-product marketing site — capabilities, pricing, and evidence must stay honest about what exists today versus what's planned.
