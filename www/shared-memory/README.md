# Auswahl des Verfahrens zur Datenübertragung in einem spezifischen Projekt

## Einleitung
Das zu entwickelnde Projekt besitzt mehrere unabhängige Bestandteile (Prozesse):
* Einlesen der Betriebsdaten von Sensoren (PYTHON-Prozess)
* Präsentation der Betriebsdaten im Web (NGINX-Prozess mit Hilfe vieler sequenziellen PHP-FPM Prozesse)
* Konfiguration der Systemumgebung in der Web-Anwendung (PYTHON-Prozess)
* Bestimmung von Steuerungsdaten für die Sensoren

## Aufgabenstellung aus dem Projekt
* Übertragung der Sensor-Daten an die PHP-FPM Prozesse des WebServers.
* Übertragung Konfigurationsdaten an die Sensor-Steuerung
* Übertragung der berechneten Steuerungsdaten an die Sensor-Steuerung

## Überlegung
Hier stellt sich die Frage, wie die Daten von einem Prozess zum anderen Prozess übertragen werden könnte. Prinziziell stehen folgende Mechanismen zur Interprozesskommunikation (IPC) zur Verfügung:
* Shared Memory (gemeinsamer Speicher)
* Pipes (Datenleitungen)
* Nachrichtenübermittlung (Message Queues, Sockets)
* Remote Procedure Calls (RPCs)
* Synchronisationsmechanismen wie Semaphoren und Mutexe

Weiterhin stehen folgende Arten der Kommunikation zur Diskussion:
* Speicherung der Daten in einer Datei und Lesen der Datei von einem zweiten Prozess.
* Speichern und Zurücklesen in einer Datenbank
* Kommunikation über MQTT, REST oder OPC-UA

## Randbedingungen
* Der Prozess zum Einlesen der Betriebsdaten kann immer wieder unterbrochen werden. In diesem Fall müssen die bereits vorhandenen Betriebsdate präsentiert werden.
* Beim ersten Start der Web-Anwendung stehen keine Konfigurationsdaten zur Verfügung, der Prozess zum Einlesen der Betriebsdaten läuft noch nicht, er wird erst durch die Web-Anwendung als User-Prozess gestartet
* 

## Lösungsansätze
Im ersten Lösungsansatz wurden die Betriebsdaten in einer Datei gespeichert und von den PHP-FPM Prozessen ausgelesen.  
Im zweiten Lösungsansatz wurde MQTT implementiert.  
Als dritter Lösungsansatz wurde die Datenübertragung über Shared-Memory untersucht. Hierbei wurde die Implementierung über System-V erfolgreich ungesetzt. Die Posix-Implementierung wurde nicht beendet.

## Lösung über System-V
[Python-Script zum Schreiben der Daten](./sysV_out.py)  
[Python-Script zum Lesen der Daten](./sysV_in.py)  
[PHP-Script zum Schreiben der Daten](./sysV_out.php)  
[PHP-Script zum Lesen der Daten](./sysV_in.php)  
