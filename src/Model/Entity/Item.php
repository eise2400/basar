<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;


/**
 * Item Entity.
 */
class Item extends Entity
{

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * @var array
	 */
	protected $_accessible = [
	'user_id' => true,
	'nummer' => true,
        'barcode' => true,
	'bezeichnung' => true,
	'groesse' => true,
	'preis' => true,
	'user' => true,
	];
	

	protected function _getPreisdt()
	{
		return str_replace(".", ",", sprintf("%01.2f", $this->preis));
	}
}
