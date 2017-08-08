<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Itemsales Controller
 *
 * @property \App\Model\Table\ItemsalesTable $Itemsales
 *
 * @method \App\Model\Entity\Itemsale[] paginate($object = null, array $settings = [])
 */
class ItemsalesController extends AppController
{

//    /**
//     * Index method
//     *
//     * @return \Cake\Http\Response|void
//     */
//    public function index()
//    {
//        $this->paginate = [
//            'contain' => ['Users']
//        ];
//        $itemsales = $this->paginate($this->Itemsales);
//
//        $this->set(compact('itemsales'));
//        $this->set('_serialize', ['itemsales']);
//    }
//
//    /**
//     * View method
//     *
//     * @param string|null $id Itemsale id.
//     * @return \Cake\Http\Response|void
//     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
//     */
//    public function view($id = null)
//    {
//        $itemsale = $this->Itemsales->get($id, [
//            'contain' => ['Users']
//        ]);
//
//        $this->set('itemsale', $itemsale);
//        $this->set('_serialize', ['itemsale']);
//    }

    
    // Abrechnung drucken
    public function abrechnung_drucken($id = null) {

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
                    'Itemsales.user_id' => $this->benutzer['id'],                    
                    'Itemsales.verkauft' => true,
                ],
                'order' => [
                    'Itemsales.nummer' => 'asc'
                ]
            ];
            $verkauft = $this->paginate($this->Items);

            // Nicht verkaufte Artikel des Benutzers holen
            $this->paginate = [
                'limit' => 100,		
                'conditions' => [
                    'Itemsales.user_id' => $this->benutzer['id'],                    
                    'Itemsales.verkauft' => false,
                ],
                'order' => [
                    'Itemsales.nummer' => 'asc'
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
}