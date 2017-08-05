<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('alle Benutzer löschen', ['controller' => 'Users', 'action' => 'delall'], ['confirm' => 'Wollen Sie wirklch ALLE Benutzer löschen? Dies löscht auch alle Artikel!', 'class' => 'button alert radius']) ?></li>
        <li><?= $this->Html->link(__('Zurück', true), ['controller' => 'Settings', 'action' => 'admin'], ['class' => 'button radius success']) ?></li>

    </ul>
</div>
<div class="settings index large-10 medium-9 columns">
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th><?= $this->Paginator->sort('name') ?></th>
            <th><?= $this->Paginator->sort('wert') ?></th>
            <th><?= $this->Paginator->sort('hinweis') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($settings as $setting): ?>
        <tr>
            <td><?= h($setting->name) ?></td>
            <td><?= h($setting->wert) ?></td>
            <td><?= h($setting->hinweis) ?></td>
            <td class="actions">
                <?= $this->Html->link(__('Bearbeiten'), ['action' => 'edit', $setting->id]) ?>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
</div>
