<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('Benutzer anzeigen', ['action' => 'index'], ['class' => 'button radius']) ?></li>
        <li><?= $this->Html->link('zurück', ['controller' => 'Settings', 'action' => 'admin'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users form large-10 medium-9 columns">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Add User') ?></legend>
        <?php
            echo $this->Form->input('nummer');
            echo $this->Form->input('code');
            echo $this->Form->input('name');
            echo $this->Form->input('vorname');
            echo $this->Form->input('telefon');
            echo $this->Form->input('email');
            echo $this->Form->input('prozentsatz');
            echo $this->Form->input('gebuehr');
            echo $this->Form->inpur('maxitems');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
