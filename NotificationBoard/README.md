# IPSymcon Notification Board

[![PHPModule](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![IP-Symcon is awesome!](https://img.shields.io/badge/IP--Symcon-5.5-blue.svg)](https://www.symcon.de)

Modul zum einbinden verschiedener Benachrichtigungen die an und abgeschaltet werden können.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Mit dem Modul kann man sich Nachrichten in Scripten, über verschieden eingebundene Module zur Kommunikation (Webfront, SMS, E-Mail, Telegram etc.) senden lassen. Die Kommunikationswege sind pro Nachricht über eine HTML-Box und / oder ein PopUp Modul ein und ausschaltbar. Die Empfänger und Kommunikationswege werden im Formular hinterlegt.

Zum Versenden einer Nachricht, baut man die Funktion mit den Übergabeparametern in sein gewünschtes Skript ein. Danach wird zu dem Betreff (z.B. Spülmaschine, Homematic Service Meldung), ein entsprechendes DummyModul angelegt, wo sich die Kommunikationswege schalten lassen. Wenn nun die Funktion im Scipt aufgerufen wird, wird nur über den ausgewählten Weg die Nachricht versandt.

Die Variablen und Instanzten können im Formular unsichtbar geschaltet werden.

In der Datei "run_NotifyBoard", können eigene Funktionen für andere Versandwege hinterlegt werden, die über die Funktion vom Modul angesprochen werden.

Überischt nur Dummy Instanzen
![Uebersicht](img/Uebersicht_NotifyBoard.png?raw=true)

Übersicht HTML Box
![Uebersicht2](img/Uebersicht_NotifyBoard2.png?raw=true)

Übersicht PupUp Modul
![Uebersicht3](img/Uebersicht_NotifyBoard3.png?raw=true)

## 2. Voraussetzungen

 - IPS 5.5
 - Mindestens ein Kommunikationsweg (Webfront, SMS, E-Mail, Telegram etc.)

## 3. Installation

### a. Modul hinzufügen

Über das Module Control folgende URL hinzufügen: `https://github.com/Housemann/NotificationBoard`
Danach eine neue Instanz hinzufügen und nach Notification Board suchen und dieses installieren.
Es wird das Modul mit drei Scripten angelegt. Die Scripte "Aktionsscript und run_NotifyBoard" werden zwingend benötigt. Das Skript "VorlageSendToNotify" dient als Vorlage zum anlegen und senden einer Nachricht.

### b. Modul konfigurieren

Nach der Installation öffnet sich das Formular, wo man Instanzen zur Kommunikation hinterlegen kann. Am Anfang werden automatisch drei Instanzen hinzugefügt. Zum einen E-Mail, zum anderen zwei Webfronts zur Benachrichtigung über ein PopUp oder eine Notification im Browser. Wenn es nicht benötigt wird, kann man diese einfach raus löschen. 

#### Hinzufügen Instanz

Zum hinzufügen einer neuen Instanz unter der Liste auf "Hinzufügen" klicken.

![InstanzHinzufuegen](img/InstanzHinzufuegen.png?raw=true)

Im Fenster was sich öffnet dann die InstanzId, Benachrichtigungsweg und Empfänger hinterlegen. 
  - InstanzId -- Hier muss die InstanzId zu einem Kommunikations-Modul hinterlegt werden (SMS, E-Mail, Webfront, Telegtam etc.).
  - Benachrichtigungsweg -- Das ist der Name um nachher im Script "run_NotifyBoard" in der Case Bedingung unterschieden werden kann.
  - Empfänger -- Hier muss je nachdem was man anspricht die E-Mail Adressen (mit ; getrennt) oder eine HandyNummer oder Telegram ChatId rein. Für das Webfront kann es leer bleiben.

![InstanzWebFrontPopUp](img/InstanzWebFrontPopUp.png?raw=true)

Danach im Modul auf Übernehmen klicken.

![Uebernehmen](img/Uebernehmen.png?raw=true)

#### Vorlage-Script VorlageSendToNotify.ips.php ausführen

Wenn man die ersten Instanzen hinzugefügt hat, kann man das Scirpt wo sich die Funktion zum Aufruf befindet abändern und starten.
```php
 print_r(
  STNB_SendToNotify(
       $instanceid            = 23913 ## ID von der Notify Instanz
      ,$NotificationSubject   = "Spülmaschiene"
      ,$NotifyType            = "alarm"
      ,$NotifyIcon            = "IPS"
      ,$Message               = "Das ist eine vorlage"
      ,$Attachment            = "" ## MedienId oder Pfad
      ,$String1               = "" ## String zur freien verwendung
      ,$String2               = "" ## String zur freien verwendung
      ,$String3               = "" ## String zur freien verwendung
  ));
```

Das print_r() dient nur zur Ausgabe der Rückgabe. Beim ersten Aufruf ist das Array noch leer. Später wird das Array mit den Übergabewerten und den Kommunikationswegen gefüllt.  

Nun wurde im Objektbaum im Modul eine neue Instanz mit dem Namen "Spülmaschiene" angelegt.

![ErsteInstanz](img/ErsteInstanz.png?raw=true)





