<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Network\Email\Email;
use Cake\I18n\Time;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{  
    private $werte = [];
    private static $jahr;
    public $paginate = [
        'limit' => 50,
        'order' => [
            'Users.nummer' => 'asc'
        ],
        'conditions' => [ 
            'OR' => [
               'Users.gruppe !=' => 'A',
               'Users.nummer IS null'
            ]
        ]
    ];
    
    
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Captcha', ['field'=>'securitycode']);
        self::$jahr = AppController::getSetting('Jahr');
    }    
    
    
    public function beforeFilter(\Cake\Event\Event $event) {
	parent::beforeFilter($event);
	$this->Auth->allow(['logout', 'addemail', 'register', 'captcha', 'adminlogin', 'vergessen']);
    }

    
    public function captcha()  {
        $this->autoRender = false;
        $this->viewBuilder()->setLayout('ajax');
        $this->Captcha->create(array('type' => 'math'));
    }
    
    
    public function isAuthorized($user)
    {
        if ($user['gruppe'] == "A") return true;

        $action = $this->request->params['action'];

        // The add and index actions are always allowed.
        if (in_array($action, ['edit', 'logout', 'moremax', 'captcha'])) {
            return true;
        }
        
        return parent::isAuthorized($user);
    }


    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->set('users', $this->paginate($this->Users));
        $this->set('_serialize', ['users']);
    }

    
    /**
     * IndexAbrechnung method
     *
     * @return void
     */
    public function indexAbrechnung()
    {
        $this->paginate = [
            'limit' => 20,
            'order' => [
                'Users.name' => 'asc',
                'Users.vorname' => 'asc'
            ],
            'conditions' => [ 
                'Users.nummer !=' => 'admin',
                'Users.nummer >0'
            ]
        ];        
        $this->set('users', $this->paginate($this->Users));
        $this->set('_serialize', ['users']);
    }

    
    /**
     * View method
     *
     * @param string|null $id User id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Items']
            ]);
        
        $this->set('user', $user);
        $this->set('_serialize', ['user']);
    }

    
    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add($anz = null)
    {
        if (is_null($anz)) $anz = 1;
        
        $angelegt = 0;
        $voll = false;
        $fehler = false;
        $nummern = '';
        
        for ($i = 0; $i < $anz; $i++) {
            $user = $this->Users->newEntity();
            $nr = $this->naechsteListe();
            if (!$nr) {
                $voll = true;
                break;        
            } else {
                $user->nummer = $nr;
                $user->code = rand(10000000, 99999999);
                $user->prozentsatz = AppController::getSetting('Prozent');
                // Eine verkaufte Liste braucht NACHTRÄGLICH keine Gebühr mehr zahlen. Zahlt ja bereits beim Kaufen der Liste
                $user->gebuehr = 0; // AppController::getSetting('Listengebühr');
                $user->maxitems = AppController::getSetting('Listenlänge');                

                if ($this->Users->save($user)) {
                    $angelegt++;
                    if ($angelegt > 1) $nummern .= ', ';
                    $nummern .= $user->nummer;
                } else {
                    $fehler = true;
                    break;
                }
            }
        }
        
        if ($angelegt == $anz) {
            if ($angelegt > 1) {
                $this->Flash->success('Es wurden '.$angelegt.' neue Listen mit den Nummern '.$nummern.' angelegt.');           
            } else {
                $this->Flash->success('Ein neuer Benutzer mit der Nummer '.$nr.' wurde angelegt.');                
            }
        }
        if ($fehler || $voll) {
            if ($angelegt > 0) {
                $text = 'Es wurden aber '.$angelegt.' neue Listen mit den Nummern '.$nummern.' angelegt.';
            } else {
                $text = 'Keine neue Listen angelegt.';
            }
            if ($fehler) $this->Flash->error('Registrierung nicht erfolgreich. '.$text); 
            if ($voll) $this->Flash->error('Keine neue Nummer mehr frei. '.$text);
        }
        return $this->redirect(['action' => 'index?sort=nummer&direction=desc']);
    }
    
    
    // E-Mail senden
    private function sendeEmail($empfaenger, $text, $betreff) {
        $email = new Email();
        $email->transport('default');
        $email->template('default', 'default')->emailFormat('html');
        $email->to($empfaenger[0], $empfaenger[1]);         
        $email->subject($betreff);
        $email->viewVars(['content' => $text]);
        $email->from('info@basar-teugn.de', 'Basar Teugn');
        $email->send();        
    }
    
    
    // Parse E-Mail
    private function _parseEMail($input) {
        $regex = '|@@(.+?)@@|';
        //$input = \preg_replace_callback($regex, "UsersController::ersetzungen", $input);
        //$input = preg_replace_callback($regex, "self::ersetzungen", $input);
        return preg_replace_callback($regex, "self::_ersetzungen", $input);
    }    
    
    
    //
    private function _ersetzungen($input) {
        switch ($input[1]) {
            case 'NAME':    
                $input = $this->werte['vorname'].' '.$this->werte['name'];
                break;
            case 'LISTE':
                $input = $this->werte['nummer'];
                break;
            case 'CODE':
                $input = $this->werte['code'];
                break;                    
            case 'LINK':
                $input = 'www.basar-teugn.de/users/register/'.$this->werte['email'].'/'.$this->werte['emailcode'];
                break;
            case 'NEUESEITE':
                $input = '<div style="page-break-after:always">&nbsp;</div>';
                break;            
            default:
                $input = $input[0];
                break;
        }
        return $input;
    }

    
    /**
     * AddEmail method
     * Methode zur Selbstregistrierung eines neuen Benutzer 
     * 
     * @return void 
     */
    public function addemail()
    {
        $this->Users->validator()->add('email', 'required', array('rule' => 'email', 'required' => 'create'));
        $this->Users->validator()->add('name', 'required', array('rule' => 'notBlank', 'required' => 'create'));
        $this->Users->validator()->add('vorname', 'required', array('rule' => 'notBlank', 'required' => 'create'));
        $user = $this->Users->newEntity();
            
        if ($this->request->is('post')) {
            $this->Users->setCaptcha('securitycode', $this->Captcha->getCode('securitycode')); //captcha
            
            // Benutzerparameter setzen: E-Mail-Code 
            $this->request->data['emailcode'] = rand(10000000, 99999999);
            $user = $this->Users->patchEntity($user, $this->request->data);
    
            if ($this->Users->save($user)) {
                $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                                'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email];                
                $nachricht = $this->_parseEMail(AppController::getSetting('EMail-Anmeldung'));
                $this->sendeEmail([$user->email, $user->name.' '.$user->vorname], $nachricht, 'Ihre Anmeldung bei basar-teugn.de');
                $this->Flash->success(__('Registrierung erfolgreich. Bitte prüfen Sie Ihren E-Mail-Eingang zum Abschluss der Registrierung.'));
                return $this->redirect(['action' => 'login']);
            } else {
                $this->Flash->error(__('Ihre Eingaben waren nicht korrekt. Bitte nochmals versuchen.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
        $this->set('datenschutzhinweis', AppController::getSetting('Datenschutzhinweis'));
    } 
    
    
    // Höchste Listennummer ermitteln und die nächste mögliche neue Listennummer zurück geben.
    // Wenn keine Liste mehr übrig ist, dann false zurückgeben
    private function naechsteListe() {
        $maxlist = $this->Users->find('all', array( 
              'fields' => array('nummer'),
              'conditions' => array('nummer >= 100', 'nummer < 1000', "gruppe = 'V'"),
              'order' => array('nummer DESC'),
              'limit' => 1
        ));
        $maxnummer = $maxlist->first();
        if (is_null($maxnummer)) $maxnummer = AppController::getSetting('ListennummerMin');
        else $maxnummer = ++$maxnummer['nummer'];
        if ($maxnummer <= AppController::getSetting('ListennummerMax')) {
            return $maxnummer;
        } else {
            return false;
        }    
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
     * addRegisterUser method
     *
     * @return int ID des neuen Kassenbenutzers.
     */
    public function addRegisterUser()
    {     
        $this->loadModel('Users');
        $user = $this->Users->newEntity();                
        $user->nummer = UsersController::naechsteKasse();
        $user->code = rand(10000000, 99999999);   
        $user->gruppe = 'K';

        if ($this->Users->save($user)) {
            $nummer = $user->id;
        } else {
            $nummer = 0;
        }
        return $nummer;
    }     

    
    /**
     * Register method
     *
     */
    public function register($email = null, $code = null)
    { 
        if (is_null($email) || is_null($code)) {
            $this->Flash->error('E-Mail oder Code nicht angegeben.');              
        }
        
        $query = $this->Users->find('all', [
            'conditions' => ['Users.email' => $email, 'Users.emailcode' => $code]
        ]);
        $user = $query->first();
  
        if (is_null($user)) {
            $this->Flash->error('E-Mail oder Code falsch.');                      
        } else {
            if ($user->emailok == 1) {
                $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                                'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email];                
                $nachricht = $this->_parseEMail(AppController::getSetting('EMail-Zugangsdaten'));                
                $this->sendeEmail([$user->email, $user->name.' '.$user->vorname], $nachricht, 'Ihre Zugangsdaten für basar-teugn.de');
                $this->Flash->success('Registrierung erfolgreich. Ihre Zugangsdaten haben wir Ihnen eben per E-Mail zugeschickt.');                   
            } else {
                $user->nummer = $this->naechsteListe();
                $user->emailok = 1;
                $user->code = rand(10000000, 99999999);
                $user->prozentsatz = AppController::getSetting('Prozent');
                // 
                $user->gebuehr = 0; //AppController::getSetting('Listengebühr');
                $user->maxitems = AppController::getSetting('Listenlänge');

                if ($this->Users->save($user)) {
                    $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                                    'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email];                
                    $nachricht = $this->_parseEMail(AppController::getSetting('EMail-Zugangsdaten'));                
                    $this->sendeEmail([$user->email, $user->name.' '.$user->vorname], $nachricht, 'Ihre Zugangsdaten für basar-teugn.de');
                    $this->Flash->success('Registrierung erfolgreich. Ihre Zugangsdaten haben wir Ihnen eben per E-Mail zugeschickt.');
                } else {
                    $this->Flash->error('Registrierung nicht erfolgreich.');   
                }
            }
        }
        return $this->redirect(['action' => 'login']);
    } 
    

    /**
     * vergessen method
     *
     */
    public function vergessen()
    { 
        $user = $this->Users->newEntity();
        
        if ($this->request->is('post')) {  
            // Benutzerparameter setzen: E-Mail-Code 
            $user = $this->Users->patchEntity($user, $this->request->data);            
            
            //$user = $this->request->data;
            $query = $this->Users->find('all', [
                'conditions' => ['Users.email' => $user->email]
            ]);
            $user = $query->first();
            if (is_null($user)) {
                $this->Flash->error('Die eingegebene E-Mail-Adresse ist nicht bekannt. Vertippt oder noch nicht registriert?');                      
            } else {
                $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                                'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email]; 
                if ($user->emailok == 1) {               
                    $nachricht = $this->_parseEMail(AppController::getSetting('EMail-Zugangsdaten'));
                    $this->sendeEmail([$user->email, $user->name.' '.$user->vorname], $nachricht, 'Ihre Zugangsdaten für basar-teugn.de');
                    $this->Flash->success('Ihre Zugangsdaten wurde Ihnen soeben per E-Mail zugeschickt.');
                } else {           
                    $nachricht = $this->_parseEMail(AppController::getSetting('EMail-Anmeldung'));
                    $this->sendeEmail([$user->email, $user->name.' '.$user->vorname], $nachricht, 'Ihre Anmeldung bei basar-teugn.de');
                    $this->Flash->success('Bitte prüfen Sie Ihren E-Mail-Eingang zum Abschluss der Registrierung.');                
                }          
                return $this->redirect(['action' => 'login']);                
            }
        }   
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    } 
    
    
    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $admin = true;
        
        // Wenn Admin, dann ist jede Benutzernummer möglich
        if (strtoupper($this->Auth->user('gruppe')) != "A") {
            $admin = false;
            // Wenn nicht, dann nur eigene Daten bearbeiten und Name und Vorname sind dann Pflichtfelder
            if ($id != $this->Auth->user('id')) $id = $this->Auth->user('id');
            $this->Users->validator()->add('name', 'required', array('rule' => 'notBlank', 'required' => true));
            $this->Users->validator()->add('vorname', 'required', array('rule' => 'notBlank', 'required' => true));        
        }
        $user = $this->Users->get($id, [
            'contain' => []
            ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Angaben gespeichert'));
                if ($admin) return $this->redirect(['action' => 'index']);
                else return $this->redirect(['controller' => 'Items', 'action' => 'index']);
            } else {
                $this->Flash->error(__('Ihre Angaben konnten nicht gespeichert werden. Bitte nochmals versuchen.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
        $this->set('admin', $admin);
    }

    
    public function moremax()
    {
        $id = $this->Auth->user('id');
        $this->Users->recursive = 2;
        $user = $this->Users->get($id, [
            'contain' => []
            ]);
         
        
        $this->loadModel('Items');
        $items = $this->Items->find('all', [
            'conditions' => [ 'Items.user_id' => $this->Auth->user('id') ]
        ]);
        
        $erweiterbar = false;
        
        // Was ist es für ein Benutzer. Ein E-Mail-Benutzer kann Positionen nachkaufen. Ein Kauflistenbenutzer nur "Listenlänge"    
        if ($user->emailcode > 1) {
            // Erweitern ist nur möglich, wenn mehr oder gleich viele Artikel angelegt sind, wie der Benutzer maximal haben darf.
            if ($items->count() >= $user->maxitems) {
                // Erweitern ist nur möglich, wenn der Benutzer weniger Artikel hat, wie maximal erlaubt. 
                if ($user->maxitems < AppController::getSetting('ListenlängeEMailMax')) {
                    $erweiterbar = true;
                    
                    $user->maxitems = min($user->maxitems + AppController::getSetting('Listenlänge'), 
                                           AppController::getSetting('ListenlängeEMailMax'));
                    
                    if ($this->Users->save($user)) {
                        $this->Flash->success(__('Liste kostenpflichtig erweitert.'));
                    } else {
                        $this->Flash->error(__('Beim Erweitern der Liste ist ein Fehler aufgetreten. Bitte nochmals versuchen.'));
                    }                    

                    
                }
            }           
        }
        $this->redirect(['controller' => 'Items', 'action' => 'index']);        
    }

    
    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('Der Benutzer wurde gelöscht.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }
    
    
    public function delall()
    {
        $this->request->allowMethod(['get', 'delete']);
        $query = $this->Users->find('all', [
            'conditions' => ['Users.gruppe !=' => 'A', 'Users.gruppe !=' => 'K']
        ]);
        
        $i = 0;
        $ok = true;
        foreach ($query->toArray() as $benutzer) {
            $entity = $this->Users->get($benutzer->id);
            if ($this->Users->delete($entity)) {
                $i++;
            } else {
                $ok = false;
                break;
            }
        }
        if ($ok) {
            $this->Flash->success('Es wurden alle '.$i.' Benutzer gelöscht');
        } else {
            $this->Flash->error('Es ist ein Fehler aufgetreten. Es konnten nur '.$i.' Benutzer gelöscht werden.');
        }            
        return $this->redirect(['action' => 'index']);
    }

    
    public function login()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
                
                // Letzten Login merken
                $benutzer = $this->Users->get($user['id']);
                $benutzer->letzterlogin = Time::now();
                $this->Users->save($benutzer);
                
                if ($user['nummer'] == "admin") {
                    return $this->redirect(['controller' => 'Settings', 'action' => 'admin']);
                } else {
                    return $this->redirect(['controller' => 'Items', 'action' => 'index']);
                }
            }
            $this->Flash->error('Listennummer oder Zugangsnummer ist nicht richtig. Bitte nochmals versuchen.');
        } 
        $logingeht = AppController::getSetting('Login möglich');
        $this->set('loginmoeglich', $logingeht);
        if ($logingeht) {
            $this->set('hint', AppController::getSetting('Login-Hinweis'));     
            $this->set('emailanmeldung', AppController::getSetting('E-Mail Registrierung möglich'));             
        } else {
            $this->set('danketext', AppController::getSetting('Danke-Text'));          
        }
    }


    public function adminlogin()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
                if ($user['nummer'] == "admin") {
                    return $this->redirect(['controller' => 'Settings', 'action' => 'admin']);
                } else {
                    return $this->redirect(['controller' => 'Items', 'action' => 'index']);
                }
            }
            $this->Flash->error('Listennummer oder Zugangsnummer ist nicht richtig. Bitte nochmals versuchen.');
        }
        
        // TEST: Login immer möglich
        // $logingeht = AppController::getSetting('Login möglich');
        $logingeht = true;
        
        $this->set('loginmoeglich', $logingeht);
        if ($logingeht) {
            $this->set('hint', AppController::getSetting('Login-Hinweis'));
            // TEST: Anmeldung immer möglich
            // $this->set('emailanmeldung', AppController::getSetting('E-Mail Registrierung möglich'));             
            $this->set('emailanmeldung', true);
        } else {
            $this->set('danketext', AppController::getSetting('Danke-Text'));          
        }
    }    
    
    
    public function logout()
    {
        $this->Flash->success('Sie haben sich abgemeldet.');
        return $this->redirect($this->Auth->logout());
    }
    
    // Zettel zum Auslegen drucken
    public function drucken($id = null) {

        //Configure::write('debug', 2);
        error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

        $old_limit = ini_set('memory_limit', '128M');
        require_once(ROOT . DS . 'vendor/dompdf/dompdf_config.inc.php');

        if ($this->request->is('post')) {
            $text = AppController::getSetting('Listenformatierung');            
            foreach ($this->request->data['user_id'] as $uid => $wert) {
                // Benutzer holen für Listennummer
                if ($wert == 1) {
                    $user = $this->Users->get($uid);
                    $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                            'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email];  

                    $text .= $this->_parseEMail(AppController::getSetting('Listentext'));
                    $text .= $this->_parseEMail('@@NEUESEITE@@');
                }
            }       
        } else {
            // Benutzer holen für Listennummer
            $user = $this->Users->get($id);
            $this->werte = ['name' => $user->name, 'vorname' => $user->vorname, 'nummer' => $user->nummer, 
                    'code' => $user->code, 'emailcode' => $user->emailcode, 'email' => $user->email];  

            $text = AppController::getSetting('Listenformatierung'); 
            $text .= $this->_parseEMail(AppController::getSetting('Listentext'));    
        }
        
        $dompdf = new \DOMPDF();
        $dompdf->load_html($text);
        $dompdf->render();
        $dompdf->stream("Druckliste.pdf", array("Attachment" => false));
    }    
}
