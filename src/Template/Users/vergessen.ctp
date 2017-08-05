<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('zurück', ['controller' => 'Users', 'action' => 'login'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users form large-10 medium-9 columns">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend>Vergessene Zugangsdaten anfordern</legend>
        <?php
            echo $this->Form->input('email', array('type' => 'email', 'allowEmpty' => 'false'));
        ?>
    </fieldset>
    <?= $this->Form->button(__('Absenden')) ?>
    <?= $this->Form->end() ?>
</div>