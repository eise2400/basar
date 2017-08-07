<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sync Model
 *
 * @property \App\Model\Table\RegistersTable|\Cake\ORM\Association\BelongsTo $Registers
 * @property \App\Model\Table\RegItemsTable|\Cake\ORM\Association\BelongsTo $RegItems
 * @property \App\Model\Table\ItemsTable|\Cake\ORM\Association\BelongsTo $Items
 *
 * @method \App\Model\Entity\Sync get($primaryKey, $options = [])
 * @method \App\Model\Entity\Sync newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Sync[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Sync|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Sync patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Sync[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Sync findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SyncTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('sync');
        $this->setDisplayField('register_id');
        $this->setPrimaryKey(['register_id', 'reg_item_id']);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Registers', [
            'foreignKey' => 'register_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('RegItems', [
            'foreignKey' => 'reg_item_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Items', [
            'foreignKey' => 'item_id',
            'joinType' => 'INNER'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('barcode')
            ->requirePresence('barcode', 'create')
            ->notEmpty('barcode');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['register_id'], 'Registers'));
        $rules->add($rules->existsIn(['reg_item_id'], 'RegItems'));
        $rules->add($rules->existsIn(['item_id'], 'Items'));

        return $rules;
    }
}
