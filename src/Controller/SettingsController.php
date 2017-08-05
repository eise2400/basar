<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;

/**
 * Settings Controller
 *
 * @property \App\Model\Table\SettingsTable $Settings
 */
class SettingsController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->paginate = [
            'limit' => 30,		
            'order' => [ 'Users.name' => 'asc' ]
        ];
        $this->set('settings', $this->paginate($this->Settings));
        $this->set('_serialize', ['settings']);
    }


    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $setting = $this->Settings->newEntity();
        if ($this->request->is('post')) {
            $setting = $this->Settings->patchEntity($setting, $this->request->data);
            if ($this->Settings->save($setting)) {
                $this->Flash->success(__('Die neue Einstellung wurde gespeichert.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The setting could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('setting'));
        $this->set('_serialize', ['setting']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Setting id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $setting = $this->Settings->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $setting = $this->Settings->patchEntity($setting, $this->request->data);
            if ($this->Settings->save($setting)) {
                $this->Flash->success(__('Die Einstellung wurde gespeichert.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Die Einstellung konnte nicht gespeichert werden.'));
            }
        }
        $this->set(compact('setting'));
        $this->set('_serialize', ['setting']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Setting id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $setting = $this->Settings->get($id);
        if ($this->Settings->delete($setting)) {
            $this->Flash->success(__('The setting has been deleted.'));
        } else {
            $this->Flash->error(__('The setting could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }
    
    // "Startseite" für den Admin-Login
    public function admin()
    {
        $connection = ConnectionManager::get('default');
        //$results = $connection->execute('SELECT * FROM articles')->fetchAll('assoc');

        // Anzahl Benutzer, Maximal mögliche Items bisher
        $benutzer = $connection->execute('SELECT count(*) as `Gesamtzahl Benutzer`, sum(maxitems) AS `max. mögliche Anzahl Teile` FROM `users` WHERE 1')->fetchAll('assoc');
        $daten[] = $benutzer[0];
        
        // E-Mail Benutzer
        $emailer = $connection->execute('SELECT count(users.id) AS `Anzahl E-Mail Benutzer` FROM `users` where emailcode>1')->fetchAll('assoc');
        $daten[] = $emailer[0];
        
        // Anzahl Benutzer mit Teilen
        $mitteilen = $connection->execute('SELECT count(UID) AS `Anzahl Benutzer mit min. einem Teil`, sum(anzahl) AS `Anzahl eingetragener Teile` FROM ('.
                'SELECT users.id AS UID, count(items.id) AS anzahl FROM `users` left join `items` on users.id = items.user_id '.
                'group by users.id having anzahl > 0) as s')->fetchAll('assoc');
        $daten[] = $mitteilen[0];
        
        // Anzahl Teile
        $teile1 = $connection->execute('SELECT count(*) AS `Anzahl verkaufter Teile` FROM `itemsales` where verkauft = true')->fetchAll('assoc');
        $daten[] = $teile1[0];        

        // Verkaufserlös
        $teile2 = $connection->execute('SELECT concat(replace(cast(sum(preis) as char), \'.\', \',\'),\'€\') AS `Verkaufserlös` FROM `itemsales` where verkauft = true')->fetchAll('assoc');
        $daten[] = $teile2[0]; 
        
        // Gewinn Basarteam
        $teile3 = $connection->execute('SELECT concat(replace(cast( round(sum(betrag),2) as char), \'.\', \',\'),\'€\') AS `Prozentanteil Basar` FROM ( SELECT id, sum( ceil(summe*2) / 2) AS betrag FROM '.
                '( SELECT users.id AS id, sum(preis*prozentsatz/100) AS summe FROM `itemsales` left join users on itemsales.user_id = users.id '.
                'WHERE verkauft = true GROUP BY users.id ) AS unter GROUP BY id ) AS ober')->fetchAll('assoc');
        $daten[] = $teile3[0];

        // Listengebühren
        $teile4 = $connection->execute('select concat(replace(cast(sum(ceil(teile/30)*2) * 1.00 as char), \'.\', \',\'),\'€\') AS `Listengebühren` from (Select users.id AS benutzer, '.
                'count(items.id) AS teile from items left join users on items.user_id = users.id where ist_da = true '.
                'group by users.id) AS sub')->fetchAll('assoc');        
        $daten[] = $teile4[0];
        
        // Anzahl Käufer
        $teile5 = $connection->execute('select count(*) AS `Anzahl Käufer` from (SELECT count(*) FROM `sync` group by sync.created) as s')->fetchAll('assoc');
        $daten[] = $teile5[0]; 
        
        
        
        $this->set(compact('daten'));
        $this->set('_serialize', ['daten']);
    }    
    
    public function isAuthorized($user)
    {
        if ($user['gruppe'] == "A") {
            return true;
        }
        else 
        {
            return false;
        }
    }  
    
    
    public function import() {
        // Datei einlesen
        // Bei Users gebuehr neu berechnen und ggf. prozentsatz aus settings schreiben
    }
    
    
//    public function eanSetzen() {
//        $jahr = AppController::getSetting('Jahr'); 
//        $code2 = substr($jahr, strlen($jahr) - 1, 1);
//
// 	$connection = ConnectionManager::get('default');
//        $result = $connection->execute('SELECT items.id AS id, items.nummer AS itnr, users.nummer AS usnr '.
//                'FROM items LEFT JOIN users ON user_id = users.id')->fetchAll('num');
//        for ($o = 0; $o < sizeof($result); $o++) {
//            $code1 = sprintf("%'.03d", $result[$o][2].substr($jahr, strlen($jahr) - 2, 1));
//            $code = $code1.sprintf("%'.02d", $result[$o][1]).$code2; 
//            $barcode = ItemsController::ean8check($code);         
//            $connection->execute('UPDATE items SET barcode = '.$barcode.' WHERE id = '.$result[$o][0]);
//        }
//    }
    
    public function export($tables = ['settings', 'users', 'items'])
    {	
	// $this->eanSetzen();
        
        $connection = ConnectionManager::get('default');        
        $return = '';
        
        //get all of the tables
	if($tables == '*')
	{
            $tables = array();
            $result = mysql_query('SHOW TABLES');
            while($row = mysql_fetch_row($result))
            {
                $tables[] = $row[0];
            }
	}
	else
	{
            $tables = is_array($tables) ? $tables : explode(',',$tables);
	}
	
	//cycle through
	foreach($tables as $table)
	{
            $result = $connection->execute('SELECT * FROM '.$table)->fetchAll('num');
            $num_fields = sizeof($result[0]);
            $return.= 'DROP TABLE IF EXISTS '.$table.';';
            $row2 = $connection->execute('SHOW CREATE TABLE '.$table)->fetchAll('assoc');
            $return.= "\n\n".$row2[0]["Create Table"].";\n\n";

            for ($o = 0; $o < sizeof($result); $o++)
            {
                $row = $result[$o];
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j < $num_fields; $j++) 
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j < ($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }

            $return.="\n\n\n";
	}
	/*
	$this->set('sql', $return);
	$this->layout = 'plaintxt';
	$this->set('filename', 'basar.sql');
        */
        $this->response->body($return);
        $this->response->type('txt');

        // Optionally force file download
        $this->response->download('basar.sql');

        // Return response object to prevent controller from trying to render
        // a view.
        return $this->response;        
    }
    
    
}
