# RatingStar.de Seal for WordPress

Embed your [RatingStar](https://ratingstar.de) seal and Google review stars
(rich snippets) into any WordPress site.

Published on WordPress.org as
[RatingStar.de Seal](https://wordpress.org/plugins/ratingstar-de-seal/).

## Features

- **Settings page** — store your RatingStar profile slug and API key once
  under *Settings → RatingStar*; the slug is verified against your live profile.
- **Seal widget in nine variants** — round seal and banner seal (static), plus
  the live widgets profile card, rating bar, overall rating, quote, carousel,
  review wall and footer bar; via the `[ratingstar]` shortcode or the
  **RatingStar Seal** block.
- **Site-wide seal** — floating profile card or footer bar on every page.
- **Google review stars** — server-side `LocalBusiness` JSON-LD with
  `AggregateRating` on the front page, key-based and cached for 6 hours.

## Requirements

- WordPress 6.0 or newer
- PHP 8.1 or newer

## Installation

1. Upload the `ratingstar-de-seal` folder to `wp-content/plugins/`, or install the ZIP
   via *Plugins → Add New → Upload Plugin*.
2. Activate **RatingStar.de Seal** under *Plugins*.
3. Open *Settings → RatingStar* and enter your profile slug and embed key.

## Usage

Place the seal with the shortcode `[ratingstar variant="profile-card"]` or the
**RatingStar Seal** block (pick the variant in the sidebar). Variants:
`seal-circle`, `seal-circle-banner`, `profile-card`, `bar`, `hero`, `quote`,
`carousel`, `wall`, `footer-bar`.

Appearance attributes from the embed generator in your RatingStar portal go
straight into the shortcode, e.g. `[ratingstar variant="carousel" car-count="4"
theme="dark"]`, or into the block's "Embed attributes" field. Keep **Google
review stars** enabled under *Settings → RatingStar* to also output the rating
as server-side JSON-LD on your front page.

## Development

The plugin is self-contained and loads without a build step — drop the folder
into any WordPress install's `wp-content/plugins/` directory to test.

For a release, `bin/build.sh` produces an installable, dev-file-free ZIP.
Pushing a `v*` tag triggers the GitHub Action that deploys that version to the
WordPress.org plugin SVN (requires the `SVN_USERNAME` / `SVN_PASSWORD`
repository secrets).

## License

[GPL-2.0-or-later](LICENSE).
