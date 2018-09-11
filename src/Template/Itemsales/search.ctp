<div class="actions columns large-2 medium-3">
    <!-- <h3><?= __('Aktionen') ?></h3> -->
    <ul class="side-nav">
        <li><?= $this->Html->link('Zurück', ['controller' => 'Registers', 'action' => 'index'], ['class' => 'button radius success']) ?></li>
    </ul>
</div>
<div class="items index large-10 medium-9 columns">
    <?= $this->Form->create() ?>
    <fieldset>
        <legend>Suchfeld</legend>
        <?php
            echo $this->Form->input('barcode');
            echo $this->Form->input('bezeichnung');
            echo $this->Form->input('groesse', ['label' => 'Größe']);
        ?>
    </fieldset>
    <?= $this->Form->button('suchen') ?>
    <?= $this->Form->end() ?>
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Barcode</th>
            <th>Bezeichnung</th>
            <th>Größe</th>
            <th>Preis</th>
            <th>Verkauft</th>
            <th>um</th>
            <th class="actions"><?= __('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $i=0;
    foreach ($items as $item): 
    $i++; 
    if ($item->preis >= 100) {
    	echo '<tr style="background: #66CC00">';
    }
    else {
    	echo '<tr>';
    } ?>
            <td><?= h($item->barcode) ?></td>
            <td><?= h($item->bezeichnung) ?></td>
            <td><?= h($item->groesse) ?></td>
            <td><?= $this->Number->format($item->preis, ['pattern' => '#.###,00', 'places' => 2, 'before' => '', 'after' => ' €', 'thousands' => '.', 'decimals' => ',']) ?></td>
            <td><?= h($item->verkauft) ?></td>
            <td><?= h($item->modified) ?></td>
            <td class="actions">
                <?= $this->Html->link(__('Verlauf'), ['controller' => 'Items', 'action' => 'view', $item->barcode], ['class' => 'button micro radius']) ?>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
</div>
