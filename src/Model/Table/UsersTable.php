<?php
namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \Cake\ORM\Association\HasMany $Items
 */
class UsersTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('users');
        $this->displayField('name');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('Items', [
            'foreignKey' => 'user_id',
            'dependent' => true
        ]);
        $this->addBehavior('Captcha', [
            'field' => 'securitycode',
            'message' => 'Sicherheitscode falsch'
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
            ->allowEmpty('nummer');
            
        $validator
            ->allowEmpty('code');
         /*   
        $validator
            ->allowEmpty('name');
            
        $validator
            ->allowEmpty('vorname');
           */ 
        $validator
            ->allowEmpty('telefon');
            
        /*$validator
            ->add('email', 'valid', ['rule' => 'email'])
            ->allowEmpty('email');
          */
        
        $validator
            ->allowEmpty('emailcode');
            
        $validator
            ->add('emailok', 'valid', ['rule' => 'boolean'])
            ->allowEmpty('emailok');
            
        $validator
            ->add('prozentsatz', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('prozentsatz');
            
        $validator
            ->add('gebuehr', 'valid', ['rule' => 'decimal'])
            ->allowEmpty('gebuehr');
            
        $validator
            ->add('maxitems', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('maxitems');
            
        $validator
            ->allowEmpty('ist_da');
        
        $validator
            ->allowEmpty('letzterlogin');

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
        $rules->add($rules->isUnique(['email'], 'Diese E-Mail-Adresse wird bereits verwendet. '.
                              'Wenn Sie Ihre Zugangsdaten vergessen haben, können Sie sich diese auf der Startseite wieder zusenden lassen.'));
        return $rules;
    }
      
}
