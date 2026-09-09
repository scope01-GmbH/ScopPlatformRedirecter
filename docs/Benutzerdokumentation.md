# SEO Redirect Plugin: Benutzerdokumentation

Diese Anleitung beschreibt, wie Sie mit dem SEO Redirect Plugin von scope01 Weiterleitungen in der
Shopware Administration anlegen und verwalten. Der Schwerpunkt liegt auf der Auswahl des Ziels und der
neuen sprach- und kanalspezifischen SEO-URL-Auswahl.

## Grundbegriffe

Eine Weiterleitung besteht aus:

- **Quell-URL:** der Pfad, den der Besucher aufruft (zum Beispiel `/altes-produkt`).
- **Ziel:** wohin weitergeleitet wird. Das Ziel kann eine frei eingegebene URL sein oder, mit der
  Premium-Erweiterung, ein Produkt oder eine Kategorie.
- **HTTP-Code:** `301` (dauerhaft) oder `302` (temporär).
- **Verkaufskanal:** optional. Ohne Auswahl gilt die Weiterleitung fuer alle Verkaufskanaele.

## Weiterleitung anlegen

1. Öffnen Sie in der Administration das Modul **SEO Redirect** und klicken Sie auf **Weiterleitung hinzufügen**.
2. Tragen Sie die **Quell-URL** ein.
3. Wählen Sie unter **Ziel** eine der folgenden Optionen:
   - **Manuelle URL:** geben Sie die Ziel-URL direkt ein.
   - **Produkt** oder **Kategorie** (Premium): wählen Sie das Zielobjekt aus. Die Ziel-URL wird
     automatisch aus der aktuellen SEO-URL des Objekts ermittelt und bleibt dynamisch aktuell.
4. Optional: schränken Sie die Weiterleitung über **Verkaufskanal** auf einen Kanal ein.
5. Speichern Sie.

## Neu: SEO-URL und Sprache auswählen

Wenn Sie als Ziel ein Produkt oder eine Kategorie wählen, erscheint ein zweites Auswahlfeld
**SEO-URL / Sprache**. Darin sind alle vorhandenen SEO-URLs des Zielobjekts aufgelistet, jeweils pro
**Sprache und Verkaufskanal**. Ein Eintrag sieht zum Beispiel so aus:

```
Storefront DE  ·  Deutsch  ·  /Wohnzimmer
Storefront EN  ·  English  ·  /en/Living-room
```

Wählen Sie den Eintrag in der gewünschten Sprache. Ab dann leitet die Weiterleitung auf genau diese
Sprachvariante der Ziel-URL weiter. Ohne Auswahl verwendet das Plugin wie bisher die Standardsprache.

Hintergrund: Vor dieser Erweiterung wurde beim Ziel Produkt/Kategorie immer die SEO-URL der
Standardsprache verwendet. Dadurch konnte eine englische Seite faelschlicherweise auf die deutsche
Zielseite zeigen. Mit der neuen Auswahl bestimmen Sie die Zielsprache selbst.

Die Ziel-URL wird weiterhin **dynamisch** aufgelöst: Ändert sich die SEO-URL des Produkts oder der
Kategorie später, folgt die Weiterleitung automatisch der neuen SEO-URL derselben Sprache.

## Zusammenspiel von Verkaufskanal und Sprache

SEO-URLs existieren in Shopware je nach Kombination aus Sprache und Verkaufskanal. Beachten Sie daher:

- Wählen Sie im zweiten Feld eine **kanalspezifische** SEO-URL (zum Beispiel "Storefront EN · English"),
  wird der Verkaufskanal der Weiterleitung automatisch auf diesen Kanal gesetzt. Die Weiterleitung
  greift dann nur in diesem Kanal.
- Beim Auflösen zur Laufzeit sucht das Plugin die SEO-URL der gewählten Sprache, die für den aufrufenden
  Verkaufskanal gültig ist (kanalspezifisch oder kanalunabhängig).

### Beispiel: Weiterleitung ohne Verkaufskanal, Zielsprache Deutsch

Angenommen, eine Weiterleitung ist **auf keinen Verkaufskanal** eingeschränkt (gilt also fuer alle
Kanaele) und die Zielsprache ist **Deutsch**. Es gibt einen weiteren Verkaufskanal, der **nur Englisch**
führt.

Dann passiert Folgendes, wenn ein Besucher die Quell-URL im englischen Kanal aufruft:

- Das Plugin sucht die **deutsche** SEO-URL des Ziels, die für den englischen Kanal gültig ist.
- Führt der englische Kanal kein Deutsch, existiert dort keine passende deutsche SEO-URL. Das Plugin
  findet kein gültiges Ziel und führt bewusst **keine** Weiterleitung aus. Der Besucher landet auf der
  normalen 404-Seite, statt auf eine nicht vorhandene Seite umgeleitet zu werden.
- Gibt es hingegen eine kanalunabhängige deutsche SEO-URL, wird diese verwendet.

**Empfehlung:** Legen Sie sprachspezifische Weiterleitungen möglichst für den Verkaufskanal an, der diese
Sprache tatsächlich führt. Am einfachsten gelingt das, indem Sie im zweiten Auswahlfeld die
kanalspezifische SEO-URL wählen. Der passende Verkaufskanal wird dann automatisch gesetzt.

## Verhalten bei gelöschtem Zielobjekt

Wird ein verknüpftes Produkt oder eine verknüpfte Kategorie gelöscht, friert das Plugin die zuletzt
bekannte SEO-URL **in der gewählten Sprache** als feste Ziel-URL ein. Die Weiterleitung bleibt so
funktionsfähig, auch wenn das Objekt nicht mehr existiert.

## Import und Export

Weiterleitungen können über die Standard-Import/Export-Funktion von Shopware importiert und exportiert
werden. Beim erneuten Import wird eine bestehende Weiterleitung anhand der Quell-URL aktualisiert, statt
ein Duplikat anzulegen.
