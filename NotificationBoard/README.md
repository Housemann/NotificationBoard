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

Mit dem Modul kann man sich eigene Nachrichten, über verschieden eingebundene Module zur Kommunikation (SMS, E-Mail, Telegram etc.) senden lassen. Die Kommunikationswege sind pro Nachricht über eine HTML-Box und / oder ein PopUp Modul ein und ausschaltbar. 

Zum Versenden einer Nachricht, baut man die Funktion mit den Übergabeparametern in sein gewünschtes Skript ein. Danach wird zu dem Betreff, ein entsprechendes DummyModul angelegt, wo sich die Kommunikationswege schalten lassen.

![Uebersicht](img/Uebersicht_NotifyBoard.png?raw=true)
