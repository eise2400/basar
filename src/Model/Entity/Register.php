<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Register Entity.
 */
class Register extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'ip' => true,
        'syncaddr' => true,
        'local' => true,
        'active' => true,
        'lastSync' => true,
        'syncEn' => true,
        'comment' => true,
        'sync' => true,
    ];
}
