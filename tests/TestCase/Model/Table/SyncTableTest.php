<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SyncTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SyncTable Test Case
 */
class SyncTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\SyncTable
     */
    public $Sync;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.sync',
        'app.registers',
        'app.users',
        'app.items',
        'app.reg_items'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Sync') ? [] : ['className' => SyncTable::class];
        $this->Sync = TableRegistry::get('Sync', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Sync);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
