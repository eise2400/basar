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
    
    public function initialize() {
        parent::initialize();
        $this->connection = ConnectionManager::get('default');
    }
    
    public function isAuthorized($user)
    {
        $action = $this->request->params['action'];

        // Nur Kassenbenutzer dürfen den Sync verwenden.
        if ($action == 'syncData' && $user['gruppe'] == "K") return true;
        
        // Der Admin darf alles
        if ($user['gruppe'] == "A") return true;
        
        return parent::isAuthorized($user);
    }

    // Message rausschreiben und beenden. Fehlerende für Ajax
    private function messageEnd($message) {
        $this->set(compact('message'));
        $this->viewBuilder()->setLayout('ajax');        
    }
    
    
    // Syncs in die Sales eintragen
    private function syncBuchen() {
        $this->loadModel('Sync');
        $this->loadModel('Sales');
        $offen = $this->Sync->find('all', array( 
              'conditions' => array('gebucht = 0'),
              'order' => array('created'),
        ))->toArray();
        foreach ($offen as $scan) {
            try {
                $sale = $this->Sales->get($scan->item_id);
            } catch (\Exception $e) {
                $sale = $this->Sales->newEntity();
                $sale->id = $scan->item_id;
            }
            if ($scan->art == 1) $sale->verkauft = 1;
            else $sale->verkauft = 0;
            if ($this->Sales->save($sale)) {
                $scan->gebucht = 1;
                $this->Sync->save($scan);
            }
            else return false;
            // In Sales eintragen
        }
        return true;
    }
    
    // Wie viele Minuten ist $date schon her?
    private function minutenHer($date) {
        $spanne = (strtotime('now') - strtotime($date)) / 60;
        return $spanne;
    }  

    private function tabelleLesen($tabelle, $modified = 0, $ausschluss = [], $id = null, $utf8encode = true) {
        $tab = [];
        if ($modified == 0) // Noch keine Daten vorhanden? Dann muss auch noch ein CREATE TABLE gemacht werden
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
        else $idBedingung = 'id = '.$id.' AND ';
        $tmpsql = 'SELECT * FROM '.$tabelle.' WHERE '.$idBedingung.'modified > "'.$modified.'"';
        
        // Ist die Tabelle noch leer, müssen alle Datensätze per INSERT übertragen werden.
        if ($modified != 0) $tmpsql .= ' AND modified = created';
        $result = $this->connection->execute($tmpsql)->fetchAll('assoc');
        $num_fields = sizeof($result) > 0 ? sizeof($result[0]) : 0;

        $tmpstr = '';
        $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
        for ($o = 0; $o < sizeof($result); $o++)
        {
            $row = $result[$o];
            if ($i == 0) $tmpstr = 'INSERT INTO '.$tabelle.' VALUES (';
            else $tmpstr .= ',(';

            $j = 0;
            foreach($row as $spalte => $wert) 
            {
                if (in_array($spalte, $ausschluss)) {
                    $wert = '';
                }
                else {
                    $wert = addslashes($wert);
                    $wert = ereg_replace("\n","\\n",$wert);
                }
                
                // Alle Fehler werden mit Anführungszeichen übertragen. SQL interpretiert den Inhalt bei Zahlwerten trotzdem richtig.
                if (isset($wert)) { $tmpstr .= '"'.$wert.'"' ; } else { $tmpstr .= '""'; }
                if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                $j++;
            }
            $tmpstr .= ")";
            $i++;

            if (($i >= 10) || ($o == sizeof($result) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
            {
                if ($utf8encode) array_push($tab, utf8_encode($tmpstr));  
                else array_push($tab, $tmpstr);  
                $i = 0;
            }
        }
        
        
        // Jetzt noch geänderte Datensätze senden, wenn die Tabelle nicht noch leer ist
        if ($modified != 0) {
            $result = $this->connection->execute('SELECT * FROM '.$tabelle.' WHERE '.$idBedingung.'modified > "'.$modified.'" AND modified > created')->fetchAll('assoc');
            $num_fields = sizeof($result) > 0 ? sizeof($result[0]) : 0;

            $tmpstr = '';
            for ($o = 0; $o < sizeof($result); $o++)
            {
                $row = $result[$o];
                $tmpstr = 'UPDATE '.$tabelle.' SET ';

                $j = 0;
                $where = ' WHERE id = -1';
                foreach($row as $spalte => $wert) 
                {
                    if ($spalte == 'id') $where = ' WHERE id = '.$wert;
                    if (in_array($spalte, $ausschluss)) {
                        $wert = '';
                    }
                    else {
                        $wert = addslashes($wert);
                        $wert = ereg_replace("\n","\\n",$wert);
                    }

                    // Alle Fehler werden mit Anführungszeichen übertragen. SQL interpretiert den Inhalt bei Zahlwerten trotzdem richtig.
                    if (isset($wert)) { 
                        $tmpstr .= $spalte.'="'.$wert.'"' ;                     
                    } else { 
                        $tmpstr .= $spalte.'=""'; 
                    }
                    if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                    $j++;
                }
                $tmpstr .= $where;

                if ($utf8encode) array_push($tab, utf8_encode($tmpstr));  
                else array_push($tab, $tmpstr);                 
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

    // Datendateil für Kasse schreiben
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
            array_push($sqlsync, $this->tabelleLesen($tabelle, 0, ['code'], false));
        }
        // Nur die eigene Kasse mit übertragen
        array_push($sqlsync, $this->tabelleLesen('registers', 0, [], $id), false);
        array_push($sqlsync, $this->getSyncTableSQL());
 	
        $return = implode(";\n", $sqlsync);

        $this->response->charset('UTF-8');
        $this->response->body($return);
        $this->response->type('txt');
        $this->response->download('kasse_'.$id.'.sql');

        // Return response object to prevent controller from trying to render a view.
        return $this->response;        
    }
        
    // Funktion für Online-Kassensync
    public function syncData() {
        $test = false;
        
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
        $message = '';
        $lastscan = 0; // Das ist der letzte bekannte Scan dieser Kasse
        
        // Nur per Post möglich
        if (!$test) {
            if (!$this->request->is('post')) return $this->messageEnd('Fehler');
        }
      
        if ($test) {
            $eingabe = array('modus' => 2, 'ip' => '192.168.2.201', 'registerid' => 10,
                'maxwerte' => '{"settings":"2017-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2018-06-09 19:35:28"}',
                'scans' => '[["10","6","123","3245324","2017-08-03 22:50:20"]]',
                'oldscans' => 0);
        } else {
            $eingabe = $this->request->data;
        }
        
        //modus = 1 => Datenabruf; modus = 2 => Scans senden
        if (!array_key_exists('modus', $eingabe)) return $this->messageEnd('Modus fehlt'); 
        $modus = $eingabe['modus'];
        if ($modus != 1 && $modus != 2) return $this->messageEnd('Modus fehlt');
        
        //$ip = '192.168.2.201';
        if (!array_key_exists('ip', $eingabe)) return $this->messageEnd('IP fehlt'); 
        $ip = $eingabe['ip'];
        if (is_null($ip) || $ip == '') return $this->messageEnd('IP fehlt');        
        
        //$eingabe['scans'] = '[["4","2","123","3245324","2017-08-03 22:50:20"]]';
        if (!array_key_exists('registerid', $eingabe)) return $this->messageEnd('Kassennummer fehlt'); 
        $registerid = $eingabe['registerid'];
        
        // Prüfen, ob registerid gesetzt und ob diese zum Benutzer passt
        $kassen = $this->Registers->find('all', array( 
          'conditions' => array('user_id = '.$this->Auth->user('id'), 'id = '.$registerid),
          'limit' => 1
        ));
        $kasse = $kassen->first();
        if (is_null($kasse)) return $this->messageEnd('Kasse nicht eingetragen oder nicht berechtigt'); 
            
        // Wann war der letzte Sync?
        // Wenn noch nicht mehr als eine Stunde her, dann IP Prüfung durchführen
        if ($this->minutenHer($kasse->lastSync) < 60) {
            if (!is_null($kasse->ip) && $kasse->ip != $ip) {
                return $this->messageEnd('Kasse unter anderer IP eingetragen. Doppelt angemeldet?');
            }
        }
                    
        // Neue Daten abfragen
        // Wegen Umlauten
        $this->connection->execute('SET NAMES utf8');
        $ERLAUBT = ['settings','users','items'];
        
        // Daten an Kasse rausschicken
        if ($modus == 1) {
            if (!array_key_exists('maxwerte', $eingabe)) return $this->messageEnd('Maxwerte fehlen'); 
            $maxwerte = json_decode($eingabe['maxwerte']);
            //$maxwerte = json_decode('{"settings":0,"users":0,"items":0}');
            //$maxwerte = json_decode('{"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2017-06-09 19:35:28"}');

            // Zeitstempel vom letzten Scan dieser Kasse holen und in $lastscan merken für modus=2
            $statement = $this->connection->execute('SELECT MAX(created) AS maxval FROM sync WHERE register_id = '.$registerid)->fetchAll('assoc');
            if (!is_null($statement[0]["maxval"])) $lastscan = $statement[0]["maxval"];
            else $lastscan = 0;
            
            $sqlsync = [];
            $synctabelleda = true;

            foreach ($maxwerte as $tabelle => $maxval) {
                if (!in_array($tabelle, $ERLAUBT)) return $this->messageEnd('Tabelle nicht erlaubt');
                if ($maxval == 0) $synctabelleda = false; // Es ist anzunehmen, dass die Synctabellen auch noch nicht vorhanden sind...
                // code, also Passwort nicht mitschicken
                $$tabelle = $this->tabelleLesen($tabelle, $maxval, ['code']);
            }

            // Wenn vermutet wird, dass die Sync-Tabellen noch nicht vorhanden sind, die Statements mal als IN NOT EXISTS mitschicken.
            if (!$synctabelleda) $sqlsync = $this->getSyncTableSQL();
            
            // Nun die IP ggf. eintragen oder ändern
            if (is_null($kasse->ip) || $kasse->ip != $ip) {
                $kasse->ip = $ip;
                if ($this->Registers->save($kasse)) $message = 'Sync erfolgreich. IP eingetragen.';
                else $message = 'Sync geschrieben. IP konnte nicht eingetragen werden.';
            }
            else {
                $message = 'Wiederholter Sync erfolgreich.';
            }

            $this->set(compact('sqlsync', 'settings', 'registers', 'users', 'items', 'message', 'lastscan'));
            Log::write('debug', 'scans:'.print_r($sqlsync, true));
        }
        
        // Ansonsten Kassen-Scans einlesen
        else {
            //$eingabe['scans'] = '[["4","2","123","3245324","2017-08-03 22:50:20"]]';
            if (!array_key_exists('scans', $eingabe)) return $this->messageEnd('Scans nicht gesetzt'); 
            $scans = json_decode($eingabe['scans']);
            
            if (!array_key_exists('oldscans', $eingabe)) return $this->messageEnd('Oldscans nicht gesetzt'); 
            $oldscans = $eingabe['oldscans'];
            
            //Prüfen, ob die Anzahl der alten Scans stimmt
            $statement = $this->connection->execute('SELECT COUNT(*) AS anz FROM sync WHERE register_id = '.$registerid)->fetchAll('assoc');
            if (!\is_null($statement[0]["anz"])) $scansindb = $statement[0]["anz"];
            else $scansindb = 0;
            if ($oldscans != $scansindb) {
                // Scans dieser Kasse ablöschen
                $statement = $this->connection->execute('DELETE FROM sync WHERE register_id = '.$registerid);
                $anz = $statement->rowCount();
                return $this->messageEnd('Oldscans stimmt nicht mit Einträgen überein. '.$anz.' alte Scans der Kasse gelöscht!');
            }
            
            //Log::write('debug', 'scans:'.print_r($scans, true));
            // Scans in Datenbank eintragen
            if ($scans) {
                //Log::write('debug', 'Numfields:'.$num_fields);
                $tmpstr = '';
                $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
                
                // Spaltennamen merken
                $spalten = '';
                foreach ($scans[0] as $spalte => $wert) {
                    $spalten .= '`'.$spalte.'`, ';
                }
                $spalten = substr($spalten, 0, strlen($spalten) - 2); // Komma wieder weg
                
                for ($o = 0; $o < sizeof($scans); $o++)
                {
                    $row = $scans[$o];
                    
                    // Jede Kasse darf nur eigene Einträge senden. Kann, muss aber nicht so sein.
                    if ($row->register_id != $kasse->id) return $this->messageEnd('Eintrag ungültiger Kasse!');
                    
                    if ($i == 0) {
                        $tmpstr = 'INSERT INTO sync ('.$spalten.') VALUES (';
                    }
                    else {
                        $tmpstr .= ',(';
                    }

                    foreach ($row as $spalte => $wert) {
                        $wert = ereg_replace("\n","\\n", addslashes($wert));
                        if (isset($wert)) { $tmpstr .= '"'.$wert.'"' ; } else { $tmpstr .= '""'; }
                        $tmpstr .= ','; 
                    }
                    $tmpstr = substr($tmpstr, 0, strlen($tmpstr) - 1);
                    $tmpstr .= ")";
                    $i++;

                    if (($i >= 10) || ($o == sizeof($scans) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
                    {
                        //Log::write('debug', 'SQL:'.$tmpstr);
                        $statement = $this->connection->execute($tmpstr);
                        $anzahl = $statement->rowCount();
                        //Log::write('debug', 'RET:'.$anzahl);
                        $i = 0;
                    }
                } 
                
                $this->syncBuchen();
                $message = 'Neue Scans übertragen.';
            }
            else {
                $message = 'Keine neuen Scans übertragen.';
            }

            //Log::write('debug', print_r($scans, true));
            
            $kasse->lastSync = date('Y-m-d H:i:s');
            if ($this->Registers->save($kasse)) {
                $message = 'Scans erfolgreich eingetragen.';
            }
            else {
                $message = 'Scans eingetragen. Sync nicht geschrieben.';
            }        
            
            $this->set(compact('message'));
        }
        
        $this->viewBuilder()->setLayout('ajax');	        
    }
    
    
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
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
    public function view($id = null)
    {
        $register = $this->Registers->get($id, [
            'contain' => ['Users']
        ]);
        $this->set('register', $register);
        $this->set('_serialize', ['register']);
    }
    
    
    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $register = $this->Registers->newEntity();
        if ($this->request->is('post')) {

            // Erst den zugehörigen Benutzer anlegen
            $userid = UsersController::addRegisterUser();
            
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
    public function edit($id = null)
    {
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
    public function delete($id = null)
    {
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
