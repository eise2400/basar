<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('einen Benutzer anlegen', ['action' => 'add'], ['class' => 'button success radius']) ?></li>
        <li><?= $this->Html->link('zehn Benutzer anlegen', ['action' => 'add/10'], ['class' => 'button success radius']) ?></li>
        <li><?= $this->Html->link('zurück', ['controller' => 'Settings', 'action' => 'admin'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users index large-10 medium-9 columns">
    <?= $this->Form->create(null, ['id' => 'UsersForm', 'url' => ['action' => 'drucken']]) ?>    
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th><?= $this->Paginator->sort('nummer') ?></th>
            <th><?= $this->Paginator->sort('name') ?></th>
            <th><?= $this->Paginator->sort('email', 'E-Mail') ?></th>
            <th>Teile</th>
            <th class="actions" ><?= __('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo $this->Form->checkbox('user_id.'.$user->id); echo(($user->nummer)? ($user->nummer) : '<i>kein</i>'); ?></td>
            <td><?= ($user->name)? ($user->vorname.' '.$user->name) : '<i>kein</i>' ?></td>
            <td><?= h($user->email) ?></td>
            <td><?= count($user->items) ?></td>
            <td class="actions">
                <?= $this->Html->link(__('Anzeigen'), ['action' => 'view', $user->id], ['class' => 'button micro radius']) ?>
                <?= $this->Html->link(__('Ändern'), ['action' => 'edit', $user->id], ['class' => 'button micro radius']) ?>
                <?= $this->Html->link(__('Zettel'), ['action' => 'drucken', $user->id], ['class' => 'button micro radius']) ?>
                <?php if (strtoupper($user->nummer) != 'ADMIN') echo $this->Form->postLink(__('Löschen'), 
                        ['action' => 'delete', $user->id],  
                        ['confirm' => __('Wollen Sie die Liste {0} bzw. ID {1} wirklich löschen?', $user->nummer, $user->id), 'class' => 'button micro alert radius']) ?>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
    <?php
    echo $this->Form->button('alle markieren', ['type' => 'button', 'onclick' => "$(':checkbox').each(function() { this.checked = true; });"]);
    echo '&nbsp;';
    echo $this->Form->button('keinen markieren', ['type' => 'button', 'onclick' => "$(':checkbox').each(function() { this.checked = false; });"]);
    echo $this->Form->submit('Zettel drucken', ['class' => 'button success radius']);	
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