<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Itemsale Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $nummer
 * @property string $barcode
 * @property string $bezeichnung
 * @property string $groesse
 * @property float $preis
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 * @property bool $verkauft
 *
 * @property \App\Model\Entity\User $user
 */
class Itemsale extends Entity
{

}
