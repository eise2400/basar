<?php
/**
 * Helper for Showing the use of Captcha*
 * @author     Arvind Kumar
 * @link       http://www.devarticles.in/
 * @copyright  Copyright © 2014 http://www.devarticles.in/
 * @version 3.0 - Tested OK in Cakephp 3.1.1
 */

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;

class CaptchaHelper extends Helper {

/**
 * helpers
 *
 * @var array
 */

  public $helpers = ['Html', 'Form'];

	protected $_defaultConfig = [];

/**
 * Constructor
 *
 * ### Settings:
 *
 * - Get settings set from Component.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $config Settings array Settings array
 */
    public function __construct(View $View, $config = []) {
				$this->View = $View;
        parent::__construct($View, $config);
    }

    function create($field='captcha', $config=array()) {

				$html = '';

        $this->_config = array_merge($this->_config, (array)$config);

        $this->_config['reload_txt'] = isset( $this->_config['reload_txt']) ? __($this->_config['reload_txt']) : __('Can\'t read? Reload');

        $this->_config['clabel'] = isset( $this->_config['clabel']) ? __($this->_config['clabel']) : __('<p>Enter security code shown above:</p>');

        $this->_config['mlabel'] = isset( $this->_config['mlabel']) ? __($this->_config['mlabel']) : __('<p>Answer Simple Math</p>');

        $controller = strtolower( $this->_config['controller']);
        $action =  $this->_config['action'];
        $qstring = array(
            'type' =>   $this->_config['type'],
            'field' =>  $field
        );

        switch( $this->_config['type']):
            case 'image':

                $qstring = array_merge($qstring, array(
                    'width' =>  $this->_config['width'],
                    'height'=>  $this->_config['height'],
                    'theme' =>  $this->_config['theme'],
                    'length' => $this->_config['length'],
                ));

                $html .= $this->Html->image(array('controller'=>$controller, 'action'=>$action, '?'=> $qstring), array('hspace'=>2));
                $html .= $this->Html->link( $this->_config['reload_txt'], '#', array('class' => 'creload', 'escape' => false));
                $html .= $this->Form->input($field, array('autocomplete'=>'off','label'=> $this->_config['clabel'],'class'=>'clabel'));
            break;
            case 'math':
                $qstring = array_merge($qstring, array('type'=>'math'));
                if(isset($this->_config['stringOperation']))   {
                    $html .= $this->_config['mlabel'] .  $this->_config['stringOperation'].' = ?';
                }   else    {
										ob_start();
                    $this->View->requestAction(array('controller'=>$controller, 'action'=>$action, '?'=> $qstring));
										$mathstring = ob_get_contents();
										ob_end_clean();
                }
								$errorclass='';
								if($this->Form->isFieldError($field)) $errorclass = 'error';
								$html .= '<div class="input text required '.$errorclass.'">' . $this->Form->label($field, $this->_config['mlabel']) . '</div>';
								$html .= '<div><strong>' . $mathstring . '</strong>' . ' = ?</div>';
                $html .= $this->Form->input($field, array('autocomplete'=>'off','label'=>false,'class'=>''));
            break;
        endswitch;

				return $html;
    }
}