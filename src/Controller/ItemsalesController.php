<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Dompdf\Dompdf;
use Cake\Datasource\ConnectionManager;

/**
 * Itemsales Controller
 *
 * @property \App\Model\Table\ItemsalesTable $Itemsales
 *
 * @method \App\Model\Entity\Itemsale[] paginate($object = null, array $settings = [])
 */
class ItemsalesController extends AppController
{
    private $benutzer; // Variable für den derzeit verarbeiteten Benutzer
    
    private function _parseBrief($input) {
        $regex = '|@@(.+?)@@|';
        return preg_replace_callback($regex, array($this, '_ersetzungen'), $input);
    }
    
    // Ersetzungen beim Druck
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
    
    // Abrechnung drucken
    public function abrechnung_drucken($id = null) {

        //Configure::write('debug', 2);
        error_reporting(E_ERROR | E_WARNING | E_PARSE);

        $old_limit = ini_set('memory_limit', '128M');

        // Benutzer holen für Listennummer
        $this->loadModel('Users');
        $this->Users->recursive = 0;

        $zudrucken = '(';
        
        if ($this->request->is('post')) {
            foreach ($this->request->data['user_id'] as $uid => $wert) {
                if ($wert) $zudrucken .= $uid.',';
            }
            if ($zudrucken == '(') {
                $this->Flash->error(__('Kein Benutzer gewählt!'));
                return $this->redirect(['controller' => 'users', 'action' => 'indexAbrechnung']);            
            }
            $zudrucken .= '-1)';
        } elseif ($id != null) {
            $zudrucken = '('.$id.')';
        } else {
            $this->Flash->error(__('Kein Benutzer gewählt!'));
            return $this->redirect(['controller' => 'users', 'action' => 'indexAbrechnung']);            
        }

        //print_r($this->request->data['user_id']);
        //pr($zudrucken);
        // Alle Benutzer holen und durchlaufen
        $query = $this->Users->find('all', [
            'conditions' => ['Users.id in '.$zudrucken],
            'order' => ['Users.name', 'Users.vorname', 'Users.nummer']
        ]);

        $benutzerliste = $query->toArray();
        //print_r($zudrucken);
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
            $verkauft = $this->paginate($this->Itemsales);

            // Nicht verkaufte Artikel des Benutzers holen
            $this->paginate = [
                'limit' => 100,		
                'conditions' => [
                    'Itemsales.user_id' => $this->benutzer['id'],                    
                    'or' => [ 
                        'Itemsales.verkauft IS NULL',
                        'Itemsales.verkauft' => false
                    ]
                ],
                'order' => [
                    'Itemsales.nummer' => 'asc'
                ]
            ];
            $unverkauft = $this->paginate($this->Itemsales);            
            
            // Hat ein Benutzer keine Artikel abgegeben, dann muss nichts gedruckt werden
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
        
        $text = $this->_parseBrief($text); 
        //Log::write('debug', print_r($text, true));
        
        $dompdf = new Dompdf();
        $dompdf->load_html($text);
        $dompdf->render();
        $pdf = $dompdf->output();

        $this->response = $this->response->withDisabledCache();
        $response = $this->response;
        $response->body($pdf);
        $response = $response->withType('pdf');
        $response = $response->withDownload('Druckliste.pdf');

        // Return response object to prevent controller from trying to render a view.
        return $response;            
    }    
    
    
    public function unterschriftsliste() {
        $sql1 = <<<EOT
SELECT users.name, users.vorname, users.nummer, format(s2.einnahmen - s2.listengebuehr - s2.einbehalt, 2, "de_DE") as Einnahmen
FROM users LEFT JOIN
( SELECT user_id, SUM(einnahmen) AS einnahmen, SUM(anzverkauft) AS anzverkauft, SUM(anzartikel) AS anzartikel, 
EOT;

        $sql2 = <<<EOT
FROM ( SELECT user_id, SUM(preis) AS einnahmen, COUNT(*) AS anzverkauft, 0 AS anzartikel
    FROM `itemsales` WHERE verkauft = 1 GROUP BY user_id
    UNION
    SELECT user_id, 0, 0, COUNT(*) FROM `items` WHERE alt = false GROUP BY user_id
    ) AS s1
GROUP BY user_id
) AS s2 
ON users.id = s2.user_id
WHERE users.gruppe = 'V' AND users.emailok = 1 AND NOT s2.anzartikel IS NULL
ORDER BY users.name, users.vorname, users.nummer
EOT;
        
        $sql = $sql1.'CEIL(SUM(anzartikel) / '.AppController::getSetting('Listenlänge').') * '.AppController::getSetting('Listengebühr').' AS listengebuehr, ';
        $sql .= 'CEIL((SUM(einnahmen) * '.AppController::getSetting('Prozent').' / 100 ) * 2) / 2 AS einbehalt '.$sql2;
        
        $connection = ConnectionManager::get('default');        
        $namen = $connection->execute($sql)->fetchAll('assoc');
                
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        $old_limit = ini_set('memory_limit', '128M');
        
        $text = AppController::getSetting('Listenformatierung');
        $nummer = 0;
        
        foreach ($namen as $daten) {
            if ($nummer == 0) {
                $text .= '<div style="top:0cm; left:2cm; font-size: 20pt;">'.AppController::getSetting('Titel').'</div>';
                $text .= '<div class="gr18" style="top:1cm; left:-1.5cm;"><table width="19cm"><thead><tr>';
                $text .= '<th align="left">Name</th><th align="left">Vorname</th><th align="left">Liste</th><th align="right">Zahlbetrag</th>';
                $text .= '<th align="right">Unterschrift</th></tr></thead><tbody>';
            }            
            $text .= '<tr><td style="white-space:nowrap;">'.$daten['name'].'</td>';
            $text .= '<td style="white-space:nowrap;">'.$daten['vorname'].'</td>';
            $text .= '<td>'.$daten['nummer'].'&nbsp;</td>';
            $text .= '<td align="right">'.$daten['Einnahmen'].'&euro;</td>';
            $text .= '<td align="right">________________</td></tr>'."\n";            
            $nummer++;
            if ($nummer >= 23) {
                $nummer = 0;
                $text .= '</tbody></table></div>'."\n";                
                $text .= '<div style="page-break-after:always">&nbsp;</div>'."\n";
            }
        }   
        
        $text = $this->_parseBrief($text); 
        //Log::write('debug', print_r($text, true));
        
        $dompdf = new Dompdf();
        $dompdf->load_html($text);
        $dompdf->render();
        $pdf = $dompdf->output();

        $this->response = $this->response->withDisabledCache();
        $response = $this->response;
        $response->body($pdf);
        $response = $response->withType('pdf');
        $response = $response->withDownload('Druckliste.pdf');

        // Return response object to prevent controller from trying to render a view.
        return $response;                
                          
    }

    
    public function search() {
        $items = [];
        if ($this->request->is(['patch', 'post', 'put'])) {
            $barcode = $this->request->data['barcode'];
            $bez = $this->request->data['bezeichnung'];
            $groesse = $this->request->data['groesse'];
            $items = $this->Itemsales->find('all')
                ->where([
                    'Itemsales.barcode LIKE' => '%'.$barcode.'%',
                    'Itemsales.bezeichnung LIKE' => '%'.$bez.'%',
                    'Itemsales.groesse LIKE' => '%'.$groesse.'%'
                ]);
        }
        $this->set(compact('item'));
        $this->set('items', $items);
    }    
}
