# Deutsche readme-Fassung für translate.wordpress.org
#
# Vorlage für das GlotPress-Sub-Projekt "Stable Readme" (de_DE) von
# ratingstar-de-seal. Nicht Teil der Distribution — languages/ steht in
# .distignore. Struktur folgt readme.txt: gleiche Reihenfolge, gleiche
# Sektionen. Beim Einpflegen Absatz für Absatz gegen die englischen Strings
# in GlotPress setzen. Nach der Freigabe kann der deutsche Übergangsblock in
# readme.txt raus.


== Kurzbeschreibung (Short description) ==

Bewertungssiegel und Google-Sterne (Rich Snippets) von RatingStar in
WordPress einbinden — per Block, Shortcode oder automatisch auf jeder Seite.


== Beschreibung (Description) ==

**RatingStar.de Seal** bringt die Bewertungen Ihres RatingStar-Profils auf
Ihre WordPress-Seite:

* Ein **Bewertungssiegel in neun Varianten** — statisches Rundsiegel und
  Banderole sowie die Live-Widgets: Profilkarte, Trust-Bar, Hero-Snippet,
  Zitat-Karte, Karussell, Wall of Love und Footer-Leiste.
* Ein **seitenweites Siegel**: schwebende Profilkarte oder Footer-Leiste auf
  jeder Seite — ohne Theme-Anpassung, ohne Block auf jeder einzelnen Seite.
* **Google-Sterne** — server-seitiges JSON-LD (`LocalBusiness` mit
  `AggregateRating`), damit Suchmaschinen Sternebewertungen zu Ihrer Seite
  anzeigen können.
* Ein **statischer Bildmodus**: Rundsiegel, Banderole und Profilkarte als
  reines SVG mit Link aufs Profil — für Umgebungen ohne JavaScript.

Tragen Sie Ihren RatingStar-Profil-Slug einmalig unter *Einstellungen →
RatingStar* ein und platzieren Sie das Siegel dann mit dem Shortcode
`[ratingstar]` oder dem Block **RatingStar Seal** — oder schalten Sie das
seitenweite Siegel ein.

**Aussehen anpassen:** Jede Einbindung akzeptiert die Gestaltungs-Attribute
aus dem Embed-Generator in Ihrem RatingStar-Portal (etwa `pc-color`,
`car-count`, `footer-bar-bg`). Nutzen Sie sie als Shortcode-Attribute
(`[ratingstar variant="carousel" car-count="4"]`), im Feld „Embed-Attribute"
des Blocks oder in den Einstellungen für das seitenweite Siegel — seal.js
prüft die Werte.

**Warum es schlank und schnell bleibt:**

* Das Siegel-Skript (`seal.js`) wird asynchron geladen, und nur auf Seiten,
  die tatsächlich ein Siegel enthalten.
* Die Bewertungsdaten fürs JSON-LD holt Ihr Server selbst — bei hinterlegtem
  API-Key über den key-basierten Endpunkt, der eine Profil-Umbenennung
  übersteht — und speichert sie 6 Stunden zwischen; ausgegeben wird das
  Markup nur auf Ihrer Startseite.
* Ist die JSON-LD-Ausgabe aktiv, unterdrückt das Siegel sein eigenes Snippet,
  damit die `AggregateRating` nie doppelt im Markup steht.
* Das statische Bild wird von ratingstar.de gerendert und mit langlebigem
  CDN- und Browser-Cache ausgeliefert.


== Installation ==

1. Plugin über *Plugins → Installieren* suchen und installieren — oder den
   Ordner `ratingstar-de-seal` nach `/wp-content/plugins/` hochladen bzw. das
   ZIP über *Plugins → Installieren → Plugin hochladen* einspielen.
2. **RatingStar.de Seal** unter *Plugins* aktivieren.
3. Unter *Einstellungen → RatingStar* den **Profil-Slug** eintragen (der Teil
   hinter `ratingstar.de/t/`). Der Slug wird gegen Ihr Live-Profil geprüft.
   Optional den **API-Key** (`rs_live_…`) für die umbenennungs-festen
   key-basierten Endpunkte hinterlegen.
4. Ihre Domain im RatingStar-Backend unter „Auslieferung" freischalten und per
   DNS-Eintrag `TXT _ratingstar.<domain>` bestätigen — sonst können die
   Siegel-Daten mit HTTP 403 blockiert werden.
5. Siegel auf einer Seite oder in einem Beitrag platzieren —
   `[ratingstar variant="profile-card"]` oder der Block **RatingStar Seal** —
   oder in den Einstellungen das **seitenweite Siegel** aktivieren.


== Häufige Fragen (FAQ) ==

= Wo finde ich meinen Profil-Slug? =

Es ist der letzte Teil Ihrer öffentlichen Profil-URL:
`https://ratingstar.de/t/<slug>`. Tragen Sie auf der Einstellungsseite nur den
`<slug>`-Teil ein.

= Welche Varianten gibt es? =

Rundsiegel und Banderole sind statisch und funktionieren in jedem Tarif.
Profilkarte, Trust-Bar, Hero-Snippet, Zitat-Karte, Karussell, Wall of Love und
Footer-Leiste sind Live-Widgets und brauchen mindestens den 4-Sterne-Tarif —
in niedrigeren Tarifen zeigt seal.js stattdessen die Banderole.

= Wie ändere ich Farben, Größen und andere Optionen? =

