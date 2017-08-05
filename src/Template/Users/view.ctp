<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link(__('Barcodeliste drucken', true), array('controller' => 'Items', 'action' => 'drucken/'.$user->id), ['class' => 'button radius success']) ?></li>  
        <li><?= $this->Html->link('zurück', ['controller' => 'Users', 'action' => 'index'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="users view large-10 medium-9 columns">
    <h2><?= h($user->nummer.': '.($user->name=='' ? 'kein Name' : $user->name)) ?></h2>
    <div class="related row">
        <div class="column large-12">
        <?php if (!empty($user->items)) { ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th>Barcode</th>
                <th><?= __('Bezeichnung') ?></th>
                <th><?= __('Größe') ?></th>
                <th><?= __('Preis') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->items as $items): ?>
            <tr>
                <td><?= h($items->barcode) ?></td>
                <td><?= h($items->bezeichnung) ?></td>
                <td><?= h($items->groesse) ?></td>
                <td><?= $this->Number->format($items->preis, ['pattern' => '#.###,00', 'places' => 2, 'before' => '', 'after' => ' €', 'thousands' => '.', 'decimals' => ',']) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('bearbeiten'), ['controller' => 'Items', 'action' => 'edit', $items->id], ['class' => 'button micro round']) ?>
                    <?= $this->Form->postLink('löschen', ['controller' => 'Items', 'action' => 'delete', $items->id], ['class' => 'button alert micro round', 'confirm' => __('Wollen Sie den Artikel "'.$items->bezeichnung.'" wirklich löschen?')]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php } else { ?>
        Für diese Liste sind noch keine Artikel angelegt.
        <?php } ?>
        </div>
    </div>
</div>
