=== RatingStar ===
Contributors: phillipb
Tags: reviews, ratings, rich snippets, schema, seal
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed your RatingStar seal and Google review stars (rich snippets) into WordPress — set up in minutes with a shortcode or block.

== Description ==

**RatingStar** brings the reviews of your [RatingStar](https://ratingstar.de) profile into your WordPress site:

* A **seal widget** (banner, circle or card) that shows your live rating and review count.
* **Google review stars** — server-side JSON-LD (`AggregateRating`) so search engines can show star ratings for your site.

Enter your RatingStar profile slug once under *Settings → RatingStar*, then place the seal anywhere with the `[ratingstar]` shortcode or the **RatingStar Seal** block. The rating data is loaded live from ratingstar.de.

**Why it stays fast and clean:**

* The seal script (`seal.js`) is loaded only on pages that actually contain a seal.
* The JSON-LD rating is fetched server-side and cached for 6 hours, and is printed only on your front page and only when real reviews exist.
* When the JSON-LD output is enabled, the seal's own snippet is suppressed so the `AggregateRating` is never duplicated.

== Installation ==

1. Upload the `ratingstar` folder to `/wp-content/plugins/`, or install the ZIP via *Plugins → Add New → Upload Plugin*.
2. Activate **RatingStar** through the *Plugins* menu.
3. Go to *Settings → RatingStar* and enter your **profile slug** (e.g. `stadtwerk-tauberfranken`, the part after `ratingstar.de/t/`). The slug is verified against your live profile.
4. Place the seal on a page or post:
   * Shortcode: `[ratingstar variant="banner"]` (variants: `banner`, `circle`, `card`).
   * Block: add the **RatingStar Seal** block and pick a variant in the sidebar.
5. Optionally keep **Google review stars** enabled to output the rating as JSON-LD on your front page.

== Frequently Asked Questions ==

= Where do I find my profile slug? =

It is the last part of your public profile URL: `https://ratingstar.de/t/<slug>`. Enter just the `<slug>` part on the settings page.

= The seal does not appear. =

Make sure a profile slug is saved under *Settings → RatingStar* and that the page contains the `[ratingstar]` shortcode or the RatingStar Seal block. If you use a caching or "remove unused JavaScript" plugin, make sure `seal.js` from `ratingstar.de` is not blocked or deferred in a way that prevents it from running.

= Does this work with caching plugins? =

Yes. The rating data for the JSON-LD is cached server-side for 6 hours via a WordPress transient, so page caches stay light. The seal itself renders client-side from live data.

= Data protection / GDPR =

The seal widget loads `seal.js` and rating data from `ratingstar.de`, so visitors' browsers connect to that domain when a seal is shown. Mention this in your privacy policy and, if you use a consent solution, treat `ratingstar.de` accordingly. The server-side Google review stars (JSON-LD) do **not** require any client-side connection.

= Do I need an embed key? =

No. The seal and the Google review stars work with your public profile slug. The optional embed key field is reserved for future features.

== Screenshots ==

1. Settings → RatingStar: profile slug, embed key and the Google review stars toggle.
2. The RatingStar Seal block with its variant selector in the editor.
3. The seal rendered on the front end.

== Changelog ==

= 0.1.0 =
* Initial release.
* Settings page for the RatingStar profile slug and embed key, with live slug verification.
* `[ratingstar]` shortcode and **RatingStar Seal** block (banner, circle, card); `seal.js` loaded only when a seal is present.
* Server-side Google review stars (JSON-LD `AggregateRating`) on the front page, cached for 6 hours, with the seal's own snippet suppressed to avoid duplicates.

== Upgrade Notice ==

= 0.1.0 =
First release.
