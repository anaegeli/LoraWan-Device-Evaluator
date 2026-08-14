# LoRaWAN Device Evaluator

Frameworkfreie PHP-Webanwendung zur Übertragung von LoRaWAN-Fieldtester-Messungen auf kalibrierte Gerätetypen.

## Funktionen

- dynamische Gerätetypen und Messorte
- Fieldtester- und Gerätemessungen mit RSSI, SNR, SF und TX-Leistung
- gekoppelte Kalibrierungsmessungen über Paar-IDs
- robuste Kalibrierung mittels Median und Median Absolute Deviation
- TX-normalisierte Prognose für alle kalibrierten Gerätetypen
- Ergebnis `geeignet`, `grenzwertig` oder `ungeeignet`
- lokale Benutzer und Rollen `viewer`, `editor`, `admin`
- Anmeldung über OIDC oder SAML
- vollständiges Deployment innerhalb eines einzigen Plesk-Webroots

## Plattform

- PHP 8.2
- MySQL 5.6+
- Apache 2.4 mit `.htaccess`
- Composer

Die komplette hochzuladende Anwendung befindet sich in `public/`. Details stehen in [docs/DEPLOYMENT-PLESK.md](docs/DEPLOYMENT-PLESK.md).

## Berechnungsprinzip

Die Kalibrierung ermittelt aus Messpaaren den verbleibenden RSSI-Unterschied, nachdem die bekannte TX-Leistungsdifferenz entfernt wurde. Bei einer Standortprüfung wird dieser gerätespezifische Restwert auf die Fieldtester-Messung übertragen. Die Anwendung schätzt daraus den Geräte-RSSI und den SNR gegenüber dem am Standort gemessenen Rauschpegel. Die ausgewiesene Reserve enthält bereits einen Sicherheitsabzug aus der beobachteten Kalibrierungsstreuung.

Die Ausgabe ist eine technische Prognose und keine Empfangsgarantie. Montage, Antennenausrichtung, zeitliche Störungen, Gateway-Diversität und Downlink-Verfügbarkeit müssen in der Praxis zusätzlich berücksichtigt werden.
