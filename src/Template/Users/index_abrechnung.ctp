<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('zurück', ['controller' => 'Settings', 'action' => 'admin'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users index large-10 medium-9 columns">
    <?= $this->Form->create(null, ['id' => 'UsersForm', 'url' => ['controller' => 'Items', 'action' => 'abrechnung_drucken']]) ?>    
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th><?= $this->Paginator->sort('nummer') ?></th>
            <th><?= $this->Paginator->sort('name') ?></th>
            <th class="actions" ><?= __('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo $this->Form->checkbox('user_id.'.$user->id); echo(($user->nummer)? ($user->nummer) : '<i>kein</i>'); ?></td>
            <td><?= ($user->name)? ($user->name.' '.$user->vorname) : '<i>kein</i>' ?></td>
            <td class="actions">
                <?= $this->Html->link(__('Anzeigen'), ['action' => 'view', $user->id], ['class' => 'button micro radius']) ?>
                <?= $this->Html->link(__('Abrechnung'), ['controller' => 'Items', 'action' => 'abrechnung_drucken', $user->id], ['class' => 'button micro radius']) ?>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
    <?php
    echo $this->Form->button('alle markieren', ['type' => 'button', 'onclick' => "$(':checkbox').each(function() { this.checked = true; });"]);
    echo '&nbsp;';
    echo $this->Form->button('keinen markieren', ['type' => 'button', 'onclick' => "$(':checkbox').each(function() { this.checked = false; });"]);
    echo $this->Form->submit('Abrechnung drucken', ['class' => 'button success radius']);	
    echo $this->Form->end(); ?>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->prev('< ' . __('zurück')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('vor') . ' >') ?>
        </ul>
    </div>
</div>
<script type="text/javascript" >
function toggle(source) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i] != source)
            checkboxes[i].checked = source.checked;
    }
}
</script;