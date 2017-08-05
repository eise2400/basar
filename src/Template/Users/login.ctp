<?php if ($loginmoeglich) { ?>
    <div class="actions columns large-8 medium-8">
        <?= $hint ?>
    </div>
    <div class="items index large-4 medium-4 columns">
        <?= $this->Form->create() ?>
        <fieldset>
            <legend><?= __('Anmeldung für Verkäufer') ?></legend>
            <?php
                echo '<b>Achtung! Artikel eintragen und ändern ist nur noch bis Fr 16.9. um 16 Uhr möglich!</b>';
                echo $this->Form->input('nummer', ['label' => 'Listennummer']);
                echo $this->Form->input('code', ['label' => 'Zugangsnummer', 'type' => 'password']);
                echo $this->Form->button('anmelden');        
            ?>
        </fieldset>  
        <?= $this->Form->end(); ?>
        <?php
        if ($emailanmeldung) { ?>
            <fieldset>
                <legend><?= __('Als neuer Verkäufer registrieren') ?></legend>
                <b>Achtung! Neuregistrierung ist nur noch bis Fr 16.9. um 16 Uhr möglich!</b><br />               
                <?= $this->Html->link(__('registrieren'), ['action' => 'addemail'], ['class' => 'button radius']) ?>
                <br />
                <?= $this->Html->link(__('Zugangsdaten vergessen'), ['action' => 'vergessen'], ['class' => 'button micro radius']) ?>    

            </fieldset>   
        <?php } ?>
    </div>
<?php } else { ?>
<div class="actions columns large-12 medium-12">
    <?= $danketext ?>
</div>
<?php } ?>
