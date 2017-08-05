<?php
namespace App\Controller;

use App\Controller\AppController;
use Dompdf\Dompdf;
use Cake\Log\Log;

/**
 * Items Controller
 *
 * @property \App\Model\Table\ItemsTable $Items
 */
class ItemsController extends AppController
{
//    private $code1;
//    private $code2;
    private $benutzer;
    
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->loadModel('Users');
        $this->Users->recursive = 0;
        $user = $this->Users->get($this->Auth->user('id'));
        $isadmin = false;
        
        if ($user['name'] == "") return $this->redirect(['controller' => 'Users', 'action' => 'edit', $this->Auth->user('id')]);

        $this->set(compact('user'));
        if ($user['gruppe'] == 'A') {
            $this->paginate = ['limit' => 100 ];
            $isadmin = true;
        }
        else $this->paginate = ['limit' => 100, 'conditions' => [ 'Items.user_id' => $this->Auth->user('id') ] ];
        $items = $this->paginate($this->Items);
        $this->set('items', $items);

        $voll = false;
        $erweiterbar = false;
        
        // Was ist es für ein Benutzer. Ein E-Mail-Benutzer kann Positionen nachkaufen. Ein Kauflistenbenutzer nur "Listenlänge"    
        if ($user->emailcode > 1) {
            if ($items->count() >= $user->maxitems) {
                $voll = true;
                if ($user->maxitems < AppController::getSetting('ListenlängeEMailMax')) {
                    $erweiterbar = true;
                }
            }           
        } else {
            if ($items->count() >= $user->maxitems) {
		$voll = true;
            }
        }
        $this->set('aendernMoeglich', AppController::getSetting('Ändern möglich'));
        $this->set('erweiterbar', $erweiterbar); 
        $this->set('voll', $voll);   
        $this->set('admin', $isadmin);         
        $this->set('_serialize', ['user', 'items']);
    }


    function drucken($id = null) {

        //Configure::write('debug', 2);
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

        $old_limit = ini_set('memory_limit', '128M');
        require_once(ROOT . DS . 'vendor/dompdf/dompdf_config.inc.php');

        // Benutzer holen für Listennummer
        $this->loadModel('Users');
        $this->Users->recursive = 0;
        
        // Admin darf jede Liste drucken, der angemeldete Benutzer nur seine eigene
        if ($this->Auth->user('gruppe') == 'A') {
            $this->benutzer = $this->Users->get($id);
        } else {
            $this->benutzer = $this->Users->get($this->Auth->user('id'));
        }

	// Artikel des Benutzers holen
        $this->paginate = [
            'limit' => 100,		
            'conditions' => [
                'Items.user_id' => $this->benutzer['id'],
            ],
            'order' => [
                'Items.nummer' => 'asc'
            ]
        ];
        $liste = $this->paginate($this->Items);

        $text = AppController::getSetting('Listenformatierung');
        $text .= AppController::getSetting('Listenkopf');
                
        $nummer = 0;
        $links = 8.8;
        // Seite(n) mit Barcodes drucken
        foreach ($liste as $daten) {
            $oben = ((int)($nummer / 2)) * 3.3 + 0.1;
            $links = 8.8 - $links;

            $code = $daten['barcode'];
            $text .= '<div class="hier" style="top:'.($oben - 0.2).'cm; left:'.$links.'cm; ">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />Hier<br />lochen und<br />befestigen</div>';
            $text .= '<div class="barcode" style="top:'.($oben - 0.1).'cm; left:'.($links + 1.85).'cm;">'.self::ean8($code, true).'&nbsp;</div>';
            $text .= '<div class="hier" style="top:'.($oben - 0.2).'cm; left:'.($links + 6.8).'cm; margin-top: 60px;">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />Hier<br />lochen und<br />befestigen</div>';
            if ($daten['groesse'] != "") {
                $groesse = "Gr.".$daten['groesse'];
            }
            else $groesse = "&nbsp;";
            $text .= '<div class="groesseR" style="top:'.($oben + 1.5).'cm; left:'.($links + 1.6).'cm;">&nbsp;</div>'."\n";
            $text .= '<div class="preisR" style="top:'.($oben + 1.5).'cm; left:'.($links + 4.2).'cm;">&nbsp;</div>'."\n";
            $text .= '<div class="textR" style="top:'.($oben + 2.3).'cm; left:'.($links + 1.6).'cm;">&nbsp;</div>'."\n";

            if (strlen($groesse) > 8) {
                $text .= '<div class="groesse" style="top:'.($oben + 1.8).'cm; left:'.($links + 1.6).'cm; font-size: 8pt;">'.$groesse.'</div>'."\n";
            } else {
                $text .= '<div class="groesse" style="top:'.($oben + 1.6).'cm; left:'.($links + 1.6).'cm;">'.$groesse.'</div>'."\n";
            }
            $text .= '<div class="preis" style="top:'.($oben + 1.6).'cm; left:'.($links + 4.2).'cm;">'.number_format($daten['preis'], 2, ',', '.').'&euro;</div>'."\n";
            if (strlen($daten['bezeichnung']) > 20) {
                //$daten['bezeichnung'] = substr($daten['bezeichnung'], 0, 25).'<br/>'.substr($daten['bezeichnung'], 25, 25);
                $text .= '<div class="text" style="top:'.($oben + 2.3).'cm; left:'.($links + 1.6).'cm; font-size: 8pt;">'.
                        substr($daten['bezeichnung'], 0, 25).'<br/>'.substr($daten['bezeichnung'], 25, 25).'&nbsp;</div>'."\n";
            } else {
                $text .= '<div class="text" style="top:'.($oben + 2.5).'cm; left:'.($links + 1.6).'cm;">'.$daten['bezeichnung'].'&nbsp;</div>'."\n";
            }
            $text .= '<div class="rahmen" style="top:'.($oben).'cm; left:'.($links).'cm;">&nbsp;</div>'."\n";
            $nummer++;
            if ($nummer >= 16) {
                $nummer = 0;
                $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
            }
        }
        
        // Seite(n) für eigene Übersicht drucken
        $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
        $nummer = 0;      
        $links = 0;
    
        foreach ($liste as $daten) {

            if ($nummer == 0) {
                $text .= '<div style="top:1cm; left:0cm; font-size: 16pt;">Kontrollausdruck für Liste @@LISTE@@: @@NAME@@ </div>';
                $text .= '<div style="top:2.5cm; left:-1cm; font-size: 14pt;"><table width="18cm"><thead><tr>';
                $text .= '<th align="left">Nummer</th><th align="left">Bezeichnung</th><th align="left">Größe</th><th align="right">Preis</th>';
                $text .= '</tr></thead><tbody>';
            }
            
            $oben = $nummer * 1.3 + 0.1;
            $text .= '<tr><td>'.$daten['barcode'].'</td>';
            $text .= '<td>'.$daten['bezeichnung'].'</td>';
            $text .= '<td>'.$daten['groesse'].'&nbsp;</td>';
            $text .= '<td align="right">'.number_format($daten['preis'], 2, ',', '.').'&euro;</td></tr>'."\n";
            /*
            if ($daten['groesse'] != "") {
                $groesse = "Gr.".$daten['groesse'];
            }
            else $groesse = "&nbsp;";            
            $text .= '<div class="text" style="top:'.($oben + 2.5).'cm; left:'.($links + 1.0).'cm; font-size: 14pt;">'.
                    substr($daten['bezeichnung'].' '.$groesse, 0, 25).'&nbsp;</div>'."\n";
           
            $text .= '<div class="preis" style="top:'.($oben + 2.5).'cm; left:'.($links + 9.2).'cm;">'.number_format($daten['preis'], 2, ',', '.').'&euro;</div>'."\n";
            
            */
            $nummer++;
            if ($nummer >= 30) {
                $nummer = 0;
                $text .= '</tbody></table></div>'."\n";                
                $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
            }
        }
        if ($nummer > 0) $text .= '</tbody></table></div>'."\n";        

        $text = $this->_parseBrief($text); 
                
        $dompdf = new \DOMPDF();
        $dompdf->load_html($text);
        $dompdf->render();
        $dompdf->stream("Druckliste.pdf", array("Attachment" => false));
    }

    function abrechnung_drucken($id = null) {

        //Configure::write('debug', 2);
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

        $old_limit = ini_set('memory_limit', '128M');
        require_once(ROOT . DS . 'vendor/dompdf/dompdf_config.inc.php');

        // Benutzer holen für Listennummer
        $this->loadModel('Users');
        $this->Users->recursive = 0;

        $zudrucken = '(';
        
        if ($this->request->is('post')) {
            foreach ($this->request->data['user_id'] as $uid => $wert) {
                if ($wert) $zudrucken .= $uid.',';
            }
            $zudrucken .= '-1)';
        } elseif ($id != null) {
            $zudrucken = '('.$id.')';
        } else {
            $this->Flash->error(__('Kein Benutzer gewählt!'));
            return $this->redirect(['controller' => 'users', 'action' => 'index_abrechnung']);            
        }

        //print_r($this->request->data['user_id']);
        //pr($zudrucken);
        // Alle Benutzer holen und durchlaufen
        $query = $this->Users->find('all', [
            'conditions' => ['Users.id in '.$zudrucken],
            'order' => ['Users.name', 'Users.vorname', 'Users.nummer']
        ]);

        $benutzerliste = $query->toArray();
        //print_r($benutzerliste);
        //return;

        $text = AppController::getSetting('Listenformatierung');     
        $zab = 0.7;
        $top = 1.0;
                    
        // Schleife über alle Benutzer
        foreach ($benutzerliste as $this->benutzer) {
            // Verkaufte Artikel des Benutzers holen
            $this->paginate = [
                'limit' => 100,		
                'conditions' => [
                    'Items.user_id' => $this->benutzer['id'],                    
                    'Items.verkauft' => true,
                ],
                'order' => [
                    'Items.nummer' => 'asc'
                ]
            ];
            $verkauft = $this->paginate($this->Items);

            // Nicht verkaufte Artikel des Benutzers holen
            $this->paginate = [
                'limit' => 100,		
                'conditions' => [
                    'Items.user_id' => $this->benutzer['id'],                    
                    'Items.verkauft' => false,
                ],
                'order' => [
                    'Items.nummer' => 'asc'
                ]
            ];
            $unverkauft = $this->paginate($this->Items);            
            
            // Hat ein Benutzer keine Artikel abgegeben, dann muss nihts gedruckt werden
            $anzArtikel = sizeof($verkauft) + sizeof($unverkauft);
            if (sizeof($verkauft) + sizeof($unverkauft) == 0) continue;
            
//            $jahr = AppController::getSetting('Jahr'); 
//            // Barcode setzt sich zusammen aus dreistelliger Listennummer und dem Jahr
//            $this->code1 = sprintf("%'.03d", $this->benutzer['nummer']).substr($jahr, strlen($jahr) - 2, 1);
//            $this->code2 = substr($jahr, strlen($jahr) - 1, 1);

            $nummer = 0;      
            $links = 0;
            $summeVerkauft = 0.0;

            if (sizeof($verkauft) == 0) {
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:0cm; font-size: 18pt;">Abrechnung für Liste '.$this->benutzer['nummer'].': '.
                        $this->benutzer['name'].' '.$this->benutzer['vorname'].'</div>';
                $nummer += 1;
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Verkaufte Artikel (0)</div>';              
            }

            foreach ($verkauft as $daten) {
                if ($nummer == 0) {
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:0cm; font-size: 18pt;">Abrechnung für Liste '.$this->benutzer['nummer'].': '.
                            $this->benutzer['name'].' '.$this->benutzer['vorname'].'</div>';
                    $nummer += 1;
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Verkaufte Artikel ('.sizeof($verkauft).'/'.$anzArtikel.')</div>';
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 13pt;"><table width="18cm"><thead><tr>';
                    $text .= '<th align="left">Nummer</th><th align="left">Bezeichnung</th><th align="left">Größe</th><th align="right">Preis</th>';
                    $text .= '</tr></thead><tbody>';
                }
                $code = $this->code1.sprintf("%'.02d", $daten['nummer']).$this->code2;   
                $text .= '<tr><td>'.$code.'</td>';
                $text .= '<td>'.substr($daten['bezeichnung'],0,40).'</td>';
                $text .= '<td>'.$daten['groesse'].'&nbsp;</td>';
                $text .= '<td align="right">'.number_format($daten['preis'], 2, ',', '.').'&euro;</td></tr>'."\n";
                $nummer++;
                $summeVerkauft += $daten['preis'];
                if ($nummer >= 38) {
                    $nummer = 0;
                    $text .= '</tbody></table></div>'."\n";                
                    $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
                }
            }
            
            // Wenn Seite nicht grad aus war, Tabelle schließen
            if ($nummer > 0) {
                $text .= '</tbody></table></div>'."\n"; 
            }
            
            // Summe ausgeben
            $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 12pt; text-align: left;">Verkaufserl&ouml;s: '.
                        number_format($summeVerkauft, 2, ',', '.').'&euro;</div>';                
            $anzartikel = sizeof($verkauft) + sizeof($unverkauft);
            $listengebuehr = ceil(( $anzartikel ) / 30) * 2;
            $verkaufsanteil = ( ceil(($summeVerkauft * 0.15) * 2) / 2);
            $nummer -= 0.3;
            $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 12pt; text-align: left;">Listengeb&uuml;hr: '.
                        number_format($listengebuehr, 2, ',', '.').'&euro; (f&uuml;r bis '.($listengebuehr * 15).' Artikel) + Verkaufsanteil: 15% von '.
                        number_format($summeVerkauft, 2, ',', '.').'&euro; = '.number_format($verkaufsanteil, 2, ',', '.').'&euro;</div>';
            $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Zahlbetrag: '.
                        number_format($summeVerkauft - $listengebuehr - $verkaufsanteil, 2, ',', '.').'&euro;</div>';
            $nummer++;
            
            // Unverkaufte Artikel ausgeben
            if ($nummer >= 30) {
                $nummer = 0;               
                $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:0cm; font-size: 18pt;">Abrechnung für Liste '.$this->benutzer['nummer'].': '.
                    $this->benutzer['name'].' '.$this->benutzer['vorname'].'</div>';
                $nummer += 1;
            }
                        
            if (sizeof($unverkauft) == 0) {
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Unverkaufte Artikel (0)</div>';              
            }
            
            if ($nummer > 0) {
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Unverkaufte Artikel ('.sizeof($unverkauft).'/'.$anzArtikel.')</div>';
                $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 13pt;"><table width="18cm"><thead><tr>';
                $text .= '<th align="left">Nummer</th><th align="left">Bezeichnung</th><th align="left">Größe</th><th align="right">Preis</th>';
                $text .= '</tr></thead><tbody>';
            }    
            
            foreach ($unverkauft as $daten) {
                if ($nummer == 0) {
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:0cm; font-size: 18pt;">Abrechnung für Liste '.$this->benutzer['nummer'].': '.
                        $this->benutzer['name'].' '.$this->benutzer['vorname'].'</div>';   
                    $nummer += 1;
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 14pt; text-align: left;">Unverkaufte Artikel ('.sizeof($unverkauft).'/'.$anzArtikel.')</div>';
                    $text .= '<div style="top:'.($nummer++ * $zab + $top).'cm; left:-1cm; font-size: 13pt;"><table width="18cm"><thead><tr>';
                    $text .= '<th align="left">Nummer</th><th align="left">Bezeichnung</th><th align="left">Größe</th><th align="right">Preis</th>';
                    $text .= '</tr></thead><tbody>';
                }
                $code = $this->code1.sprintf("%'.02d", $daten['nummer']).$this->code2;   
                $text .= '<tr><td>'.$code.'</td>';
                $text .= '<td>'.$daten['bezeichnung'].'</td>';
                $text .= '<td>'.$daten['groesse'].'&nbsp;</td>';
                $text .= '<td align="right">'.number_format($daten['preis'], 2, ',', '.').'&euro;</td></tr>'."\n";
                $nummer++;
                if ($nummer >= 38) {
                    $nummer = 0;
                    $text .= '</tbody></table></div>'."\n";                
                    $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
                }
            }             

            // nächster Benutzer 
            if ($nummer > 0) {
                $nummer = 0;
                $text .= '</tbody></table></div>'."\n";                
                $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
            }
        }    
        
        //pr($text);
        $text = $this->_parseBrief($text); 
                
        $dompdf = new \DOMPDF();
        $dompdf->load_html($text);
        $dompdf->render();
        $dompdf->stream("Druckliste.pdf", array("Attachment" => false));
    }    
    
    
    function _parseBrief($input) {
        $regex = '|@@(.+?)@@|';
        //$input = preg_replace_callback($regex, array($this, '_ersetzungen'), $input);
        //$input = preg_replace_callback($regex, array($this, '_ersetzungen'), $input);
        return preg_replace_callback($regex, array($this, '_ersetzungen'), $input);
    }

    /**
     * View method
     *
     * @param string|null $id Item id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $item = $this->Items->get($id, [
                'contain' => ['Users']
            ]);
        $this->set('item', $item);
        $this->set('_serialize', ['item']);
    }

	
    public function ajaxview()
    {
        $this->paginate = ['limit' => 5 ];
        $this->set('item', $this->paginate($this->Items));

        //$id = $this->request->query('scan');
        //if ($id == null) $id = 21;
        //$item = $this->Items->get($id);
        //$this->set('item', $item);
        //$this->set('_serialize', ['item']);
        $this->layout = 'ajax';		
    }	
	
	
    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        // In a controller or table method.
        $query = $this->Items->find('all', [
            'conditions' => ['Items.user_id =' => $this->Auth->user('id')]
            ]);
        $number = $query->count();
        
        $this->loadModel('Users');
        $this->Users->recursive = 0;
        $benutzer = $this->Users->get($this->Auth->user('id'));
        
        // Was ist es für ein Benutzer. Ein E-Mail-Benutzer kann Positionen nachkaufen. Ein Kauflistenbenutzer nur "Listenlänge"    
        if ($benutzer->emailcode > 1) {
            if ($number >= $benutzer->maxitems) {
                $this->Flash->success(__('Sie haben bereits '.$number.' Artikel angelegt. Mehr sind pro Liste nicht erlaubt.'));
                return $this->redirect(['action' => 'index']);			
            }           
        } else {
            if ($number >= $benutzer->maxitems) {
                $this->Flash->success(__('Sie haben bereits '.$number.' Artikel angelegt. Mehr sind pro Liste nicht erlaubt.'));
                return $this->redirect(['action' => 'index']);			
            }
        }

        $item = $this->Items->newEntity();
        if ($this->request->is('post')) {
            $this->request->data['preis'] = str_replace(",", ".", $this->request->data['preisdt']);	
            $this->request->data['preisdt'] = str_replace(",", ".", $this->request->data['preisdt']);
            $item = $this->Items->patchEntity($item, $this->request->data);
            $item->user_id = $this->Auth->user('id');

            $lastmax = $this->Items->find('all', 
                array('fields' => array('Items.nummer'), 
                    'conditions' => array('Items.user_id =' => $this->Auth->user('id')),
                    'order' => array('Items.nummer')
                )
            );
            $next = 1;

            foreach ($lastmax as $teil) {
                if ($teil['nummer'] != $next) break;
                $next++;
            }
            $item->nummer = $next;

            //Barcode erzeugen
            $item->barcode = $this->barcodeerzeugen($benutzer, $item);

            if ($this->Items->save($item)) {
                $this->Flash->success(__('Artikel gesichert'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->request->data['preisdt'] = str_replace(".", ",", sprintf("%01.2f", $this->request->data['preisdt']));
                $this->Flash->error(__('Der Artikel konnte nicht gesichert werden. Bitte nochmals versuchen.'));
            }
        }
        $this->set(compact('item'));
        $this->set('_serialize', ['item']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Item id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        // Admin darf jeden Artikel ändern, der angemeldete Benutzer nur seine eigenen
        if ($this->Auth->user('gruppe') == 'A') {
            $admin = true;
            $item = $this->Items->get($id);
        } else {
            $admin = false;
            if (AppController::getSetting('Ändern möglich')) {
                $item = $this->Items->get($id, [
                    'contain' => [],
                    'conditions' => [ 'Items.user_id' => $this->Auth->user('id') ]
                ]);
            } else {
                $this->Flash->error(__('Keine Berechtigung!'));
                return $this->redirect(['action' => 'index']);
            }
        }
        
        if ($this->request->is(['patch', 'post', 'put'])) {
            $this->request->data['preis'] = str_replace(",", ".", $this->request->data['preisdt']);		
            $this->request->data['preisdt'] = str_replace(",", ".", $this->request->data['preisdt']);
            $item = $this->Items->patchEntity($item, $this->request->data);
            
            // Admin ändert nur bestehende Artikel, daher bleibt Userid beim speicher unverändert.
            // Bei allen anderen die User_id nochmals schreiben, dass der Artikel keinem anderen Verkäufer zugeordnet wird.
            if (strtoupper($this->Auth->user('nummer')) != 'ADMIN') {
                $item->user_id = $this->Auth->user('id');
            }

            // Wegen benötigter Listennummer den zugehörigen Benutzer holen
            $this->loadModel('Users');
            $this->Users->recursive = 0;
            $benutzer = $this->Users->get($item->user_id);            
            $item->barcode = $this->barcodeerzeugen($benutzer, $item);            

            if ($this->Items->save($item)) {
                $this->Flash->success(__('Artikel gesichert'));
                if ($admin) {
                    return $this->redirect(['controller' => 'Users', 'action' => 'view/'.$item->user_id]);
                }
                else {
                    return $this->redirect(['action' => 'index']);
                }
            } else {
                $this->request->data['preisdt'] = str_replace(".", ",", sprintf("%01.2f", $this->request->data['preisdt']));
                $this->Flash->error(__('Der Artikel konnte nicht gesichert werden. Bitte nochmals versuchen.'));
            }
        }
        //pr("!!!".$item->preis."!!!");
        //pr("!!!".$item->preisdt."!!!");
        $this->set(compact('item'));
        $this->set('_serialize', ['item']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Item id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        // Admin darf jeden Artikel ändern, der angemeldete Benutzer nur seine eigenen
        if ($this->Auth->user('gruppe') == 'A') {
            $admin = true;
            $item = $this->Items->get($id);
        } else {
            $admin = false;
            if (AppController::getSetting('Ändern möglich')) {
                $item = $this->Items->get($id, [
                    'contain' => [],
                    'conditions' => [ 'Items.user_id' => $this->Auth->user('id') ]
                ]);
            } else {
                $this->Flash->error(__('Keine Berechtigung!'));
                return $this->redirect(['action' => 'index']);
            }
        }        
        
        if ($this->Items->delete($item)) {
            $this->Flash->success(__('Artikel gelöscht'));
            if ($admin) {
                return $this->redirect(['controller' => 'Users', 'action' => 'view/'.$item->user_id]);
            }
            else {
                return $this->redirect(['action' => 'index']);
            }
        } else {
            $this->Flash->error(__('Der Artikel konnte nicht gelöscht werden. Bitte nochmals versuchen.'));
        }
        return $this->redirect(['action' => 'index']);
    }

    public function isAuthorized($user)
    {
        if ($user['gruppe'] === "A") {
            return true;
        }

        $action = $this->request->params['action'];

        // The add and index actions are always allowed.
        if (in_array($action, ['index', 'add', 'drucken'])) {
            return true;
        }

        // All other actions require an id.
        if (empty($this->request->params['pass'][0])) {
            return false;
        }

        // Check that the item belongs to the current user.
        $id = $this->request->params['pass'][0];
        $item = $this->Items->get($id);
        if ($item->user_id == $user['id']) {
            return true;
        }
        return parent::isAuthorized($user);
    }
    
    public function beforeFilter(\Cake\Event\Event $event) {
	parent::beforeFilter($event);
    }    

    private function _ersetzungen($input) {
        switch ($input[1]) {
            case 'NAME':    
                $input = $this->benutzer['vorname'].' '.$this->benutzer['name'];
                break;
            case 'LISTE':
                $input = $this->benutzer['nummer'];
                break;     
            case 'NEUESEITE':
                $input = '<div style="page-break-after:always">&nbsp;</div>';
                break;
            case 'BARCODE':
                //$input = self::ean8($this->code1.'00'.$this->code2, false);
                $input = self::ean8($this->barcodeerzeugen($this->benutzer, null), false);
                break;                
            default:
                $input = $input[0];
                break;
        }
        return $input;
    }

    // Pseudoprüfziffer für Betrag erzeugen
    public static function betragcheck($betrag, $eineZiffer = true) {
        $zahltext = sprintf("%'.07d", (int) ($betrag * 100));
        
        // Prüfziffernberechnung beginnt bei der letzten Stelle des Centbetrags und multipliziert
        // diese mit 5, die vorletzte mit 7, die drittletzte mit 9 usw.
        $factor = 5;
        $weightedTotal = 0;
        for ($i = strlen($zahltext) - 1; $i >= 0; $i--) {
            $weightedTotal += $factor * ((int)substr($zahltext, $i, 1));
            $factor = $factor + 2;
        }
        
        // Davon die Quersumme und davon die letzte Stelle
        if ($eineZiffer) {
            $check = ( self::crossfoot($weightedTotal) % 10 );
        } else {
            $check = sprintf("%'.02d", self::crossfoot($weightedTotal));           
        }

        return $check;        
    }
    
    // Quersumme einer Zahl berechnen
    private static function crossfoot ( $digits )
    { 
        // Typcast falls Integer uebergeben
        $strDigits = ( string ) $digits;

        for( $intCrossfoot = $i = 0; $i < strlen ( $strDigits ); $i++ )
        {
          $intCrossfoot += $strDigits{$i};
        }

        return $intCrossfoot;
    } 
    
    public function barcodeerzeugen($benutzer, $item) {
//        // Barcode setzt sich zusammen aus dreistelliger Listennummer und Betragsprüfziffer
//        $code =  sprintf("%'.03d", $benutzer['nummer']);
//        $code .= self::betragcheck($item['preis']);
//        
//        // sowie der Nr des Artikels und der letzten Stelle des Jahr
//        $code .= sprintf("%'.02d", $item['nummer']);
//        $jahr = AppController::getSetting('Jahr'); 
//        $code .= substr($jahr, strlen($jahr) - 1, 1);

        // Barcode setzt sich zusammen aus dreistelliger Listennummer und Betragsprüfziffer
        $code =  sprintf("%'.03d", $benutzer['nummer']);
        $code .= self::betragcheck($item['preis'], false);
        
        // sowie der Nr des Artikels und der letzten Stelle des Jahr
        $code .= sprintf("%'.02d", $item['nummer']);
        $jahr = AppController::getSetting('Jahr'); 
        $code .= substr($jahr, strlen($jahr) - 2, 2);

        // ergänzt um die Prüfziffer
        return self::ean8check($code);
    }
    
    // Prüfziffer errechnen
    public static function ean8check($zahltext) {
        if (strlen($zahltext) != 7 && strlen($zahltext) != 9) return "00000000";
        
        $factor = 3;
        $weightedTotal = 0;
        for ($i = strlen($zahltext) - 1; $i >= 0; $i--) {
            $weightedTotal += $factor * ((int)substr($zahltext, $i, 1));
            $factor = 4 - $factor;
        }
        $check = ($weightedTotal % 10);
        if ($check != 0) $check = 10 - $check; 
        return $zahltext.$check;
    }
    
    
    public static function ean8($dataToEncode, $zahlanzeigen = true) {
        if (strlen($dataToEncode) != 8 && strlen($dataToEncode) != 10)  return "(0000*0000(";

        $halb = strlen($dataToEncode) / 2 - 1;
        $dataToPrint = "(";
        if ($zahlanzeigen) {
            for ($i = 0; $i < strlen($dataToEncode); $i++) {
                if ($i < $halb) {
                    $dataToPrint .= $dataToEncode[$i];
                }
                elseif ($i == $halb) {
                    $dataToPrint .= $dataToEncode[$i].'*';
                }
                else { 
                    $dataToPrint .= chr(ord($dataToEncode[$i]) + 27);
                }
            }
        } else {
            for ($i = 0; $i < strlen($dataToEncode); $i++) {
                if ($i < $halb) {
                    $dataToPrint .= chr(ord($dataToEncode[$i]) + 49);
                }
                elseif ($i == $halb) {
                    $dataToPrint .= chr(ord($dataToEncode[$i]) + 49).'*';
                }
                else { 
                    $dataToPrint .= chr(ord($dataToEncode[$i]) + 59);
                }
            }
        }
        return $dataToPrint.'(';
    }
}