Öffnen Sie den Embed-Generator in Ihrem RatingStar-Portal, stellen Sie das
Widget ein und übernehmen Sie die erzeugten Attribute in den Shortcode, in das
Feld „Embed-Attribute" des Blocks oder in die Einstellungen für das
seitenweite Siegel. Das `data-`-Präfix dürfen Sie mitschreiben oder weglassen;
ungültige Werte fallen still auf den Standard zurück.

= Das Siegel erscheint nicht. =

Prüfen Sie, ob unter *Einstellungen → RatingStar* ein Profil-Slug gespeichert
ist und ob die Seite den Shortcode `[ratingstar]`, den Block RatingStar Seal
oder ein aktiviertes seitenweites Siegel enthält. Prüfen Sie außerdem, ob Ihre
Domain im RatingStar-Backend freigeschaltet und per DNS bestätigt ist. Wenn
Sie ein Caching-Plugin oder eine Funktion „ungenutztes JavaScript entfernen"
einsetzen, stellen Sie sicher, dass `seal.js` von `ratingstar.de` nicht
blockiert wird.

= Funktioniert das mit Caching-Plugins? =

Ja. Die Bewertungsdaten fürs JSON-LD werden server-seitig 6 Stunden lang in
einem WordPress-Transient zwischengespeichert, Ihre Seiten-Caches bleiben also
leicht. Das Siegel selbst rendert client-seitig aus Live-Daten; das statische
Bild wird von CDN und Browser zwischengespeichert.

= Datenschutz / DSGVO =

Das Siegel-Widget lädt `seal.js` und die Bewertungsdaten von `ratingstar.de` —
der Browser Ihrer Besucher verbindet sich also mit dieser Domain, sobald ein
Siegel angezeigt wird. Nennen Sie das in Ihrer Datenschutzerklärung und
behandeln Sie `ratingstar.de` entsprechend, falls Sie eine Consent-Lösung
einsetzen. Die server-seitigen Google-Sterne (JSON-LD) brauchen **keine**
Verbindung aus dem Browser; der statische Bildmodus fordert lediglich ein
einzelnes Bild an.

= Brauche ich einen Embed-Key? =

Das Siegel funktioniert allein mit Ihrem öffentlichen Profil-Slug. Für die
Google-Sterne empfehlen wir den API-Key (Format `rs_live_…` aus Ihrem
RatingStar-Backend): Er treibt die key-basierten Endpunkte, die auch nach
einer Umbenennung Ihres Profils weiterarbeiten und in jedem Tarif offen sind.


== Externe Dienste (External services) ==

Dieses Plugin verbindet sich mit der **RatingStar**-Plattform
(https://ratingstar.de), einem Dienst für Bewertungserfassung und
Bewertungssiegel der Baumgärtner Marketing GmbH. Diese Verbindung ist der
Zweck des Plugins: Sie zeigt Ihr RatingStar-Siegel mit Live-Bewertungsdaten
und gibt Ihre Bewertung als Google-Sterne aus. Ohne RatingStar-Profil tut das
Plugin nichts.

Was wann übertragen wird:

* **Siegel-Widget:** Wird eine Seite mit Siegel aufgerufen, lädt der Browser
  des Besuchers `seal.js`, die Siegel-Styles und die Bewertungsdaten des
  eingestellten Profils von ratingstar.de und fordert einen Zählpixel an, der
  die Einblendung (und beim Klick den Klick) zählt. Wie bei jeder
  Web-Anfrage erreichen IP-Adresse und User-Agent des Besuchers technisch den
  Server; das Plugin selbst überträgt keine Namen, E-Mail-Adressen oder andere
  personenbezogenen Felder.
* **Statischer Bildmodus:** Der Browser des Besuchers lädt ein einzelnes
  SVG-Bild von ratingstar.de.
* **Google-Sterne (JSON-LD):** Ihr Webserver — nicht der Besucher — ruft die
  Bewertungs-Zusammenfassung von ratingstar.de ab (erkennbar an Ihrer
  Website-URL im User-Agent) und speichert sie 6 Stunden zwischen. Dabei sind
  keine Besucherdaten im Spiel.
* **Einstellungen:** Wenn Sie einen geänderten Profil-Slug speichern, prüft
  Ihr Webserver ihn einmalig gegen ratingstar.de.

Diensteanbieter: Baumgärtner Marketing GmbH, Deutschland.

* AGB: https://ratingstar.de/agb
* Datenschutzerklärung: https://ratingstar.de/datenschutz


== Screenshots ==

1. Einstellungen → RatingStar: Profil-Verbindung, Google-Sterne und
   seitenweites Siegel.
2. Der Block RatingStar Seal mit Variante, Platzierung und Embed-Attributen.
3. Das Siegel im Frontend.


== Changelog ==

= 1.0.0 =
* Erste öffentliche Version.
* Bewertungssiegel in neun Varianten (Shortcode + Block) mit den
  Gestaltungs-Attributen aus dem Portal.
* Seitenweites Siegel: schwebende Profilkarte oder Footer-Leiste auf jeder
  Seite.
* Server-seitige Google-Sterne: key-basiertes `LocalBusiness`-JSON-LD,
  6 Stunden zwischengespeichert, ohne Dopplung.
* Verlinkter statischer Bildmodus (SVG) für Rundsiegel, Banderole und
  Profilkarte.
* Slug-Prüfung, konfigurierbare Basis-Adresse, Hinweise zur Domain-Freigabe.
* Deutsche Übersetzungen (de_DE und de_DE_formal), auch für den Block-Editor.


== Hinweis zum Update (Upgrade Notice) ==

= 1.0.0 =
Erste öffentliche Version.
