# v1.0.0

- Erstes Release des Plugins

# v1.0.1

- Fehlerbehebung Weiterleitung bei mehreren SEO-Urls

# v1.0.2

- Weiterleitung bei nicht existierender Quellurl

# v1.0.3

- Erlaube Weiterleitung der gelöschten Urls

# v1.0.4

- Schließe store api von redirects aus

# v1.0.5

- Fehlerbehebung Weiterleitungen mit Paramatern in den Links

# v1.1.0

- Importieren und Exportieren hinzugefügt
- Deaktivieren von Weiterleitungen hinzugefügt

# v1.1.1

- Fehlerbehebung csv Dateierkennung bei Import
- Fehlerbehebung Endlosweiterleitung bei mehreren SEO-Urls zum gleichen Produkt

# v1.1.2

- Fehlerbehebung Weiterleitung von alter SEO-Url

# v1.1.3

- Fehlerbehebung Weiterleitung in Shopware Versionen unter 6.4.0.0

# v1.1.4

- Fehlerbehebung Endlosweiterleitung wenn sich Quell- und Ziel-URL nur in der Groß- und Kleinschreibung unterscheiden.

# v1.1.5

- Fehlerbehebung Weiterleitung von einer absoluten URL mit einem virtuellen Path

# v1.2.0

- Neue Option "Query Parameter ignorieren" hinzugefügt

# v2.0.0

- Update auf Shopware v6.5
- Fehlerbehebung Error im Log bei erfolgreicher Weiterleitung

# v2.1.0

- Möglichkeit der Übernahme von Query Parametern an die Ziel URL hinzugefügt
- Logikfehler bei der Validierung von Importdateien behoben

# v2.2.0

- Plugin-Konfiguration zur Unterstützung für spezielle Zeichen (wie Umlaute) in der Quell URL hinzugefügt
- Fehlerbehebung Error beim Erstellen/Editieren einer Weiterleitung mit leerer Quell-/Ziel-URL

# v2.3.0

- Verkaufskanalwahl zu Weiterleitungen hinzugefügt

# v2.3.1

- Beheben von Paging für Redirect List

# v2.3.2

- Beheben von PSR-4 Standard 

# v3.0.0

- Update auf Shopware v6.6
- 
# v3.0.1

- Redirect Erstellung behoben

# v3.1.0

- Import / Export Funktion benutzt nun den standard Shopware Import/Export

# v3.1.1

- Migration fixen

# v3.1.2

- Inline Edit Validation wurde eingebaut um leere source/target URL zu vermeiden

# v3.1.3

- Fehlerbehebung Sales Channel Auflösung mit Parametern

# v4.0.0

- Shopware 6.7 Kompatibilität

# v4.0.1

- Shopware 6.7.1 Kompatibilität

# v4.0.2

- Behebung eines Migrationsfehlers, der die Neuinstallation des Plugins verhinderte.

# v4.0.3

- Validierung beim Import erweitert
- Duplikaterstellung bei Re-Import verhindert

# v4.1.3

- Umsetzung Suchfunktion für IAP
- Primärschlüssel zur Tabelle hinzugefügt

# v4.1.4

- Behebt Problem mit der Validierung bei der Einstellung der Verkaufskanäle auf "Alle"
- Behebt Problem bei der Migration, wenn der Primärschlüssel bereits existiert

# v4.1.5

- Behebt Problem mit der Anzeige der Verkaufskanäle in der Weiterleitungsliste

# v4.1.6

- Hinzufügen von Erstellungsdatum zur Redirect Liste und Sortierungsmöglichkeit danach

# v4.1.7

- Validierung für verbotene URLs hinzugefügt und "/" als Quell-URL verboten

# v4.2.0

- Automatische Erstellung von Weiterleitungen beim Deaktivieren von Produkten hinzugefügt (Premium-Funktion)

# v4.3.0

- 404-Fehler werden geloggt und können direkt mit einer Weiterleitung verknüpft werden (Premium-Funktion)

# v4.4.0

- Automatische Erstellung von Weiterleitungen beim Löschen von Produkten hinzugefügt, mit konfigurierbarem HTTP-Statuscode (Premium-Funktion)

# v4.5.0

- Weiterleitungen können jetzt mit einem Produkt oder einer Kategorie verknüpft werden; die Ziel-URL wird dynamisch aus der aktuellen SEO-URL der verknüpften Entität aufgelöst (Premium-Funktion)
- Wird das verknüpfte Produkt oder die Kategorie gelöscht, wird die letzte SEO-URL automatisch in die Weiterleitung eingefroren, damit sie weiterhin funktioniert
- 404-Log "Weiterleitung erstellen"-Modal nutzt nun denselben Entity-Link-Mechanismus (kein automatisches Vorbefüllen der URL mehr)

# v4.5.1

- Spalten-Migrationen sind nun wiederholbar und schlagen beim erneuten Ausführen nicht mehr fehl

# v4.5.2

- Automatische Erstellung von Weiterleitungen für nicht vorhandene Sales-Channel-Domains beheben