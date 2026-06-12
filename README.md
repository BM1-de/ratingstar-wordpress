# RatingStar für WordPress

Bindet das RatingStar-Siegel und die Google-Sterne (Rich Snippets) einer
RatingStar-Filiale (https://ratingstar.de) in eine WordPress-Website ein.

**Status: in Entwicklung** — Funktionsumfang und Roadmap stehen in den
Issues dieses Repos.

## Geplanter Funktionsumfang

- Einstellungsseite: RatingStar-Profil-Slug + Embed-Key hinterlegen
- Siegel-Widget (Rundsiegel, Banderole, Floating-Badge) als Shortcode
  `[ratingstar]` und Gutenberg-Block
- Google-Sterne: serverseitiges JSON-LD über den offiziellen
  RatingStar-PHP-Snippet-Endpunkt (`/seal/k/<key>.json`)

## Entwicklung

Dieses Repo enthält **nur das Plugin**. Die lokale Test-Instanz liegt
drumherum: DDEV-Projekt `ratingstar-plugin-wordpress`
(https://ratingstar-plugin-wordpress.ddev.site, Login `admin`),
das Plugin ist dort unter `wp-content/plugins/ratingstar` ausgecheckt.

```bash
cd ~/Sites/Dev/ratingstar-plugin-wordpress
ddev start
ddev wp plugin activate ratingstar
```
