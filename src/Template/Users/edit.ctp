<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <?php if ($admin) { 
            echo '<li>'.$this->Html->link('zurück', ['controller' => 'Users', 'action' => 'index'], ['class' => 'button radius']).'</li>';
        } else {
            echo '<li>'.$this->Html->link(__('abmelden'), ['action' => 'logout'], ['class' => 'button radius']).'</li>';
        } ?>
    </ul>
</div>
<div class="users form large-10 medium-9 columns">
    <?php if ($admin) echo '<h2>Liste '.$user->nummer.'</h2>'; ?>
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('eigene Daten') ?></legend>
        <?php
            echo $this->Form->input('name', ['label' => 'Ihr Name']);
            echo $this->Form->input('vorname', ['label' => 'Ihr Vorname']);
            echo $this->Form->input('telefon', ['label' => 'Telefonnummer (Angabe freiwillig)']);
        ?>
    </fieldset>
    <?= $this->Form->button(__('weiter')) ?>
    <?= $this->Form->end() ?>
</div>
