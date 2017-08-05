<div class="actions columns large-2 medium-3">
    <h3><?= __('Actions') ?></h3>
    <ul class="side-nav">
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $register->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $register->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Registers'), ['action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('List Sync'), ['controller' => 'Sync', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Sync'), ['controller' => 'Sync', 'action' => 'add']) ?></li>
    </ul>
</div>
<div class="registers form large-10 medium-9 columns">
    <?= $this->Form->create($register) ?>
    <fieldset>
        <legend><?= __('Edit Register') ?></legend>
        <?php
            echo $this->Form->input('ip');
            echo $this->Form->input('syncaddr');
            echo $this->Form->input('local');
            echo $this->Form->input('active');
            echo $this->Form->input('lastSync');
            echo $this->Form->input('syncEn');
            echo $this->Form->input('comment');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
