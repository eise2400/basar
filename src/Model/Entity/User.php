<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity.
 */
class User extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'nummer' => true,
        'code' => true,
        'name' => true,
        'vorname' => true,
        'telefon' => true,
        'email' => true,
        'emailcode' => true,
        'emailok' => true,
        'prozentsatz' => true,
        'gebuehr' => true,
        'maxitems' => true,
        'ist_da' => true,
        'letzterlogin' => true,
        'gruppe' => true,
        'items' => true,
    ];
}
