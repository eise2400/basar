<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('zurück', ['action' => 'index'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="registers form large-10 medium-9 columns">
    <?= $this->Form->create($register) ?>
    <fieldset>
        <legend>Kasse bearbeiten</legend>
        <?php
            echo $this->Form->input('comment', ['label' => 'Kommentar']);
        ?>
    </fieldset>
    <div class="row">
        <div class="large-5 columns strings">
            <h6 class="subheader">Syncadresse</h6>
            <p><?= h($register->syncaddr) ?></p>
            <h6 class="subheader">IP im LAN</h6>
            <p><?= h($register->ip) ?></p>
        </div>
        <div class="large-2 columns numbers end">
            <h6 class="subheader">ID</h6>
            <p><?= $this->Number->format($register->id) ?></p>
            <h6 class="subheader">User-ID</h6>
            <p><?= $this->Number->format($register->user_id) ?></p>
        </div>
        <div class="large-2 columns dates end">
            <h6 class="subheader">erzeugt</h6>
            <p><?= h($register->created) ?></p>
            <h6 class="subheader">geändert</h6>
            <p><?= h($register->modified) ?></p>
            <h6 class="subheader">Letzter Scan</h6>
            <p><?= h($register->lastSync) ?></p>
        </div>
        <div class="large-2 columns booleans end">
            <h6 class="subheader"><?= __('Local') ?></h6>
            <p><?= $register->local ? __('Yes') : __('No'); ?></p>
            <h6 class="subheader"><?= __('Active') ?></h6>
            <p><?= $register->active ? __('Yes') : __('No'); ?></p>
            <h6 class="subheader"><?= __('SyncEn') ?></h6>
            <p><?= $register->syncEn ? __('Yes') : __('No'); ?></p>
        </div>
    </div>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
