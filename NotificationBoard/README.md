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
  - Benachrichtigungsweg -- Das ist der Name um nachher im Script "run_NotifyBoard" in der Case Bedingung unterschieden werden kann (bereits hinterlegt sind die Instanzen die am Anfang im Formular geladen wurden).
  - Empfänger -- Hier muss je nachdem was man anspricht die E-Mail Adressen (mit ; getrennt) oder eine HandyNummer oder Telegram ChatId rein. Für das Webfront kann es leer bleiben.

![InstanzWebFrontPopUp](img/InstanzWebFrontPopUp.png?raw=true)

Danach im Modul auf Übernehmen klicken.

![Uebernehmen](img/Uebernehmen.png?raw=true)



#### Hinzufügen weitere Instanz (z.B. SMS) und Anpassung Skript run_NotifyBoard

Ich füge hier nun ein SMS Modul hinzu und übernehme dieses im Modul...

![InstanzHinzufuegenSMS](img/InstanzHinzufuegenSMS.png?raw=true)

Danach muss im Skript run_NotifyBoard eine neue CASE Bedingung hinzugefügt werden.

![Caserun_NotifyBoard](img/Caserun_NotifyBoard.png?raw=true)

Nachdem der neue Kommunikationsweg hinterlegt wurde, steht dieser in allen Benachrichtigungen zur Verfügung. 

![WebFrontSMS](img/WebFrontSMS.png?raw=true)

Wenn man nun das Scirpt ausführt, bekommt man eine SMS an die hinterlegt Nummer.

![HandySMS](img/HandySMS.png?raw=true)





### c. run_NotifyBoard konfigurieren und anpassen

Das Skript bekommt aus dem Modul die Werte in die Übergabe-Parameter gesendet.

```php
  $_IPS['notifyWayName'];
  $_IPS['NotificationSubject'];
  $_IPS['InstanceId'];
  $_IPS['NotifyType'];
  $_IPS['Message'];
  $_IPS['Receiver'];
  $_IPS['ExpirationTime'];
  $_IPS['NotifyIcon'];
  $_IPS['MediaID'];
  $_IPS['AttachmentPath'];
  $_IPS['String1'];
  $_IPS['String2'];
  $_IPS['String3'];
```
Diese kann man dann in eigenen Funktionen oder Funktionen von Modulen übergeben. 

Hier eine kurze Beschreibung, welcher Parameter für was steht.

```php
"notifyWayName"         // Name für CASE-Bedingung (Benachrichtigungsweg SMS, Mail etc.) worübr im RunScript gesendet werden soll
"NotificationSubject"   // Name der DummyInstanz wofür die Nachricht ist (Müllabfuhr, Klingel, ServiceMedlung)
"InstanceId"            // InstanceId für Benachrichtigungsweg übergeben (wenn im Formular hinterlegt)
"NotifyType"            // Information / Warnung / Alarm / Aufgabe
"Message"               // Nachricht
"Receiver"              // Empfänger
"ExpirationTime"        // Ablaufzeit wann Nachricht auf gelesen gesetzt werden soll (noch nicht in Gebrauch)
"NotifyIcon"            // Icons aus IP Symcon (https://www.symcon.de/service/dokumentation/komponenten/icons/)
"MediaID"               // ID zum Medien Objekt in IPS   -- Wird automatisch gefüllt je nachdem was in STNB_SendToNotify() übergeben wird. ***
"AttachmentPath"        // Pfad zum Medien / Dateiobjekt -- Wird automatisch gefüllt je nachdem was in STNB_SendToNotify() übergeben wird. ***
"String1"               // String zur freien verwendung
"String2"               // String zur freien verwendung
"String3"               // String zur freien verwendung
```


*** Wenn in der Funktion STNB_SendToNotify() eine MedienId im $Attachment übergeben wird, wird diese genommen sofern sie existiert. Wird ein Pfad hinterlgegt, wird dieser genommen. Bei der MedienId bekommt man im Parameter $_IPS['MediaID'] und $_IPS['AttachmentPath'] einen Wert zurück.


![Erklaerungrun_NotifyBoard](img/Erklaerungrun_NotifyBoard.png?raw=true)



#### Script VorlageSendToNotify.ips.php ausführen zum anlegen der neuen Instanz

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


#### Erste Nachricht senden und empfangen

Nun kann man im WebFront für die erste Nachricht z.B. das WebFront SendNotification einschalten.

![ErsteNachrichtEinschalten](img/ErsteNachrichtEinschalten.png?raw=true)

Danach fürt man noch mal das Skript "VorlageSendToNotify.ips.php" aus. In meinem Fall sehe ich nun eine Benachrichtigung oben rechts im WebFront.

![ErsteNachrichtEmpfangen](img/ErsteNachrichtEmpfangen.png?raw=true)

Für ein PopUp schalten wir das PopUp ein und führen das Script "VorlageSendToNotify.ips.php" erneut aus.

![ErsteNachrichtEmpfangen2](img/ErsteNachrichtEmpfangen2.png?raw=true)



















## 4. Funktionsreferenz

Diese Funktion in alle benötigten Scripte einfügen worüber Ihr eine Benachrichtigung senden wollt. Die Werte ab "NotificationSubject" sind nach belieben selber anzupassen.

```php
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
);
``` 
