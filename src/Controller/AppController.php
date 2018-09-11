<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Flash');        
        $this->set('title', AppController::getSetting('Titel'));
        $this->set('head', AppController::getSetting('Kopf'));  

        // Für die Kasseninitialisierung ist eine Direktanmeldung über Basic-Authentifizierung möglich
        if ($_SERVER['REQUEST_URI'] == '/kasse') {
            $this->loadComponent('Auth', [
                'authenticate' => [
                    'Basic' => [
                        'fields' => ['username' => 'nummer', 'password' => 'code'],
                        'userModel' => 'Users',
                        'passwordHasher' => [
                            'className' => 'No',
                        ]
                    ],
                ],
                'storage' => 'Memory',
                'unauthorizedRedirect' => false
            ]);  
        }
        
        // Alles andere erfordert eine Anmeldung über das Formular
        else {
            $this->loadComponent('Auth', [
                'authorize' => 'Controller',
                'authenticate' => [
                    'Form' => [
                        'fields' => [
                            'username' => 'nummer',
                            'password' => 'code'
                        ],
                        'passwordHasher' => [
                            'className' => 'No',
                        ]
                    ]
                ],
                'loginAction' => [
                        'controller' => 'Users',
                        'action' => 'login'
                ],
                'storage' => 'Session',
                'unauthorizedRedirect' => false //$this->referer()
            ]);
        }            
    }
    
    public function beforeFilter(\Cake\Event\Event $event) {
	parent::beforeFilter($event);
    }    

    public function isAuthorized($user)
    {
        // Den Admins ist alles erlaubt
        if ($user['gruppe'] === 'A') return true;
        
        // Alles was nicht erlaubt ist, ist anderen verboten
        else return false;
    }
        
    public function getSetting($name) {
        $this->loadModel('Settings');
        $wert = $this->Settings->find('all')
                ->where(['Settings.name' => $name])->first();
        if ($wert['art'] == 'Wert' || $wert['art'] == 'Checkbox') return $wert['wert'];
        else return $wert['text'];
    }

}
