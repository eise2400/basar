<?php

// Jede Kasse muss beim Server (Webserver) registriert werden, damit diese mit dem Server syncen kann.
// Dabei wird auch ein Benutzer/Kennwort angelegt und eine Kassennummer vergeben.
// 
// Beim Start Deines Python-Servers für die Kasse (Sync-Server) fragt dieser entweder nach einer 
// Datendatei oder nach Zugangsdaten.
// 
// 1. Die Datendatei jeder Kasse lässt sich vom Webserver direkt herunterladen.
//    Sie enthält ausschließlich SQL Statements. Enthalten sind:
//    die eigenen Zugangsdaten (ein Eintrag in der registers), den settings, 
//    users (das Kennwort lasse ich weg), items sowie die leeren Tabellen
//    scans (für Scandaten aller Kassen), sales (für in Verkäufe umgesetzte scans)
//    und dem View itemsales (aus items und sales).
//
// 2. Alternativ können Zugangsdaten eingegeben werden. Dies sind URL, Benutzer, 
//    Kennwort und Kassen-ID. 
//    
// Der Sync-Server sollte bei jedem Neustart prüfen, ob die registers lokal existiert
// und einen Eintrag enthält. Ist dies der Fall, die Werte anzeigen und nach Bestätigung
// das Skript unten ausführen. Dieses Skript ist nur für den Abgleich mit dem Webserver
// zuständig und holt im ersten Schritt neue Daten vom Server und trägt diese lokal
// ein und schickt dann die dem Server noch fehlenden scans an den Server.

require "debugHelper.php";
require_once 'db.php';

syncWeb();


