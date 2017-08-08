<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Itemsales Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Itemsale get($primaryKey, $options = [])
 * @method \App\Model\Entity\Itemsale newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Itemsale[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Itemsale|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Itemsale patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Itemsale[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Itemsale findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ItemsalesTable extends Table
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

        $this->setTable('itemsales');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->integer('id')
            ->requirePresence('id', 'create')
            ->notEmpty('id');

        $validator
            ->integer('nummer')
            ->requirePresence('nummer', 'create')
            ->notEmpty('nummer');

        $validator
            ->requirePresence('barcode', 'create')
            ->notEmpty('barcode');

        $validator
            ->requirePresence('bezeichnung', 'create')
            ->notEmpty('bezeichnung');

        $validator
            ->allowEmpty('groesse');

        $validator
            ->decimal('preis')
            ->requirePresence('preis', 'create')
            ->notEmpty('preis');

        $validator
            ->boolean('verkauft')
            ->allowEmpty('verkauft');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }
}
