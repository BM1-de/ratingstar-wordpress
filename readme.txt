=== RatingStar.de Seal ===
Contributors: phillipb
Tags: reviews, customer reviews, review widget, rich snippets, trust seal
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your customer reviews on your WordPress site: RatingStar seals, review widgets and Google review stars — via block, shortcode or site-wide.

== Description ==

[RatingStar](https://ratingstar.de) is the review tool for businesses: collect customer reviews, bundle reviews from Google and other platforms, and show them all on your own website. **RatingStar.de Seal** brings them into WordPress:

* A **seal widget** in nine variants — the static round seal and banner seal, plus the live widgets: profile card, trust bar, hero snippet, featured quote, carousel, wall of love and footer bar.
* A **site-wide seal**: show the floating profile card or the footer bar on every page — no theme edit, no per-page block.
* **Google review stars** — server-side JSON-LD (`LocalBusiness` with `AggregateRating`) so search engines can show star ratings for your site.
* A **static image mode**: the round seal, banner seal and profile card as a plain SVG image, linked to your profile — for contexts without JavaScript.

Enter your RatingStar profile slug once under *Settings → RatingStar*, then place the seal anywhere with the `[ratingstar]` shortcode or the **RatingStar Seal** block — or switch on the site-wide seal.

**Customising the look:** every embed accepts the appearance attributes produced by the embed generator in your RatingStar portal (for example `pc-color`, `car-count`, `footer-bar-bg`). Use them as shortcode attributes (`[ratingstar variant="carousel" car-count="4"]`), paste them into the block's "Embed attributes" field, or into the site-wide settings — seal.js validates the values.

**Why it stays fast and clean:**

* The seal script (`seal.js`) is loaded asynchronously and only on pages that actually contain a seal.
* The JSON-LD rating is fetched server-side — key-based when an API key is set, so it survives a profile rename — and cached for 6 hours; it is printed only on your front page.
* When the JSON-LD output is enabled, the seal's own snippet is suppressed so the `AggregateRating` is never duplicated.
* The static image is rendered and cached by ratingstar.de, with long-lived CDN and browser caching.

== Installation ==

1. Upload the `ratingstar-de-seal` folder to `/wp-content/plugins/`, or install the ZIP via *Plugins → Add New → Upload Plugin*.
2. Activate **RatingStar.de Seal** through the *Plugins* menu.
3. Go to *Settings → RatingStar* and enter your **profile slug** (the part after `ratingstar.de/t/`). The slug is verified against your live profile. Optionally add your **API key** (`rs_live_…`) for the rename-proof key-based endpoints.
4. Whitelist your domain in the RatingStar backend (tab "Auslieferung" / Delivery) and verify it via the `TXT _ratingstar.<domain>` DNS record — otherwise the seal data may be blocked with HTTP 403.
5. Place the seal on a page or post — `[ratingstar variant="profile-card"]` or the **RatingStar Seal** block — or enable the **site-wide seal** in the settings.

== Frequently Asked Questions ==

= Where do I find my profile slug? =

It is the last part of your public profile URL: `https://ratingstar.de/t/<slug>`. Enter just the `<slug>` part on the settings page.

= Which variants are there? =

The round seal and the banner seal are static and work on every plan. Profile card, trust bar, hero snippet, featured quote, carousel, wall of love and footer bar are live widgets and need a 4-star plan or higher — on lower plans seal.js shows the banner seal instead.

= How do I change colours, sizes and other options? =

Open the embed generator in your RatingStar portal, configure the widget and copy the resulting attributes into the shortcode, the block's "Embed attributes" field or the site-wide settings. The `data-` prefix may be included or left out; invalid values fall back silently.

= The seal does not appear. =

Make sure a profile slug is saved under *Settings → RatingStar* and that the page contains the `[ratingstar]` shortcode, the RatingStar Seal block or an enabled site-wide seal. Check that your domain is whitelisted and DNS-verified in the RatingStar backend. If you use a caching or "remove unused JavaScript" plugin, make sure `seal.js` from `ratingstar.de` is not blocked.

= Does this work with caching plugins? =

Yes. The rating data for the JSON-LD is cached server-side for 6 hours via a WordPress transient, so page caches stay light. The seal itself renders client-side from live data; the static image is cached by CDN and browser.

= Data protection / GDPR =

The seal widget loads `seal.js` and rating data from `ratingstar.de`, so visitors' browsers connect to that domain when a seal is shown. Mention this in your privacy policy and, if you use a consent solution, treat `ratingstar.de` accordingly. The server-side Google review stars (JSON-LD) do **not** require any client-side connection; the static image mode only requests a single image.

= Do I need an embed key? =

The seal works with your public profile slug alone. The API key (format `rs_live_…` from your RatingStar backend) is recommended for the Google review stars: it drives the rename-proof key-based endpoints, which keep working when your profile slug changes and are open on all plans.

== External services ==

This plugin connects to the **RatingStar** platform (https://ratingstar.de), a review collection and rating seal service operated by Baumgärtner Marketing GmbH. The connection is what the plugin is for: it displays your RatingStar seal with live rating data and outputs your rating as Google review stars. Without a RatingStar profile the plugin does nothing.

What is sent, and when:

* **Seal widget:** When a page containing a seal is shown, the visitor's browser loads `seal.js`, the seal styles and the rating data for the configured profile from ratingstar.de, and requests a tracking pixel that counts the seal impression (and, on click, the click). As with any web request, the visitor's IP address and user agent technically reach the server; the plugin itself transmits no names, e-mail addresses or other personal fields.
* **Static image mode:** The visitor's browser loads a single SVG image from ratingstar.de.
* **Google review stars (JSON-LD):** Your web server — not the visitor — fetches the rating summary from ratingstar.de (identified by your site URL in the user agent) and caches it for 6 hours. No visitor data is involved.
* **Settings:** When you save a changed profile slug, your web server verifies it against ratingstar.de once.

Service provider: Baumgärtner Marketing GmbH, Germany.

* Terms of service: https://ratingstar.de/agb
* Privacy policy: https://ratingstar.de/datenschutz

== Screenshots ==

1. Settings → RatingStar: profile connection, Google review stars and the site-wide seal.
2. The RatingStar Seal block with variant, placement and embed attributes.
3. The seal rendered on the front end.

== Changelog ==

= 1.0.2 =
* Clearer plugin description: RatingStar collects customer reviews, bundles
  reviews from Google and other platforms and shows them on your website.

= 1.0.1 =
* The German description and installation steps now come from the translation
  packages on translate.wordpress.org instead of being embedded in the readme.

= 1.0.0 =
* First public release.
* Seal widget in nine variants (shortcode + block) with the portal's per-embed appearance attributes.
* Site-wide seal: floating profile card or footer bar on every page.
* Server-side Google review stars: key-based `LocalBusiness` JSON-LD, cached for 6 hours, duplicate-free.
* Linked static image mode (SVG) for round seal, banner seal and profile card.
* Slug verification, configurable base origin, domain-whitelist hints.
* German translations (de_DE and de_DE_formal), including the block editor.

== Upgrade Notice ==

= 1.0.2 =
Documentation only — updated plugin description.

= 1.0.1 =
Documentation only — the German texts now ship as a translation package.

= 1.0.0 =
First public release.
