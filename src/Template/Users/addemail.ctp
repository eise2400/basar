<?php ?>
<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('zurück', ['controller' => 'Users', 'action' => 'login'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users form large-10 medium-9 columns">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend>Als neuer Benutzer registrieren</legend>
        <?php
            echo $this->Form->input('name');
            echo $this->Form->input('vorname');
            echo $this->Form->input('telefon');
            echo $this->Form->input('email', array('type' => 'email', 'allowEmpty' => 'false'));
            echo $this->Captcha->create('securitycode', array('type' => 'image', 'fontAdjustment' => 1)); 

        ?>
    </fieldset>
    <?= $datenschutzhinweis ?>
    <?= $this->Form->button(__('Registrieren')) ?>
    <?= $this->Form->end() ?>
</div>
<script>
jQuery('.creload').on('click', function() {
    var mySrc = $(this).prev().attr('src');
    var glue = '?';
    if(mySrc.indexOf('?')!=-1)  {
        glue = '&';
    }
    $(this).prev().attr('src', mySrc + glue + new Date().getTime());
    return false;
});
</script>
