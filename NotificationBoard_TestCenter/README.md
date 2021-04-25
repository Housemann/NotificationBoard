# IPSymcon Notification Board Test Center

[![PHPModule](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![IP-Symcon is awesome!](https://img.shields.io/badge/IP--Symcon-5.5-blue.svg)](https://www.symcon.de)

Modul zum einbinden verschiedener Benachrichtigungsmodule (SMS, E-Mail etc.), die an und abgeschaltet werden können.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)

## 1. Funktionsumfang

Mit dem Modul kann man Testbenachrichtigungen über das Notification Board senden.

## 2. Voraussetzungen

 - IPS 5.5
 - Mindestens ein Kommunikationsweg (Webfront, SMS, E-Mail, Telegram etc.)
 - Verschachtelung im WebFront Editor sollte aktiviert sein, damit man alles sieht

## 3. Installation

### a. Modul hinzufügen

Über das Module Control folgende URL hinzufügen: `https://github.com/Housemann/NotificationBoard`
Danach eine neue Instanz hinzufügen und nach Notification Board Test Center suchen und dieses installieren.

### b. Modul konfigurieren

Nach der Installation öffnet sich das Formular, wo man als Instanz die ID vom  Notification Board hinterlegt.

In der Variable String Inhalt kann man z.B. wie folgt werde hinterlegen. Werte die mit einer Raute (#) anfangen, werden nicht berücksichtigt.

```php
"Channel":"IpSymcon"
"AlexaStumm":""
#"pushoverUrl":"https://www.google.de"
#"pushoverUrlName":"Google"
#"pushoverRetry":"20"
#"pushoverExpire":"3000"
#"pushoverContentType":"1"
#"pushoverSound":"gamelan"
```