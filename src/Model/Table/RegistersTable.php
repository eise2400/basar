<?php
namespace App\Model\Table;

use App\Model\Entity\Register;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Registers Model
 *
 * @property \Cake\ORM\Association\HasMany $Sync
 */
class RegistersTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('registers');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasOne('User', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('Sync', [
            'foreignKey' => 'register_id'
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
            
//        $validator
//            ->requirePresence('ip', 'create')
//            ->notEmpty('ip');
            
        $validator
            ->requirePresence('syncaddr', 'create')
            ->notEmpty('syncaddr');
            
//        $validator
//            ->add('local', 'valid', ['rule' => 'boolean'])
//            ->requirePresence('local', 'create')
//            ->notEmpty('local');
//            
//        $validator
//            ->add('active', 'valid', ['rule' => 'boolean'])
//            ->requirePresence('active', 'create')
//            ->notEmpty('active');
//            
//        $validator
//            ->requirePresence('lastSync', 'create')
//            ->notEmpty('lastSync');
//            
//        $validator
//            ->add('syncEn', 'valid', ['rule' => 'boolean'])
//            ->requirePresence('syncEn', 'create')
//            ->notEmpty('syncEn');
//            
//        $validator
//            ->requirePresence('comment', 'create')
//            ->notEmpty('comment');
        
        $validator
            ->add('user_id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('user_id', 'create');        

        return $validator;
    }
}
