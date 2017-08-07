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
    public function isAuthorized($user)
    {
        $action = $this->request->params['action'];

        // Nur Kassenbenutzer dürfen den Sync verwenden.
        if ($action == 'syncData' && $user['gruppe'] == "K") return true;
        
        // Der Admin darf alles
        if ($user['gruppe'] == "A") return true;
        
        return parent::isAuthorized($user);
    }


    private function messageEnd($message) {
        $this->set(compact('message'));
        $this->viewBuilder()->setLayout('ajax');        
    }
    
    
    // Wie viele Minuten ist $date schon her?
    private function minutenHer($date)
    {
        $spanne = (strtotime('now') - strtotime($date)) / 60;
        return $spanne;
    }  


    /**
     * Sync method
     *
     * @param string|null $id Register id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function syncData()
    {
        $test = true;
        
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
        $message = '';
        $lastscan = 0; // Das ist der letzte bekannte Scan dieser Kasse
        
        // Nur per Post möglich
        if (!$test) {
            if ($this->request->is('post')) return $this->messageEnd('Fehler');
        }
      
        if ($test) {
            $eingabe = array('modus' => 1, 'ip' => '192.168.2.201', 'registerid' => 10,
                'maxwerte' => '{"settings":"2016-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2018-06-09 19:35:28"}');
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
        $connection = ConnectionManager::get('default');              
        $connection->execute('SET NAMES utf8');
        
        $ERLAUBT = ['settings','users','items'];
        
        if ($modus == 1) {
            if (!array_key_exists('maxwerte', $eingabe)) return $this->messageEnd('Maxwerte fehlen'); 
            $maxwerte = json_decode($eingabe['maxwerte']);
            //$maxwerte = json_decode('{"settings":0,"users":0,"items":0}');
            //$maxwerte = json_decode('{"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2017-06-09 19:35:28"}');

            // Zeitstempel vom letzten Scan dieser Kasse holen und in $lastscan merken für modus=2
            $statement = $connection->execute('SELECT MAX(created) AS maxval FROM sync WHERE register_id = '.$registerid)->fetchAll('assoc');
            if ($statement) $lastscan = $statement[0]["maxval"];
            else $lastscan = 0;
            
            $sqlsync = [];
            $synctabelleda = true;

            //tabelleLesen($tabelle, $modified, $ausschluss)
            foreach ($maxwerte as $tabelle => $maxval) {
                if (!in_array($tabelle, $ERLAUBT)) return $this->messageEnd('Tabelle nicht erlaubt');
                
                $$tabelle = []; // Jede Tabelle wird in die entsprechende Variable geschrieben.
                if ($maxval == 0) // Noch keine Daten vorhanden? Dann muss auch noch ein CREATE TABLE gemacht werden
                {
                    $synctabelleda = false; // Es ist anzunehmen, dass die Synctabellen auch noch nicht vorhanden sind...
                    $sqlsync[] = 'DROP TABLE IF EXISTS '.$tabelle;
                    $statement = $connection->execute('SHOW CREATE TABLE '.$tabelle)->fetchAll('assoc');
                    $sqlsync[] = $statement[0]["Create Table"];                  
                }

                // Und jetzt noch die eigentlichen Daten auslesen
                // Da die Primärschlüssel erhalten bleiben müssen und bei diesen Tabellen nur auf dem
                // Server geschrieben werden dürfen, werden die Daten samt Schlüssel übertragen.
                // Um bei geänderten Einträgen mit INSERTs nicht zu kollidieren, müssen ggf. vorhandene Zeilen vorm Insert gelöscht werden.
                // Geht nur, weil die Fremdschlüsselüberwachung beim Client nicht aktiv ist!
                $result = $connection->execute('SELECT * FROM '.$tabelle.' WHERE modified > "'.$maxval.'"')->fetchAll('num');
                $num_fields = sizeof($result) > 0 ? sizeof($result[0]) : 0;

                $tmpstr = '';
                $delstr = '';
                $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
                for ($o = 0; $o < sizeof($result); $o++)
                {
                    $row = $result[$o];
                    if ($i == 0) {
                        $delstr = 'DELETE FROM '.$tabelle.' WHERE id IN (';
                        $tmpstr = 'INSERT INTO '.$tabelle.' VALUES (';
                    }
                    else 
                    {
                        $delstr .= ',';
                        $tmpstr .= ',(';
                    }

                    $delstr .= $row[0]; // id ist jeweils die erste Spalte
                    for($j=0; $j < $num_fields; $j++) 
                    {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                        
                        // Alle Fehler werden mit Anführungszeichen übertragen. SQL interpretiert den Inhalt bei Zahlwerten trotzdem richtig.
                        if (isset($row[$j])) { $tmpstr .= '"'.$row[$j].'"' ; } else { $tmpstr .= '""'; }
                        if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                    }
                    $tmpstr .= ")";
                    $i++;

                    if (($i >= 10) || ($o == sizeof($result) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
                    {
                        array_push($$tabelle, $delstr.')');
                        array_push($$tabelle, utf8_encode($tmpstr));                      
                        $i = 0;
                    }
                }
            }

            // Wenn vermutet wird, dass die Sync-Tabellen noch nicht vorhanden sind, die Statements mal als IN NOT EXISTS mitschicken.
            if (!$synctabelleda)
            {
                $statement = $connection->execute('SHOW CREATE TABLE sync')->fetchAll('assoc');
                $tmpstr = $statement[0]["Create Table"];   
                $sqlsync[] = 'CREATE TABLE IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'TABLE') + 5);

                $statement = $connection->execute('SHOW CREATE TABLE sales')->fetchAll('assoc');
                $tmpstr = $statement[0]["Create Table"];
                $sqlsync[] = 'CREATE TABLE IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'TABLE') + 5);

                $statement = $connection->execute('SHOW CREATE TABLE itemsales')->fetchAll('assoc');
                $tmpstr = $statement[0]["Create View"];
                $sqlsync[] = 'CREATE VIEW IF NOT EXISTS '.substr($tmpstr, strpos($tmpstr, 'VIEW') + 4); // Sicherheitsinfos mit rauslöschen
            }

            $this->set(compact('sqlsync', 'settings', 'registers', 'users', 'items', 'message', 'lastscan'));
            
            // Nun die IP ggf. eintragen oder ändern
            if (is_null($kasse->ip) || $kasse->ip != $ip) {
                $kasse->ip = $ip;
                if ($this->Registers->save($kasse)) {
                    $message = 'Sync erfolgreich. IP eingetragen.';
                }
                else {
                    $message = 'Sync geschrieben. IP konnte nicht eingetragen werden.';
                }
            }
            else {
                $message = 'Wiederholter Sync erfolgreich.';
            }
        }
        
        // Ansonsten Kassen-Scans einlesen
        else {
            // Ist Kasse bereits eingtragen?
            $query = $this->Registers->findByIp($ip);
            $kasse = $query->first();
            if ($kasse == null) // Kasse ist noch nicht eingetragen, also neu anlegen
            {
                $message = 'Kasse noch nicht vorhanden. Abbruch!';             
            }
            else 
            {
                $scans = json_decode($eingabe['scans']);
                Log::write('debug', 'scans:'.print_r($scans, true));
                // Scans in Datenbank eintragen
                if ($scans) {
                    $num_fields = sizeof($scans[0]);
                    Log::write('debug', 'Numfields:'.$num_fields);
                    $tmpstr = '';
                    $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
                    for ($o = 0; $o < sizeof($scans); $o++)
                    {
                        $row = $scans[$o];
                        if ($i == 0) {
                            $tmpstr = 'INSERT INTO sync VALUES (';
                        }
                        else 
                        {
                            $tmpstr .= ',(';
                        }

                        for($j=0; $j < $num_fields; $j++) 
                        {
                            $row[$j] = addslashes($row[$j]);
                            $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                            if (isset($row[$j])) { $tmpstr .= '"'.$row[$j].'"' ; } else { $tmpstr .= '""'; }
                            if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                        }
                        $tmpstr .= ")";
                        $i++;

                        if (($i >= 10) || ($o == sizeof($scans) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
                        {
                            Log::write('debug', 'SQL:'.$tmpstr);
                            $statement = $connection->execute($tmpstr)->fetchAll('assoc');
                            $i = 0;
                        }
                    } 
                    $message = 'Neue Scans übertragen.';
                }
                else {
                    $message = 'Keine neuen Scans übertragen.';
                }
      
                Log::write('debug', print_r($scans, true));
                $message = 'Scans in Datenbank eingetragen!'; 
            }
            $this->set(compact('message'));
        }
        
        $this->viewBuilder()->setLayout('ajax');	        
    }
    
    
    
    /**
     * Sync method
     *
     * @param string|null $id Register id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function syncScans()
    {
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
        
        if ($this->request->is('post')) {
            $eingabe = $this->request->data;
            //$ip = '192.168.2.201';
            $ip = $eingabe['ip'];
            $maxwerte = json_decode($eingabe['maxwerte']);
            //$maxwerte = json_decode('{"settings":0,"users":0,"items":0}');
            //$maxwerte = json_decode('{"settings":"2018-12-31 23:00:00","users":"2018-06-09 21:21:21","items":"2017-06-09 19:35:28"}');
            
            // Ist Kasse bereits eingtragen?
            $query = $this->Registers->findByIp($ip);
            $kasse = $query->first();
            if ($kasse == null) // Kasse ist noch nicht eingetragen, also neu anlegen
            {
                $register = $this->Registers->newEntity();
                $register->ip = $ip;
                $register->active = true;
                $register->local = false;
                $register->syncEn = true;
                if ($this->Registers->save($register)) {
                    $message = 'Kasse neu angelegt';
                } else {
                    $message = 'Kasse konnte nicht angelegt werden';
                }             
            }
            else // Kasse ist bereits eingetragen. Nichts tun.
            {
                $message = 'Kasse bereits eingetragen';
            }
            $register = json_encode($kasse);
            
            
            // Neue Daten abfragen
            $connection = ConnectionManager::get('default');              
            $connection->execute('SET NAMES utf8');
            
            $sqlsync = array();
            $synctabelleda = true;
            
            foreach ($maxwerte as $tabelle => $maxval) {
                $$tabelle = array(); // Jede Tabelle wird in die entsprechende Variable geschrieben.
                if ($maxval == 0) // Noch keine Daten vorhaben? Dann muss auch noch ein CREATE TABLE gemacht werden
                {
                    $synctabelleda = false;
                    $sqlsync[] = 'DROP TABLE IF EXISTS '.$tabelle;
                    $statement = $connection->execute('SHOW CREATE TABLE '.$tabelle)->fetchAll('assoc');
                    $sqlsync[] = $statement[0]["Create Table"];                  
                }

                // Und jetzt noch die eigentlichen Daten auslesen
                // Da die Primärschlüssel erhalten bleiben müssen und bei diesen Tabellen nur auf dem
                // Server geschrieben werden dürfte und muss, werden die Daten samt Schlüssel übertragen.
                // Um mit den INSERTs nicht zu kollidieren, müssen ggf. vorhandene Zeilen vorm Insert gelöscht werden.
                $result = $connection->execute('SELECT * FROM '.$tabelle.' WHERE modified > "'.$maxval.'"')->fetchAll('num');
                $num_fields = sizeof($result) > 0 ? sizeof($result[0]) : 0;

                $tmpstr = '';
                $delstr = '';
                $i = 0; // Zähler wie viele Zeilen in ein Statement gepackt werden 
                for ($o = 0; $o < sizeof($result); $o++)
                {
                    $row = $result[$o];
                    if ($i == 0) {
                        $delstr = 'DELETE FROM '.$tabelle.' WHERE id IN (';
                        $tmpstr = 'INSERT INTO '.$tabelle.' VALUES (';
                    }
                    else 
                    {
                        $delstr .= ',';
                        $tmpstr .= ',(';
                    }
                    
                    $delstr .= $row[0]; // id ist jeweils die erste Spalte
                    for($j=0; $j < $num_fields; $j++) 
                    {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $tmpstr .= '"'.$row[$j].'"' ; } else { $tmpstr .= '""'; }
                        if ($j < ($num_fields-1)) { $tmpstr .= ','; }
                    }
                    $tmpstr .= ")";
                    $i++;
                    
                    if (($i >= 10) || ($o == sizeof($result) - 1)) // nach 10 Datensätzen oder wenn keine mehr übrig sind
                    {
                        array_push($$tabelle, $delstr.')');
                        array_push($$tabelle, utf8_encode($tmpstr));                      
                        $i = 0;
                    }
                }
            }
            
            // Wenn die anderen Tabellen nicht vorhanden sind, dann die Sync-Tabelle auch nicht
            if (!$synctabelleda)
            {
                $statement = $connection->execute('SHOW CREATE TABLE sync')->fetchAll('assoc');
                $sqlsync[] = $statement[0]["Create Table"];   
                $statement = $connection->execute('SHOW CREATE TABLE sales')->fetchAll('assoc');
                $sqlsync[] = $statement[0]["Create Table"];
                $statement = $connection->execute('SHOW CREATE TABLE itemsales')->fetchAll('assoc');
                $sqlsync[] = $statement[0]["Create Table"];                  
            }
            $this->set(compact('sqlsync', 'settings', 'users', 'items', 'message'));    
        }
        
        $this->layout = 'ajax';	        
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
