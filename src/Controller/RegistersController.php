<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

/**
 * Registers Controller
 *
 * @property \App\Model\Table\RegistersTable $Registers
 */
class RegistersController extends AppController
{
    private $connection;
    private $kasse;
    private $eingabe;
    private $message;
    
    public function initialize() {
        parent::initialize();
        $this->connection = ConnectionManager::get('default');
    }
    
    public function isAuthorized($user) {
        $action = $this->request->params['action'];

        // Nur Kassenbenutzer dürfen den Sync verwenden.
        if ($action == 'syncData' && $user['gruppe'] == "K") return true;
        
        // Der Admin darf alles
        if ($user['gruppe'] == "A") return true;
        
        return parent::isAuthorized($user);
    }

    // Message rausschreiben und beenden. Fehlerende für Ajax
    private function messageEnd($message, $doThrow = false) {
        $this->message .= $message;
        $this->set(compact('message'));
        $this->viewBuilder()->setLayout('ajax');  
        if ($doThrow) throw new \Exception($message);
    }
      
    
    // Syncs in die Sales eintragen
    private function syncBuchen() {
        $this->loadModel('Sync');
        $this->loadModel('Sales');
        $this->loadModel('Users');        
        $offen = $this->Sync->find('all', array( 
              'conditions' => array('gebucht = 0'),
              'order' => array('created'),
        ))->toArray();
        $verbucht = 0;
        
        foreach ($offen as $scan) {
            // Check-In
            if ($scan->art == 0) {
                $user = UsersController::getUser($scan->barcode);
                if ($user) {
                    $user->ist_da = true;
                    $this->Users->save($user);
                    $scan->gebucht = 1;
                    $this->Sync->save($scan);   
                    $verbucht++;
                } 
                else {
                    $this->messageEnd('Es wurden '.$verbucht.' Scans verbucht; Benutzer zu '.$scan->barcode.' nicht gefunden.;', true);
                } 
            }
            elseif ($scan->art == 1) {
//                try {
//                    $sale = $this->Sales->get($scan->item_id);
//                } catch (\Exception $e) {
//                    $sale = $this->Sales->newEntity();
//                    $sale->id = $scan->item_id;
//                }
//                if ($scan->art == 1) $sale->verkauft = 1;
//                else $sale->verkauft = 0;
//                if ($this->Sales->save($sale)) {
//                    $scan->gebucht = 1;
//                    $this->Sync->save($scan);
//                }
//                else return false;
                // In Sales eintragen                
            }
        }
        return true;
    }
    
    // Wie viele Minuten ist $date schon her?
    private function minutenHer($date) {
        if (is_null($date)) return strtotime('now') / 60; 
        $spanne = (strtotime('now') - strtotime($date->i18nFormat('yyyy-MM-dd HH:mm:ss'))) / 60;
        return $spanne;
    }  

    private function makeStatements($tabelle, $ausschluss, $utf8encode, $result, $insert = true, $sql = true) {
        $tmpstr = '';
        $tab = [];
        $line = [];
        $where = ''; // wird nur für UPDATE gebraucht
        
        $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden
        if ($insert) $maxI = 10; // so viele Inserts werden zusammengepackt
        else $maxI = 1; // Bei Update muss jedes Statment separat stehen
        
        $num_rows = sizeof($result);
        $num_fields = $num_rows > 0 ? sizeof($result[0]) : 0;        
        for ($o = 0; $o < sizeof($result); $o++)
        {
            $row = $result[$o];
            if (!$sql) $line = [];
            elseif ($i == 0) {
                if ($insert) {
                    $tmpstr = 'INSERT INTO '.$tabelle.' VALUES (';
                }
                else {
                    $tmpstr = 'UPDATE '.$tabelle.' SET '; 
                    $where = ' WHERE id = -1';
                }
            }
            else $tmpstr .= ',(';

            $j = 0;
            foreach($row as $spalte => $wert) 
            {
                if (!$insert && $spalte == 'id') $where = ' WHERE id = '.$wert;
                if (in_array($spalte, $ausschluss)) {
                    $wert = '';
                }
                else {
                    //$wert = addslashes($wert);
                    $wert = preg_replace('/\n/','/\\n/', $wert);
                }
                
                // Alle Felder werden mit Anführungszeichen übertragen. SQL interpretiert den Inhalt bei Zahlwerten trotzdem richtig.
                if ($sql) {
                    if ($insert) {
                        if (isset($wert)) { $tmpstr .= '"'.$wert.'"'; } else { $tmpstr .= '""'; }
                    }
                    else {
                        if (isset($wert)) { $tmpstr .= $spalte.'="'.$wert.'"'; } else { $tmpstr .= $spalte.'=""'; }                 
                    }
                    if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                }
                else {
                    $line[] = $utf8encode ? utf8_encode($wert) : $wert;
                }
                $j++;
            }
            
            if ($sql) {
                if ($insert) $tmpstr .= ")";
                else $tmpstr .= $where;
                
                $i++;

                if (($i >= $maxI) || ($o == sizeof($result) - 1)) // nach maxI Datensätzen oder wenn keine mehr übrig sind
                {
                    if ($utf8encode) array_push($tab, utf8_encode($tmpstr));  
                    else array_push($tab, $tmpstr);  
                    $i = 0;
                }                
            } 
            else {
                $tab[] = $line;
            }
        } 
        return $tab;
    }
    
