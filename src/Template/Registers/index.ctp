<?php
/**
  * @var \App\View\AppView $this
  * @var \App\Model\Entity\Register[]|\Cake\Collection\CollectionInterface $registers
  */
?>
<div class="actions columns large-2 medium-3">
    <ul class="side-nav">
        <li><?= $this->Html->link('neue Kasse anlegen', ['action' => 'add'], ['class' => 'button success radius']) ?></li>
        <li><?= $this->Html->link('zurück', ['controller' => 'Settings', 'action' => 'admin'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="registers index large-9 medium-8 columns content">
    <h3>Kassen</h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Login</th>  
                <th scope="col">IP im LAN</th>
                <th scope="col">angelegt</th>
                <th scope="col">geändert</th>
                <th scope="col">letzter Sync</th>
                <th scope="col">Kommentar</th>
                <th scope="col" class="actions"><?= __('Aktionen') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registers as $register): ?>
            <tr>
                <td><?= $this->Number->format($register->id) ?></td>
                <td><?= h($register->user->nummer) ?></td>
                <td><?= h($register->ip) ?></td>
                <td><?= h($register->created) ?></td>
                <td><?= h($register->modified) ?></td>
                <td><?= h($register->lastSync) ?></td>
                <td><?= h($register->comment) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Datendatei'), ['action' => 'datafile', $register->id], ['class' => 'button micro radius']) ?>
                    <?= $this->Html->link(__('Bearbeiten'), ['action' => 'edit', $register->id], ['class' => 'button micro radius']) ?>
                    <?= $this->Form->postLink(__('Löschen'), ['action' => 'delete', $register->id], 
                            ['confirm' => __('Soll die Kasse {0} wirklich gelöscht werden?', $register->id), 'class' => 'button micro alert radius']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
