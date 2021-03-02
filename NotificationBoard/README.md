# IPSymcon Notification Board

[![PHPModule](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![IP-Symcon is awesome!](https://img.shields.io/badge/IP--Symcon-5.5-blue.svg)](https://www.symcon.de)

Modul zum einbinden verschiedener Benachrichtigungsmodule (SMS, E-Mail etc.), die an und abgeschaltet werden können.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Beispiele](#5-beispiele)  

## 1. Funktionsumfang

Mit dem Modul kann man sich Info oder Status-Nachrichten aus Scripten senden lassen, die über verschieden eingebundene Module zur Kommunikation (Webfront, SMS, E-Mail, Telegram etc.) versendet werden. Die Kommunikationswege sind pro Nachricht über eine HTML-Box oder ein PopUp Modul ein- und ausschaltbar. Die Empfänger und Kommunikationswege werden im Konfigurationsformular hinterlegt.

Das Modul was zur Kommunikation eingebunden wird, muss über eine Funktion aufgerufen werden können. 
Beispiel z.B. IP-Symcon SMTP_SendMail: SMTP_SendMail (integer $InstanzID, string $Betreff, string $Text)

Zum Versenden einer Nachricht, baut man die Funktion STNB_SendToNotify mit den Übergabeparametern in sein gewünschtes Skript ein. Danach wird zu dem Betreff (z.B. Spülmaschine, Homematic Service Meldung), ein entsprechendes DummyModul angelegt, wo sich darunter die Kommunikationswege umschalten lassen. Wenn nun die Funktion im Scipt aufgerufen wird, wird die Nachricht nur über den ausgewählten Weg versendet.

Die Variablen und Instanzten können im Formular unsichtbar geschaltet werden.

In der Datei "run_NotifyBoard", können eigene Funktionen für andere Versandwege hinterlegt werden, die über das Modul angesprochen werden.

Überischt nur Dummy Instanzen
![Uebersicht](img/Uebersicht_NotifyBoard.png?raw=true)

Übersicht HTML Box
![Uebersicht2](img/Uebersicht_NotifyBoard2.png?raw=true)

Übersicht PupUp Modul
![Uebersicht3](img/Uebersicht_NotifyBoard3.png?raw=true)

## 2. Voraussetzungen

 - IPS 5.5
 - Mindestens ein Kommunikationsweg (Webfront, SMS, E-Mail, Telegram etc.)
 - Verschachtelung im WebFront Editor sollte aktiviert sein, damit man alles sieht

## 3. Installation

### a. Modul hinzufügen

Über das Module Control folgende URL hinzufügen: `https://github.com/Housemann/NotificationBoard`
Danach eine neue Instanz hinzufügen und nach Notification Board suchen und dieses installieren.
Zu dem Modul werden drei Skripte angelegt. Die Skripte "Aktionsskript und run_NotifyBoard" werden zwingend benötigt. Das Skript "VorlageSendToNotify" dient als Vorlage zum  senden einer Nachricht. Dieses kann später verschoben oder gelöscht werden.

### b. Modul konfigurieren

Nach der Installation öffnet sich das Formular, wo man Instanzen zur Kommunikation hinterlegen kann. Am Anfang werden automatisch drei Instanzen hinzugefügt. Wenn diese bei euch existieren, wird automatisch die ObjektId hinterlegt. Zum einen wird E-Mail, zum anderen zwei mal das Webfront (Benachrichtigung PopUp und Notification) hinzugefügt. Wenn diese nicht benötigt werden, kann man sie einfach raus löschen.

#### Variablen anlegen und bei bedarf unsichtbar machen

Im unteren Bereich vom Modul können die Variablen bei Bedarf angelegt werden. Die Variable für den Betreff (Benachrichtigung für...) sollte zwingend angelegt werden, da ansonsten nicht im PopUp oder der HtmlBox zwischen den Werten gewechselt werden kann. Die Erstellung der Html Box oder des PopUps kann wahlweise erfolgen.

![VarAnlegen](img/VarAnlegen.png?raw=true)

Hier können die einzelnen Variablen, dass PopUp Modul oder die Instanzen unsichtbar gemacht werden.

![Unsichtbar](img/Unsichtbar.png?raw=true)


#### WebHook User und Password hinterlegen

Im unteren Bereich muss für die HTML Box ein WebHook konfiguriert werden, damit das umschalten die entsprechende Variable klappt.

![WebHook](img/WebHook.png?raw=true)


#### Hinzufügen Instanz

Zum hinzufügen einer neuen Instanz unter der Liste auf "Hinzufügen" klicken.

![InstanzHinzufuegen](img/InstanzHinzufuegen.png?raw=true)

Im Fenster was sich öffnet dann die InstanzId, Benachrichtigungsweg und Empfänger hinterlegen. 
  - InstanzId -- Hier muss die InstanzId zu einem Kommunikations-Modul hinterlegt werden (SMS, E-Mail, Webfront, Telegtam etc.).
  - Benachrichtigungsweg -- Das ist der Name um nachher im Skript "run_NotifyBoard" in der Case Bedingung unterschieden werden kann (bereits hinterlegt sind die Instanzen die am Anfang im Formular geladen wurden).
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
  $_IPS['StatusCreateDummy'];
```
Diese kann man dann in eigenen Funktionen oder Funktionen von Modulen übergeben. 

Hier eine kurze Beschreibung, welcher Parameter für was steht.

```php
"notifyWayName"         // Name für CASE-Bedingung (Benachrichtigungsweg SMS, Mail etc.) worübr im RunScript gesendet werden soll
"NotificationSubject"   // Name der DummyInstanz wofür die Nachricht ist (Müllabfuhr, Klingel, ServiceMedlung)
"InstanceId"            // InstanceId für Benachrichtigungsweg (wenn im Formular hinterlegt wird die InstanzId übergeben vom entsprechenden Modul)
"NotifyType"            // Information / Warnung / Alarm / Aufgabe
"Message"               // Nachricht
"Receiver"              // Empfänger (wird aus dem Formular ausgelesen)
"ExpirationTime"        // Ablaufzeit wann Nachricht auf gelesen gesetzt werden soll (noch nicht in Gebrauch)
"NotifyIcon"            // Icons aus IP Symcon (https://www.symcon.de/service/dokumentation/komponenten/icons/)
"MediaID"               // ID zum Medien Objekt in IPS   -- Wird automatisch gefüllt je nachdem was in STNB_SendToNotify() übergeben wird. ***
"AttachmentPath"        // Pfad zum Medien / Dateiobjekt -- Wird automatisch gefüllt je nachdem was in STNB_SendToNotify() übergeben wird. ***
"String1"               // String zur freien verwendung
"String2"               // String zur freien verwendung
"String3"               // String zur freien verwendung
"StatusCreateDummy"     // Rückgabe ob ein Dummy erstellt wurde um ggf. eine andere Aktion anzustoßen (HilfsVariable)
```


*** Wenn in der Funktion STNB_SendToNotify() eine MedienId im $Attachment übergeben wird, wird diese genommen sofern sie existiert. Wird ein Pfad hinterlgegt, wird dieser genommen. Bei der MedienId bekommt man im Parameter $_IPS['MediaID'] und $_IPS['AttachmentPath'] einen Wert zurück.


![Erklaerungrun_NotifyBoard](img/Erklaerungrun_NotifyBoard.png?raw=true)

#### String1 / String2 / String3

Die drei String Variablen sind zur freien Verwendung falls man noch irgendwas mit übergebeben möchte. In meinem Fall fülle ich $String1 mit dem Ende der Instanznamen von meinen Telegram Modulen. Hier habe ich mehrere angelegt, um in einem Chat unterschiedliche Nachrichten von Verschiedenen Bots zu bekommen. Daher trage ich im Konfigurator zu Telegram auch keine InstanzId ein, weil diese in meiner Funktion über den Namen ($String1) gesucht wird.

Beispiel:
```php
STNB_SendToNotify(
     $InstanceId            = IPS_GetInstanceListByModuleID ("{CD0C7974-6044-1795-4F88-9829021D2858}")[0]
    ,$NotificationSubject   = "Spülmaschine"
    ,$NotifyType            = "alarm"
    ,$NotifyIcon            = "IPS"
    ,$Message               = "Das ist eine vorlage"
    ,$Attachment            = ""
    ,$String1               = "Telegrammessenger_FileBot"
    ,$String2               = ""
    ,$String3               = ""
```

![BeispielTelegram1](img/BeispielTelegram1.png?raw=true)

Im Skript "run_NotifyBoard", übergebe ich im $String1 den Namen des Telegram-Moduls an eine eigene Funktion im Skript. In der Funktion suche ich dann über den Modulnamen das Modul an das ich bei Telegram senden will.
```php
case "Telegram":
  TelegramSenden($String1, $Receiver, $NotificationSubject, $Message, $AttachmentPath);
  break;
```


#### Skript VorlageSendToNotify.ips.php ausführen zum anlegen der neuen Instanz

Wenn man die ersten Instanzen hinzugefügt hat, kann man das Scirpt wo sich die Funktion zum Aufruf befindet abändern und starten.
```php
 print_r(
  STNB_SendToNotify(
       $InstanceId            = IPS_GetInstanceListByModuleID ("{CD0C7974-6044-1795-4F88-9829021D2858}")[0] ## ID von der Notify Instanz
      ,$NotificationSubject   = "Spülmaschine"
      ,$NotifyType            = "alarm"
      ,$NotifyIcon            = "IPS"
      ,$Message               = "Das ist eine vorlage"
      ,$Attachment            = "" ## MedienId oder Pfad
      ,$String1               = "" ## String zur freien verwendung
      ,$String2               = "" ## String zur freien verwendung
      ,$String3               = "" ## String zur freien verwendung
  ));
```

Das print_r() dient nur zur Ausgabe der Rückgabe. Beim ersten Aufruf hat das Array noch nicht alle werte, da erst eine neue Instanz erstellt wurde. Später wird das Array mit den Übergabewerten und den Kommunikationswegen gefüllt.  
```php
Array
(
    [0] => Array
        (
            [StatusRunScript] => 1
            [notifyWayName] => 
            [NotificationSubject] => Create_Spülmaschine
            [InstanceId] => 
            [NotifyType] => 
            [Message] => 
            [Receiver] => 
            [ExpirationTime] => 
            [NotifyIcon] => 
            [MediaID] => 
            [AttachmentPath] => 
            [String1] => 
            [String2] => 
            [String3] => 
            [StatusCreateDummy] => 1
        )

)
```

Nun wurde im Objektbaum im Modul eine neue Instanz mit dem Namen "Spülmaschine" angelegt.

![ErsteInstanz](img/ErsteInstanz.png?raw=true)


#### Erste Nachricht senden und empfangen

Nun kann man im WebFront für die erste Nachricht z.B. das WebFront SendNotification einschalten.

![ErsteNachrichtEinschalten](img/ErsteNachrichtEinschalten.png?raw=true)

Danach fürt man noch mal das Skript "VorlageSendToNotify.ips.php" aus. In meinem Fall sehe ich nun eine Benachrichtigung oben rechts im WebFront.

![ErsteNachrichtEmpfangen](img/ErsteNachrichtEmpfangen.png?raw=true)

Für ein PopUp schalten wir das PopUp ein und führen das Skript "VorlageSendToNotify.ips.php" erneut aus.

![ErsteNachrichtEmpfangen2](img/ErsteNachrichtEmpfangen2.png?raw=true)

Nun sieht man auch, das dass Array mit werten gefüllt wird die als Rückgabne dienen.

```php
Array
(
    [0] => Array
        (
            [StatusRunScript] => 1
            [notifyWayName] => WebFront SendNotification
            [NotificationSubject] => Spülmaschine
            [InstanceId] => 40366
            [NotifyType] => ips
            [Message] => Das ist eine vorlage
            [Receiver] => 
            [ExpirationTime] => 86400
            [NotifyIcon] => Camera
            [MediaID] => 
            [AttachmentPath] => 
            [String1] => 
            [String2] => 
            [String3] => 
            [StatusCreateDummy] => 0
        )

    [1] => Array
        (
            [StatusRunScript] => 1
            [notifyWayName] => WebFront PopUp
            [NotificationSubject] => Spülmaschine
            [InstanceId] => 40366
            [NotifyType] => ips
            [Message] => Das ist eine vorlage
            [Receiver] => 
            [ExpirationTime] => 86400
            [NotifyIcon] => Camera
            [MediaID] => 
            [AttachmentPath] => 
            [String1] => 
            [String2] => 
            [String3] => 
            [StatusCreateDummy] => 0
        )
)
```



## 4. Funktionsreferenz

Diese Funktion in alle benötigten Skripte einfügen worüber Ihr eine Benachrichtigung senden wollt. Die Werte ab "NotificationSubject" sind nach belieben selber anzupassen.

```php
STNB_SendToNotify(
     $instanceid            = 23913 ## ID von der Notify Instanz
    ,$NotificationSubject   = "Spülmaschine"
    ,$NotifyType            = "alarm"
    ,$NotifyIcon            = "IPS"
    ,$Message               = "Das ist eine vorlage"
    ,$Attachment            = "" ## MedienId oder Pfad
    ,$String1               = "" ## String zur freien verwendung
    ,$String2               = "" ## String zur freien verwendung
    ,$String3               = "" ## String zur freien verwendung
);
```


## 5. Beispiele
