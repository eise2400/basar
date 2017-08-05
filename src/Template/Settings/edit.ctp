<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link(__('Zurück'), ['action' => 'index'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="settings form large-10 medium-9 columns">
    <?= $this->Form->create($setting) ?>
    <fieldset>
        <legend><?= __('Einstellung bearbeiten') ?></legend>
        <?php
            //echo $this->Form->input('name');
            echo "<h3>$setting->name</h3>";
            echo "<p>$setting->hinweis</p>";
            //echo $this->Form->input('art');
            if ($setting->art == 'Wert') {
                echo $this->Form->input('wert');
            } elseif ($setting->art == 'Checkbox') {
                echo $this->Form->checkbox('wert');
            } else {
                echo $this->Form->input('text');
                //echo $this->Form->input('text', array('class'=>'ckeditor'));
            }
            //echo $this->Form->input('hinweis');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Speichern')) ?>
    <?= $this->Form->end() ?>
</div>
