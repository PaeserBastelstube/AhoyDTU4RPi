# Auswahl des Verfahrens zur Datenübertragung in einem spezifischen Projekt

## Einleitung
Das zu entwickelnde Projekt besitzt die folgenden Bestandteile, welche als mehrere unabhängige Prozesse agieren:
1. Konfiguration und Überwachung der System- u. Projektparameter in der Web-GUI (NGINX-Prozess mit Hilfe vieler sequenziellen PHP-FPM Prozessen) 
1. Steuerung der Sensoren durch Einlesen der Betriebsdaten, Überwachung, Fehlererkennung und Logging (Python-Prozess)
1. Darstellung der Sensor-Betriebsdaten und Log-Files in der Web-GUI (NGINX-Prozess mit Hilfe vieler sequenziellen PHP-FPM Prozesse) 
1. Konfiguration der Sensor-Betriebsparameter in der Web-GUI (PHP-FPM Prozesse)
1. Weiterleiten spezifischer Betriebsparameter (Konfiguration) an die Sensoren (Python Prozess)

## Aufgaben zur Datenübertragung im Projekt
* Übertragung statischer Konfigurationsdaten aus der Web-GUI (PHP-FPM Prozesse) an die Sensor-Steuerung (Python-Prozess)
* Übertragung der Sensor-Betriebsdaten (Python-Prozess) an die Web-GUI (PHP-FPM Prozesse).
* Automatisierte Steuerung spezifischer Betriebsabläufe als dynamische Konfiguration (bidirektionale Kommunikation aller beteiligter Prozesse)

## Randbedingungen
* Beim ersten Start der Web-GUI stehen (noch) keine Konfigurationsdaten zur Verfügung, der Prozess zum Einlesen der Betriebsdaten läuft nicht. Dieser Prozess wird erst durch die Web-GUI als User-Prozess gestartet.
* Der Prozess zum Einlesen der Sensor-Betriebsdaten läuft unabhängig aller anderen Prozesse als User-Prozess. Bei besonderen Konstellationen und des Nachts wird er angehalten, in diesen Fällen müssen die vorhandenen (veralteten) Betriebsdaten in der Web-GUI angezeigt werden.
* Die Web-GUI ist kein laufender System-Prozess. Ein interaktiver Benutzer ruft über seinen Browser diese Web-GUI auf. Hierbei werden PHP-FPM Prozesse vom NGINX-WebServer gestart, diese Prozesse haben nur eine kurze Laufzeit (Anteile einer Sekunde).

## Überlegung
Es stellt sich die Frage, wie die Daten von einem Prozess zum anderen Prozess übertragen werden könnte.
Prinzipiell stehen folgende Mechanismen zur Interprozesskommunikation (IPC) zur Verfügung:
* Shared Memory (gemeinsamer Speicher)
* Pipes (Datenleitungen)
* Nachrichtenübermittlung (Message Queues, Sockets)
* Remote Procedure Calls (RPCs)
* Synchronisationsmechanismen wie Semaphoren und Mutexe

Weiterhin stehen folgende Arten der Kommunikation und zur Datenübertragung zur Diskussion:
* Speicherung der Daten in einer Datei und Lesen der Datei von einem zweiten Prozess.
* Speichern und Zurücklesen in einer Datenbank
* Kommunikation über MQTT, REST oder OPC-UA

## Lösungsansätze
Im ersten Lösungsansatz wurden die Betriebsdaten in einer Datei gespeichert und von den PHP-FPM Prozessen ausgelesen.  
Im zweiten Lösungsansatz wurde MQTT implementiert.  
Als dritter Lösungsansatz wurde die Datenübertragung über Shared-Memory untersucht. Hierbei wurde die Implementierung über System-V erfolgreich umgesetzt. Die Posix-Implementierung wurde nicht beendet.

## Lösung über System-V
[Python-Script zum Schreiben der Daten](./sysV_out.py)  
[Python-Script zum Lesen der Daten](./sysV_in.py)  
[PHP-Script zum Schreiben der Daten](./sysV_out.php)  
[PHP-Script zum Lesen der Daten](./sysV_in.php)  