    // Tabellenwerte auslesen uns zurück liefern
    // $tabelle: Name der Tabelle
    // $modified: Wenn ungleich null, dann alle Datensätze, die im Feld modified jünger sind 
    // $ausschluss: Diese Felder werden nicht mit übertragen bzw. werden im SQL-Modus als "" übertragen
    // $id: Genau diese ID wird übertragen. Wird eine Zahl übergeben, wird genau dieser Datensatz geliefert, ansonsten der String als Bedingung verwendet
    // $utf8encode: Sollen die Daten UTF8 codiert werden?
    // $sql: Ausgabe als SQL Statements oder als Feldliste
    // $maxentries: Die maximale Anzahl an Datensätzen, die auf einmal übertragen wird.
    private function tabelleLesen($tabelle, $modified = 0, $ausschluss = [], $id = null, $utf8encode = true, $sql = true, $maxentries = 0) {
        $tab = [];

        // Noch keine Daten vorhanden? Dann muss auch noch ein CREATE TABLE gemacht werden. Aber nur im SQL-Modus
        if ($modified == 0 && $sql == true) 
        {
            array_push($tab, 'DROP TABLE IF EXISTS '.$tabelle);
            $statement = $this->connection->execute('SHOW CREATE TABLE '.$tabelle)->fetchAll('assoc');
            array_push($tab, $statement[0]["Create Table"]);
        } 

         // Und jetzt noch die eigentlichen Daten auslesen
         // Da die Primärschlüssel erhalten bleiben müssen und bei diesen Tabellen nur auf dem
         // Server geschrieben werden dürfen, werden die Daten samt Schlüssel übertragen.
         // Um bei geänderten Einträgen nicht mit Fremdschlüsseln zu kollidieren, müssten geänderte Einträge per UPDATE übertragen werden
        if (is_null($id)) $idBedingung = '';
        elseif (is_numeric($id)) $idBedingung = 'id = '.$id.' AND ';
        else $idBedingung = $id.' AND ';
        $tmpsql = 'SELECT * FROM '.$tabelle.' WHERE '.$idBedingung.'modified > "'.$modified.'"';
        
        // Ist die Tabelle beim Client noch leer, müssen alle Datensätze per INSERT übertragen werden.
        // Ansonsten müssen die per Insert übertragen werden, die nach dem $modified angelegt worden sind
        if ($modified != 0) $tmpsql .= ' AND created > "'.$modified.'" ORDER BY modified';
        
        if ($maxentries != 0) {
            $limit = true;
            $tmpsql .= ' LIMIT '.$maxentries;
        } else $limit = false;
        
        $result = $this->connection->execute($tmpsql)->fetchAll('assoc');
        $num_rows = sizeof($result);
        
        if ($sql) {
            $tab['insert'] = $this->makeStatements($tabelle, $ausschluss, $utf8encode, $result);
        } else {
            //makeStatements($tabelle, $ausschluss, $utf8encode, $result, $insert = true, $sql = true)
            $tab['insert'] = $this->makeStatements($tabelle, $ausschluss, $utf8encode, $result, true, false);
        }
        
        // Jetzt noch geänderte Datensätze senden, wenn die Tabelle nicht noch leer ist
        if ($modified != 0 && ( !$limit || ( $maxentries < $num_rows ) ) ) {
            $tmpstr = 'SELECT * FROM '.$tabelle.' WHERE '.$idBedingung.'modified > "'.$modified.'" AND created <= "'.$modified.'" ORDER BY modified';
            if ($limit) {
                $maxentries -= $num_rows;
                $tmpstr .= ' LIMIT '.$maxentries;
            }

            $result = $this->connection->execute($tmpstr)->fetchAll('assoc');
            if ($sql) {
                $tab['update'] = $this->makeStatements($tabelle, $ausschluss, $utf8encode, $result, false);
            } else {
                $tab['update'] = $this->makeStatements($tabelle, $ausschluss, $utf8encode, $result, false, false);
            }  
        }

        return $tab;
    }
    
