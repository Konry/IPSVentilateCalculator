# Ventilate Calculator
Der Lüftungsberechner soll helfen zu entscheiden, wann in den jeweiligen Räumen gelüftet werden soll.

Dabei werden verschieden Standard Parameter genutzt, zum Berechnen der absoluten Luftfeuchtigkeit. Es soll helfen automatisierte Lüftungen durchzuführen.

### Inhaltsverzeichnis

1. [Funktionsumfang](#Beschreibung1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Berechnung ob Lüften ja oder nein
* Berechnung auf einer Scala von 0 - 100 zum Bedarf der Lüftung

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0

### 3. Software-Installation

* Über den Module Store das 'Ventilate Calculator'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen
  * https://github.com/Konry/IPSVentilateCalculator/tree/main/IPSVentilateCalculator

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'VentilateCalculator'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Temperatur Innen         |Quellvariable für Temperatur Innen, notwendig
Luftfeuchtigkeit Innen         |Quellvariable für Luftfeuchtigkeit Innen, notwendig
Temperatur Außen         |Quellvariable für Temperatur Außen, notwendig
Luftfeuchtigkeit Außen         | Quellvariable für Luftfeuchtigkeit Außen, notwendig
Luftdruck         | Quellvariable für Luftdruck Außen, notwendig
Prüf Intervall         | Zeitangabe in Sekunden zum Anstoßen der neuen Berechnung
         |
         |
         |

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ |---------| ------------
Aktiv  | boolean | Aktiviert oder deaktiviert die Berechnung
Lüften | boolean | Gibt an ob gelüftet werden darf, basiert nur auf der absoluten Luftfeuchtigkeit

#### Profile

Name   | Typ
------ | -------
VC.Ventilate       | boolean
       |

### 6. WebFront

Über das WebFront oder in den mobilen Apps werden Werte angezeigt. Das gesammte Modul kann über das WebFront oder die mobilen App de-/aktiviert werden.

### 7. PHP-Befehlsreferenz

`boolean VC_BeispielFunktion(integer $InstanzID);`
Erklärung der Funktion.

Beispiel:
`VC_BeispielFunktion(12345);`