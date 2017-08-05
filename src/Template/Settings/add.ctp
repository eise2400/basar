<div class="actions columns large-2 medium-3">
    <h3><?= __('Actions') ?></h3>
    <ul class="side-nav">
        <li><?= $this->Html->link(__('List Settings'), ['action' => 'index']) ?></li>
    </ul>
</div>
<div class="settings form large-10 medium-9 columns">
    <?= $this->Form->create($setting) ?>
    <fieldset>
        <legend><?= __('Add Setting') ?></legend>
        <?php
            echo $this->Form->input('name');
            echo $this->Form->input('art', array(
                'options' => array('HTML' => 'HTML', 'Wert' => 'Wert', 'Checkbox' => 'Checkbox'),
                'empty' => '(auswählen)'
            ));
            echo $this->Form->input('wert');
            echo $this->Form->input('text');
            echo $this->Form->input('hinweis');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
