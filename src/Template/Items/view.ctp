<div class="actions columns large-2 medium-3">
    <!-- <h3><?= __('Aktionen') ?></h3> -->
    <ul class="side-nav">    
        <li><?= $this->Html->link(__('zurück'), ['controller' => 'Itemsales', 'action' => 'search'], ['class' => 'button radius']) ?></li>        
    </ul>
</div>

<div class="items index large-10 medium-9 columns">
	<p style="margin-top: 0.5rem; margin-left: 1rem;"><b>&nbsp;Barcode:&nbsp;<?php 
		echo $barcode.' - ';
                echo $user->nummer.': '.$user->name.' '.$user->vorname;
		if ($user->telefon != "") { echo ' ('.$user->telefon.')'; }
                echo '</b> &nbsp; ';
        ?></p>
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Zeitpunkt</th>
            <th>Bezeichnung</th>
            <th>Größe</th>
            <th>Preis</th>
            <th>Gedruckt</th>
            <th>Gelöscht</th>
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
            <td><?= h($item->modified) ?></td>
            <td><?= h($item->bezeichnung) ?></td>
            <td><?= h($item->groesse) ?></td>
            <td><?= $this->Number->format($item->preis, ['pattern' => '#.###,00', 'places' => 2, 'before' => '', 'after' => ' €', 'thousands' => '.', 'decimals' => ',']) ?></td>
            <td><?= h($item->gedruckt) ?></td>
            <td><?= h($item->alt) ?></td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
</div>
