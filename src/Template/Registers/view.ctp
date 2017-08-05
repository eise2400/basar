<div class="actions columns large-2 medium-3">
    <h3><?= __('Actions') ?></h3>
    <ul class="side-nav">
        <li><?= $this->Html->link(__('Edit Register'), ['action' => 'edit', $register->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Register'), ['action' => 'delete', $register->id], ['confirm' => __('Are you sure you want to delete # {0}?', $register->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Registers'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Register'), ['action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Sync'), ['controller' => 'Sync', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Sync'), ['controller' => 'Sync', 'action' => 'add']) ?> </li>
    </ul>
</div>
<div class="registers view large-10 medium-9 columns">
    <h2><?= h($register->id) ?></h2>
    <div class="row">
        <div class="large-5 columns strings">
            <h6 class="subheader"><?= __('Ip') ?></h6>
            <p><?= h($register->ip) ?></p>
            <h6 class="subheader"><?= __('Syncaddr') ?></h6>
            <p><?= h($register->syncaddr) ?></p>
            <h6 class="subheader"><?= __('Comment') ?></h6>
            <p><?= h($register->comment) ?></p>
        </div>
        <div class="large-2 columns numbers end">
            <h6 class="subheader"><?= __('Id') ?></h6>
            <p><?= $this->Number->format($register->id) ?></p>
        </div>
        <div class="large-2 columns dates end">
            <h6 class="subheader"><?= __('Created') ?></h6>
            <p><?= h($register->created) ?></p>
            <h6 class="subheader"><?= __('Modified') ?></h6>
            <p><?= h($register->modified) ?></p>
            <h6 class="subheader"><?= __('LastSync') ?></h6>
            <p><?= h($register->lastSync) ?></p>
        </div>
        <div class="large-2 columns booleans end">
            <h6 class="subheader"><?= __('Local') ?></h6>
            <p><?= $register->local ? __('Yes') : __('No'); ?></p>
            <h6 class="subheader"><?= __('Active') ?></h6>
            <p><?= $register->active ? __('Yes') : __('No'); ?></p>
            <h6 class="subheader"><?= __('SyncEn') ?></h6>
            <p><?= $register->syncEn ? __('Yes') : __('No'); ?></p>
        </div>
    </div>
</div>
<div class="related row">
    <div class="column large-12">
    <h4 class="subheader"><?= __('Related Sync') ?></h4>
    <?php if (!empty($register->sync)): ?>
    <table cellpadding="0" cellspacing="0">
        <tr>
            <th><?= __('Register Id') ?></th>
            <th><?= __('Reg Item Id') ?></th>
            <th><?= __('Item Id') ?></th>
            <th><?= __('Barcode') ?></th>
            <th><?= __('Created') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($register->sync as $sync): ?>
        <tr>
            <td><?= h($sync->register_id) ?></td>
            <td><?= h($sync->reg_item_id) ?></td>
            <td><?= h($sync->item_id) ?></td>
            <td><?= h($sync->barcode) ?></td>
            <td><?= h($sync->created) ?></td>

            <td class="actions">
                <?= $this->Html->link(__('View'), ['controller' => 'Sync', 'action' => 'view', $sync->register_id]) ?>

                <?= $this->Html->link(__('Edit'), ['controller' => 'Sync', 'action' => 'edit', $sync->register_id]) ?>

                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Sync', 'action' => 'delete', $sync->register_id], ['confirm' => __('Are you sure you want to delete # {0}?', $sync->register_id)]) ?>

            </td>
        </tr>

        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    </div>
</div>
