<?php
namespace App\Model\Table;

use App\Model\Entity\Item;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
//use App\Model\Table;

/**
 * Items Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 */
class ItemsTable extends Table
{
	
	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config)
	{
		$this->table('items');
		$this->displayField('id');
		$this->primaryKey('id');
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
		->add('id', 'valid', ['rule' => 'numeric'])
		->allowEmpty('id', 'create');

		$validator
		->add('nummer', 'valid', ['rule' => 'numeric'])
		->requirePresence('nummer', 'create')
		->notEmpty('nummer');
 
		$validator
		->requirePresence('bezeichnung', 'create')
		->notEmpty('bezeichnung');

		$validator
		->allowEmpty('groesse');

		$validator
		->add('preis', 'valid', ['rule' => 'decimal'])
		->add('preis', 'custom', ['rule' => function ($value, $context) {
					if ($value >= 1000) return false;
					if ($value < 0) return false;
					if ($value * 2 == round($value * 2, 0)) return true;
					else return false;
				},
				'message' => 'Es sind nur halbe oder ganze Eurobeträge bis max. 1000€ erlaubt. Der Mindestbetrag beträgt 0,5€' ])
		->requirePresence('preis', 'create')
		->notEmpty('preis');

		$validator
		->add('preisdt', 'valid', ['rule' => 'decimal'])
		->add('preisdt', 'custom', ['rule' => function ($value, $context) {
					if ($value >= 1000) return false;
					if ($value <= 0) return false;
					if ($value * 2 == round($value * 2, 0)) return true;
					else return false;
			},
			'message' => 'Es sind nur halbe oder ganze Eurobeträge bis max. 1000€ erlaubt. Der Mindestbetrag beträgt 0,5€.' ])
		->requirePresence('preisdt', 'create')
		->notEmpty('preisdt');		
		
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
