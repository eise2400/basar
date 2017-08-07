<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Sync Entity
 *
 * @property int $register_id
 * @property int $reg_item_id
 * @property int $item_id
 * @property int $barcode
 * @property \Cake\I18n\Time $created
 *
 * @property \App\Model\Entity\Register $register
 * @property \App\Model\Entity\RegItem $reg_item
 * @property \App\Model\Entity\Item $item
 */
class Sync extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'register_id' => false,
        'reg_item_id' => false
    ];
}