function syncWeb()
{
    global $con;

    // URL des Aufrufs.
    // Wird dann auch per https gehen
    $addr = 'http://K6:18316650@localhost/kasse';
    
    // Basar-eindeutige ID der Kasse
    $registerid = 10;
    
    
    // Diese Tabellen sind abzugleichen
    $tables = array('settings', 'users', 'items');
 
    // lokale IP-Adresse auslesen damit beim Web-Server eine Eindeutigkeit
    // der Kasse geprüft werden kann.
    // Nach der Logik kann sich eine Kasse mit gleicher registerid und
    // anderer IP-Adresse erst nach einer Stunde ohne Sync-Aktivität syncen.
    // Damit wird vermieden, dass die gleichen Zugangsdaten für zwei unterschiedliche
    // Kassen verwendet wird. Da die Kasse am nächsten Tag eine andere lokale IP
    // haben könnte, das mit der Stunde.
    $myip = ownIP(); 

    // Alles in UTF8 wg. Umlauten
    $con->query('SET NAMES utf8');
    
    // Jede Tabelle aus $tables hat ein Feld modified. Was ist jeweils der aktuellste Eintrag?
    $maxwerte = array();
    foreach ($tables as $table) {
        $sql = 'SELECT MAX(modified) AS maxval FROM '.$table;
        $result = $con->query($sql);
        if ($result) {
            $row = mysqli_fetch_array($result);
            $maxwerte[$table] = $row['maxval'];
        }
        else {
            $maxwerte[$table] = 0;
        }
    }

    // Felder für die Anfrage an den Server zusammenpacken
    // Ohne lokale Tabellen sieht es dann so aus
    // maxwerte = '{"settings":0,"users":0,"items":0}'
    // Existieren die Tabellen bereits, dann eher so
    // maxwerte = '{"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2017-06-09 19:35:28"}'
    // ip = '192.168.2.101'
    // modus = 1 // 1 ist der erste Abgleich vom Server, 2 der Sync der Scans zum Server
    $fields = array(
        'registerid' => $registerid,
        'ip' => $myip,
        'modus' => '1' ,
        'maxwerte' => json_encode($maxwerte),        
    );

    // Anfrage beim Server
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $addr);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Antwort vom Server
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    // Textnachricht vom Server = Fehlermeldung oder sonst was
    $nachricht = '';
    
    // Was ist der aktuellste Scaneintrag der Kasse beim Webserver?
    $lastscan = 0;
    
    // Nicht erreichbar?
    if ($http_status == 404)
    {
        // Hier brauchen wir noch ein kontrolliertes Ende mit JSON-Antwort vgl. unten
        return -404;
    }
          
    // Antwort decodieren
    // Enthalten sind die beiden Felder message (Text vom Server) und lastscan (Zeitstempel des letzten Scans auf dem Webserver)
    // Sowie in den Arrays 'sqlsync', 'settings', 'users', 'items' jeweils Statements für den Datenabgleich
    $syncItems = json_decode($response, true);
    if ($syncItems != NULL)
    {
        // Damit der Datenbankeintrag schneller geht
        mysqli_autocommit($con, FALSE);
        
        // die einzelnen Tabellen durchgehen
        foreach ($syncItems as $tabelle => $werte) 
        {
            // Der Eintrag message enthält eine Statusmeldung
            if ($tabelle == 'message') {
                $nachricht = $werte;
            }
            
            // Der Eintrag lastscan ist das Datum des letzten Sync-Eintrags dieser Kasse
            elseif ($tabelle == 'lastscan') {
                // Wert merken für zweiten Abgleich
                $lastscan = $werte;
            }
            else {
                // Alle anderen Einträge sind Statements, die direkt ausgeführt werden können.
                if (is_array($werte)) {
                    foreach ($werte as $statement)
                    {
                        // Erfolgreiche Ausführung sollte noch geprüft werden
                        mysqli_query($con, utf8_decode($statement));
                    }

                    // Commit erst nach allen Einträgen einer Tabelle
                    mysqli_commit($con);
                }
            }
        }
        
        // Ab jetzt wieder autocommit
        mysqli_autocommit($con, TRUE);
    }

    // Jetzt die Anzahl der Einträge in den lokalen Tabellen auslesen um diese später anzuzeigen
    $tabellengroesse = array();    
    foreach ($tables as $tabelle) {
        $result = mysqli_query($con, 'SELECT count(*) AS anzahl FROM '.$tabelle);
        if ($result) {
            $row = mysqli_fetch_array($result);
            $tabellengroesse[$tabelle] = $row['anzahl'];
        }
    }

    
    // Jetzt die Datenübertragung der Scans vorbereiten
    // Alle neueren Scans, die noch nicht auf dem Server sind sammeln.
    $scans = [];
    $sql = 'SELECT * FROM sync WHERE register_id = '.$registerid.' AND created > "'.$lastscan.'"';
    $result = $con->query($sql);
    if ($result) {
        $scans = mysqli_fetch_all($result, MYSQLI_ASSOC);
        // Jede Kasse und der Server müssen selber buchen. Daher alle gebucht = 0 setzen
        for ($i=0; $i < sizeof($scans); $i++) $scans[$i]['gebucht'] = 0;
    }   
    
    // Außerdem ermitteln, wie viele Scans dieser Kasse schon auf dem Server sein müssten.
    $sql = 'SELECT COUNT(*) AS anz FROM sync WHERE register_id = '.$registerid.' AND created <= "'.$lastscan.'"';
    $result = mysqli_query($con, $sql);
    if ($result) {
        $row = mysqli_fetch_array($result);
        $oldscans = $row['anz'];
    } 
    else $oldscans = 0;
    
    // Felder für die Anfrage an den Server zusammenpacken
    $fields = array(
        'registerid' => $registerid,        
        'ip' => $myip,
        'modus' => '2',
        'scans' => json_encode($scans),
        'oldscans' => $oldscans
    );

    // Anfrage beim Server
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $addr);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Antwort vom Server
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch); 
    curl_close($ch);

    // Nicht erreichbar?
    if ($http_status == 404)
    {
        // Hier brauchen wir noch ein kontrolliertes Ende mit JSON-Antwort vgl. unten
        return -404;
    }
 
    // Antwort decodieren und auswerten
    // Bis dato wird nur eine Statusmeldung zurück übertragen. Weitere Werte natürlich möglich
    $answer2 = json_decode($response, true);    
    if ($answer2 != NULL) {
        foreach ($answer2 as $tabelle => $werte) 
        {
            // Der Eintrag message enthält eine Statusmeldung und bislang noch nicht mehr
            if ($tabelle == 'message') {
                $nachricht .= ' '.$werte;
            }
        }
    }
    else {
        $nachricht .= ' Fehler bei Phase 2';
    }
    
    // Antwort schreiben für den Aufruf über ajax sync.html
    $retArray = array('action' => 'WEB', 'groesse' => $tabellengroesse, 'zeit' => date('H:i:s'), 'lastscan' => $lastscan, 'message' => $nachricht);
    echo(json_encode($retArray,true));    
    return true;      
}

?>