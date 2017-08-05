<?php /*<div class="actions columns large-12 medium-12">
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Administrator Login') ?></legend>
        <?php
            echo $this->Form->input('nummer', ['label' => 'Benutzername']);
            echo $this->Form->input('code', ['label' => 'Passwort']);
            echo $this->Form->button('anmelden');        
        ?>
    </fieldset>  
    <?= $this->Form->end(); ?>
</div> */ ?>

<?php if ($loginmoeglich) { ?>
    <div class="actions columns large-8 medium-8">
        <?= $hint ?>
    </div>
    <div class="items index large-4 medium-4 columns">
        <?= $this->Form->create() ?>
        <fieldset>
            <legend><?= __('Anmeldung für Verkäufer') ?></legend>
            <?php
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

