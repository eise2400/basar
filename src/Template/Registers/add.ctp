<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('zurück', ['action' => 'index'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="registers form large-10 medium-9 columns">
    <?= $this->Form->create($register) ?>
    <fieldset>
        <legend><?= __('Neue Kasse') ?></legend>
        <?php
            echo $this->Form->input('comment', ['label' => 'Kommentar']);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