    // Statements für Sync-Tabellen holen
    public function getSyncTableSQL() {
        $sqlsync = [];
        
        $statement = $this->connection->execute('SHOW CREATE TABLE sync')->fetchAll('assoc');
        $tmpstr = $statement[0]["Create Table"];   
        $sqlsync[] = 'CREATE TABLE IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'TABLE') + 5);

        $statement = $this->connection->execute('SHOW CREATE TABLE sales')->fetchAll('assoc');
        $tmpstr = $statement[0]["Create Table"];
        $sqlsync[] = 'CREATE TABLE IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'TABLE') + 5);

        $statement = $this->connection->execute('SHOW CREATE TABLE itemsales')->fetchAll('assoc');
        $tmpstr = $statement[0]["Create View"];
        $sqlsync[] = 'CREATE VIEW IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'VIEW') + 4); // Sicherheitsinfos mit rauslöschen
        
        return $sqlsync;
    }

    
    // Datendatei für Kasse schreiben
    public function datafile($id = null) {	
        $kasse = $this->Registers->get($id);
        if (is_null($kasse)) {
            $this->Flash->error(__('Die Kasse konnte nicht gefunden werden.'));
            return $this->redirect(['action' => 'index']);
        }
        
        $sqlsync = [];
        $this->connection->execute('SET NAMES utf8');

        $tables = ['settings','users','items'];
        foreach ($tables as $tabelle) {
            $sqlsync = array_merge($sqlsync, $this->tabelleLesen($tabelle, 0, ['code'], null, false));
        }
        // Nur die eigene Kasse mit übertragen
        $sqlsync = array_merge($sqlsync, $this->tabelleLesen('registers', 0, [], $kasse->id, false));
        $sqlsync = array_merge($sqlsync, $this->getSyncTableSQL());
 	
        $return = implode(";\n", $sqlsync);

        $this->response->charset('UTF-8');
        $this->response->body($return);
        $this->response->type('txt');
        $this->response->download('kasse_'.$id.'.sql');

        // Return response object to prevent controller from trying to render a view.
        return $this->response;        
    }
    
    
    // Daten an Kasse rausschicken  
    // update - array('maxwerte') wie unten => array('table': {'I': {...spalten...}, 'U': ...spalten }) 10 Einträge + lastscan 
    private function update($asSQL) {
        $ERLAUBT = ['settings','users','items'];    
        $lastscan = 0; // Das ist der letzte bekannte Scan dieser Kasse        
        
        if (!array_key_exists('maxwerte', $this->eingabe)) return $this->messageEnd('Maxwerte fehlen', true); 
        $maxwerte = $this->eingabe['maxwerte'];
        //$maxwerte = json_decode('{"settings":0,"users":0,"items":0}');
        //$maxwerte = json_decode(' ');

        // Zeitstempel vom letzten Scan dieser Kasse holen und in $lastscan merken für modus=2
        $statement = $this->connection->execute('SELECT MAX(created) AS maxval FROM sync WHERE register_id = '.$this->eingabe['registerid'])->fetchAll('assoc');
        if (!is_null($statement[0]["maxval"])) $lastscan = $statement[0]["maxval"];
        else $lastscan = 0;

        $sqlsync = [];
        $synctabelleda = true;

        foreach ($maxwerte as $tabelle => $maxval) {
            if (!in_array($tabelle, $ERLAUBT)) return $this->messageEnd('Tabelle nicht erlaubt', true);
            if ($asSQL && $maxval == 0) $synctabelleda = false; // Es ist anzunehmen, dass die Synctabellen auch noch nicht vorhanden sind...
            // code, also Passwort nicht mitschicken
            // tabelleLesen($tabelle, $modified = 0, $ausschluss = [], $id = null, $utf8encode = true, $sql = true, $maxentries = 0)
            $$tabelle = $this->tabelleLesen($tabelle, $maxval, ['code'], null, true, $asSQL);
        }

        // Wenn vermutet wird, dass die Sync-Tabellen noch nicht vorhanden sind, die Statements mal als IN NOT EXISTS mitschicken.
        if ($asSQL && !$synctabelleda) {
            $sqlsync = $this->getSyncTableSQL();
            // Ggf. auch die Registers-Tabelle mit rausschreiben
            // $sqlsync = array_merge($sqlsync, $this->tabelleLesen('registers', 0, [], $kasse->id, true));
        }

        $this->set(compact('settings', 'registers', 'users', 'items', 'lastscan'));
        if ($asSQL) $this->set('sqlsync');
        //Log::write('debug', 'scans:'.print_r($sqlsync, true));         
    }
    
    
    // Modus: init - table[string]/maxid[int] => array('table': {'I': {...spalten...}, 'U': ...spalten }) 10 Einträge  
    private function init() {
        $ERLAUBT = ['settings','users','items'];  
        if (!array_key_exists('table', $this->eingabe)) return $this->messageEnd('table fehlt', true); 
        if (!in_array($this->eingabe['table'], $ERLAUBT)) return $this->messageEnd('Tabelle nicht erlaubt', true);
        if (!array_key_exists('maxid', $this->eingabe)) return $this->messageEnd('maxid fehlt', true);
        if (array_key_exists('utf8', $this->eingabe)) $utf8encode = true;
        else $utf8encode = false;
        
        if ($this->eingabe['maxid'] <= 0) {
            $bedingung = null;
        }
        else {
            $bedingung = 'id > '.$this->eingabe['maxid'];
        }
        if (array_key_exists('maxanzahl', $this->eingabe) && is_numeric($this->eingabe['maxanzahl'])) $maxanzahl = max([0, $this->eingabe['maxanzahl']]);
        else $maxanzahl = 10;        
        
        // tabelleLesen($tabelle, $modified = 0, $ausschluss = [], $id = null, $utf8encode = true, $sql = true, $maxentries = 0)            
        ${$this->eingabe['table']} = $this->tabelleLesen($this->eingabe['table'], 0, ['code'], $bedingung, $utf8encode, false, $maxanzahl);
        $this->set(compact('sqlsync', 'settings', 'registers', 'users', 'items', 'lastscan'));
    }
  
    
    // Was ist der aktuellst Eintrag einer Tabelle?    
    // lastmodified - table[array/string]? => array(table: modified)     
    private function lastModified() {
        $ERLAUBT = ['settings','users','items','sync'];    
        $lastscan = 0; // Das ist der letzte bekannte Scan dieser Kasse        
        
        if (!array_key_exists('table', $this->eingabe)) return $this->messageEnd('Table fehlt', true);
        
        // Falls String übertragen wurde, in Array umwandeln
        if (!is_array($this->eingabe['table'])) $this->eingabe['table'] = [$this->eingabe['table']];

        $modified = [];

        foreach ($this->eingabe['table'] as $tabelle) {
            if (!in_array($tabelle, $ERLAUBT)) return $this->messageEnd('Tabelle nicht erlaubt', true);
            if ($tabelle == 'sync') {
                $statement = $this->connection->execute('SELECT MAX(created) AS maxval FROM '.$tabelle.
                        ' WHERE register_id = '.$this->eingabe['registerid'])->fetchAll('assoc');
            }
            else {
                $statement = $this->connection->execute('SELECT MAX(modified) AS maxval FROM '.$tabelle)->fetchAll('assoc');
            }
            if (!is_null($statement[0]["maxval"])) $lastscan = $statement[0]["maxval"];
            else $lastscan = 0;
            $modified[$tabelle] = $lastscan;
        }

        $this->set(compact('modified'));
        //Log::write('debug', 'scans:'.print_r($sqlsync, true));         
    }
    
    
    // Ansonsten Kassen-Scans einlesen    
    private function upSync() {
        // Typ: 0 = Checkin, Typ: 1 = Verkauft
        //                      Kasse ID  Barcode  Typ  Timestamp
        //$eingabe['scans'] = '[["4","2","3245324","0","2017-08-03 22:50:20"]]';
        if (!array_key_exists('scans', $this->eingabe)) return $this->messageEnd('Scans nicht gesetzt; ', true); 
        $scans = $this->eingabe['scans'];

        
        if (!array_key_exists('oldscans', $this->eingabe)) return $this->messageEnd('Oldscans nicht gesetzt; ', true); 
        $oldscans = $this->eingabe['oldscans'];

        //Prüfen, ob die Anzahl der alten Scans stimmt
        $statement = $this->connection->execute('SELECT COUNT(*) AS anz FROM sync WHERE register_id = '.$this->eingabe['registerid'])->fetchAll('assoc');
        if (!\is_null($statement[0]["anz"])) $scansindb = $statement[0]["anz"];
        else $scansindb = 0;

        if ($oldscans != $scansindb) {
           // Scans dieser Kasse ablöschen
           $statement = $this->connection->execute('DELETE FROM sync WHERE register_id = '.$this->eingabe['registerid']);
           $anz = $statement->rowCount();
           return $this->messageEnd('Oldscans stimmt nicht mit Einträgen überein. '.$anz.' alte Scans der Kasse gelöscht; ', true);
        }

        //Log::write('debug', 'scans:'.print_r($scans, true));
        // Scans in Datenbank eintragen
        if ($scans) {
           //Log::write('debug', 'Numfields:'.$num_fields);
           $tmpstr = '';
           $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
           $anzahl = 0; // So viele Syncs sind in die Datenbank eingetragen worden.

           // Spaltennamen merken
           /*$spalten = '';
           foreach ($scans[0] as $spalte => $wert) {
               $spalten .= '`'.$spalte.'`, ';
           }
           $spalten = substr($spalten, 0, strlen($spalten) - 2); // Komma wieder weg
           */
           $spalten = '`register_id`, `reg_item_id`, `barcode`, `art`, `created`';
             

           for ($o = 0; $o < sizeof($scans); $o++)
           {
               $row = $scans[$o];

               // Jede Kasse darf nur eigene Einträge senden. Kann, muss aber nicht so sein.
               if ($row[0] != $this->kasse->id) return $this->messageEnd('Eintrag ungültiger Kasse; ', true);

               if ($i == 0) {
                   $tmpstr = 'INSERT INTO sync ('.$spalten.') VALUES (';
               }
               else {
                   $tmpstr .= ',(';
               }

               foreach ($row as $spalte => $wert) {
                   $wert = preg_replace('/\n/','/\\n/', addslashes($wert));
                   if (isset($wert)) { $tmpstr .= '"'.$wert.'"' ; } else { $tmpstr .= '""'; }
                   $tmpstr .= ','; 
               }
               $tmpstr = substr($tmpstr, 0, strlen($tmpstr) - 1);
               $tmpstr .= ")";
               $i++;

               if (($i >= 10) || ($o == sizeof($scans) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
               {
                   //Log::write('debug', 'SQL:'.$tmpstr);
                   try {
                    $statement = $this->connection->execute($tmpstr);
                   }
                   catch (\Exception $e) {
                        return $this->messageEnd('SQL nicht erfolgreich! '.$anzahl.' Einträge gespeichert; '.$e->getMessage()."; ", true);
                   } 
                   $anzahl += $statement->rowCount();
                   //Log::write('debug', 'RET:'.$anzahl);
                   $i = 0;
               }
           } 

           $this->message .= 'Es wurden '.$anzahl.' neue Scans übertragen; ';
           $this->syncBuchen();           
        }
        else {
           $this->message = 'Keine neuen Scans übertragen; ';
        }

        //Log::write('debug', print_r($scans, true));     
    }
    
    
    // Hauptfunktion für Online-Kassensync
    public function syncData() {
        
        // Um keine Probleme mit dem ausgegebenen JSON zu bekommen.
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
        $message = '';
        
        // Zugriff ist nur per Post möglich
        if (!$this->request->is('post')) return $this->messageEnd('Fehler');
        
        // Daten können entweder als normale POST-Variablen oder als komplettes JSON übertragen werden
        $request_body = file_get_contents('php://input');
        $this->eingabe = json_decode($request_body, true);
        
        // Falls kein JSON übergeben wurde, liegen die Variablen bereits im Array $this->eingabe vor
        // Müssen ggf. aber noch in ein Array umgewandelt werden
        if (is_null($this->eingabe)) {
            $this->eingabe = $this->request->data;
            foreach($this->eingabe as $var => $wert) {
                $tmp = json_decode($wert);
                if (!is_null($tmp)) $this->eingabe[$var] = $tmp;
            }
        }
        
        //$ip = '192.168.2.201';
        if (!array_key_exists('ip', $this->eingabe)) return $this->messageEnd('IP fehlt; '); 
        $ip = $this->eingabe['ip'];
        if (is_null($ip) || $ip == '') return $this->messageEnd('IP fehlt; ');        
        
        //$eingabe['scans'] = '[["4","2","123","3245324","2017-08-03 22:50:20"]]';
        if (!array_key_exists('registerid', $this->eingabe)) return $this->messageEnd('Kassennummer fehlt; '); 
        
        // Prüfen, ob registerid gesetzt und ob diese zum Benutzer passt
        $kassen = $this->Registers->find('all', array( 
          'conditions' => array('user_id = '.$this->Auth->user('id'), 'id = '.$this->eingabe['registerid']),
          'limit' => 1
        ));
        $this->kasse = $kassen->first();
        if (is_null($this->kasse)) return $this->messageEnd('Kasse nicht eingetragen oder nicht berechtigt; '); 
            
        // Wann war der letzte Sync?
        // Wenn noch nicht mehr als eine Stunde her, dann IP Prüfung durchführen
        if ($this->minutenHer($this->kasse->lastSync) < 60) {
            if (!is_null($this->kasse->ip) && $this->kasse->ip != $ip) {
                return $this->messageEnd('Kasse unter anderer IP eingetragen. Doppelt angemeldet?; ');
            }
        }   
        
        // Wegen Umlauten
        $this->connection->execute('SET NAMES utf8');  
        
        if (!array_key_exists('modus', $this->eingabe)) return $this->messageEnd('Modus fehlt; '); 
        try {
            switch ($this->eingabe['modus']) {
                case '1':
                case 'updatesql':
                    $this->update(true);
                    break;
                case 'init':
                    $this->init();              
                    break;
                case 'lastmodified':
                    $this->lastModified();               
                    break;
                case 'update':
                    $this->update(false);               
                    break;
                case '2':
                case 'upsync':
                    $this->upSync();
    //               upsync - array('tabelle': JSON)                 
                    break;
                default:
                    return $this->messageEnd('Falscher Modus; ');
            }
            
            $this->setLastSync();
            
        }
        catch (\Exception $e) {
            $this->messageEnd($e->getMessage());
        }
        
        // immer mit message  
//        modus=1&ip=192.168.2.10&registerid=12&maxwerte={"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2018-06-09 19:35:28"}
//       {"modus":"1","ip":"192.168.2.10","registerid":"10","maxwerte":{"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2018-06-09 19:35:28"}}          
        $this->viewBuilder()->setLayout('ajax');	        
    }
 
    
    private function setLastSync() {
        // Nun die IP ggf. eintragen oder ändern
        if (is_null($this->kasse->ip) || $this->kasse->ip != $this->eingabe['ip']) {
            $this->kasse->ip = $this->eingabe['ip'];
            $this->kasse->lastSync = date('Y-m-d H:i:s');
            if ($this->Registers->save($this->kasse)) $this->message .= 'Sync erfolgreich. IP eingetragen; ';
            else $this->message .= 'Sync geschrieben. IP konnte nicht eingetragen werden; ';
        }
        else {
            $this->kasse->lastSync = date('Y-m-d H:i:s');
            if ($this->Registers->save($this->kasse)) {
                $this->message .= 'Wiederholter Sync erfolgreich; ';
            }
            else {
                $this->message .= 'Fehler. Kasse konnte nicht geschrieben werden; ';
            }                
        }

        $this->set('message', $this->message);    
    }
    
    
    /**
     * Index method
     *
     * @return void
     */
    public function index() {
        $this->paginate = [
            'contain' => ['Users']
        ];
        $regs = $this->paginate($this->Registers);
        $this->set('registers', $regs);
        $this->set('_serialize', ['registers']);
    }

    
    /**
     * View method
     *
     * @param string|null $id Register id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null) {
        $register = $this->Registers->get($id, [
            'contain' => ['Users']
        ]);
        $this->set('register', $register);
        $this->set('_serialize', ['register']);
    }
    
    
    /**
     * addRegisterUser method
     *
     * @return int ID des neuen Kassenbenutzers.
     */
    public function addRegisterUser()
    {     
        $this->loadModel('Users');
        $user = $this->Users->newEntity();                
        $user->nummer = $this->naechsteKasse();
        $user->code = rand(10000000, 99999999);   
        $user->gruppe = 'K';

        if ($this->Users->save($user)) {
            $nummer = $user->id;
        } else {
            $nummer = 0;
        }
        return $nummer;
    }      
    
    

    // Höchste Kassennummer ermitteln und die nächste Kassennummer zurück geben.
    private function naechsteKasse() {
        $maxlist = $this->Users->find('all', array( 
              'fields' => array('nummer'),
              'conditions' => array("gruppe = 'K'"),
              'order' => array('nummer DESC'),
              'limit' => 1
        ));
        $max = $maxlist->first();
        if (is_null($max)) $maxnummer = 'K1'; // Die erste Kasse heisst K1
        else {
            // ab dann wird durchnummeriert.
            // K weg und in Zahl umwandeln
            $maxnummer = (int)substr($max['nummer'], 1);
            // Dann erhöhen und 'K' wieder dran
            $maxnummer = 'K'.($maxnummer + 1);
        }
        return $maxnummer;   
    }        
    
    
    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add() {
        $register = $this->Registers->newEntity();
        if ($this->request->is('post')) {

            // Erst den zugehörigen Benutzer anlegen
            $userid = $this->addRegisterUser();
            
            // Dann Eintrag für Kasse vorbereiten
            $register = $this->Registers->patchEntity($register, $this->request->data);
            $register->user_id = $userid;
            
            // Zugangsdaten brauchen wir für die Syncadresse
            $this->loadModel('User');
            $user = $this->Users->get($userid);
            
            // Syncadresse zusammenbauen:
            // Format: https://Kx:passwort@www.basar-teugn.de/kasse
            $url = AppController::getSetting('Kassen-URL');
            $prot = substr($url, 0, strpos($url, '://') + 3);
            $uri = substr($url, strpos($url, '://') + 3);
            $register->syncaddr = $prot.$user->nummer.':'.$user->code.'@'.$uri;
            
            if ($this->Registers->save($register)) {
                $this->Flash->success(__('Neue Kasse angelegt.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Die Kasse konnte nicht angelegt werden.'));
            }
        }
        $this->set(compact('register'));
        $this->set('_serialize', ['register']);
    }

    
    /**
     * Edit method
     *
     * @param string|null $id Register id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null) {
        $register = $this->Registers->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $register = $this->Registers->patchEntity($register, $this->request->data);
            if ($this->Registers->save($register)) {
                $this->Flash->success(__('The register has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The register could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('register'));
        $this->set('_serialize', ['register']);
    }

    
    /**
     * Delete method
     *
     * @param string|null $id Register id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $register = $this->Registers->get($id);
        $benutzer = $register->user_id;
        if ($this->Registers->delete($register)) {     
            $this->loadModel('Users');
            $user = $this->Users->get($benutzer);
            if (is_null($user)) {
               $this->Flash->success(__('Die Kasse wurde gelöscht. Der Kassenbenutzer war schon weg.'));                
            }
            elseif ($this->Users->delete($user)) {
                $this->Flash->success(__('Die Kasse und Kassenbenutzer wurden gelöscht.'));
            } 
            else {
                $this->Flash->error(__('Kasse gelöscht. Kassenbenutzer konnte nicht gelöscht werden.'));
            }
        } 
        else {
            $this->Flash->error(__('Die Kasse konnte nicht gelöscht werden.'));
        }
        return $this->redirect(['action' => 'index']);
    }
}
