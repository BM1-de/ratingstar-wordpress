# RatingStar for WordPress

Embed your [RatingStar](https://ratingstar.de) seal and Google review stars
(rich snippets) into any WordPress site.

> **Status:** early development — see the repository issues for the roadmap.

## Features

- **Settings page** — store your RatingStar profile slug and embed key once
  under *Settings → RatingStar*; the slug is verified against your live profile.
- **Seal widget** *(planned)* — render the RatingStar seal (round badge,
  banderole, floating badge) via the `[ratingstar]` shortcode or a Gutenberg block.
- **Google stars** *(planned)* — server-side JSON-LD (`AggregateRating`) in the
  page head, cached via a transient, toggleable per page.

## Requirements

- WordPress 6.0 or newer
- PHP 8.1 or newer

## Installation

1. Upload the `ratingstar` folder to `wp-content/plugins/`, or install the ZIP
   via *Plugins → Add New → Upload Plugin*.
2. Activate **RatingStar** under *Plugins*.
3. Open *Settings → RatingStar* and enter your profile slug and embed key.

## Usage

Place the seal with the shortcode `[ratingstar variant="banner"]` (variants:
`banner`, `circle`, `card`) or the **RatingStar Seal** block (pick the variant
in the sidebar). Keep **Google review stars** enabled under *Settings →
RatingStar* to also output the rating as server-side JSON-LD on your front page.

## Development

The plugin is self-contained and loads without a build step — drop the folder
into any WordPress install's `wp-content/plugins/` directory to test.

For a release, `bin/build.sh` produces an installable, dev-file-free ZIP.
Pushing a `v*` tag triggers the GitHub Action that deploys that version to the
WordPress.org plugin SVN (requires the `SVN_USERNAME` / `SVN_PASSWORD`
repository secrets).

## License

[GPL-2.0-or-later](LICENSE).
