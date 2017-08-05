<div class="actions columns large-2 medium-3">
    <!-- <h3><?= __('Aktionen') ?></h3> -->
    <ul class="side-nav">

        <?php 
        if ($aendernMoeglich) {
            if (!$voll) {
                echo '<li>'.$this->Html->link('Artikel anlegen ('.sizeof($items).'/'.$user->maxitems.')', 
                        ['action' => 'add'], ['class' => 'button radius']).'</li>';
            } 
            else {
                if ($erweiterbar) {
                    echo '<li>'.$this->Html->link('Artikelliste voll ('.sizeof($items).'/'.$user->maxitems.') Erweitern?', 
                            ['controller' => 'Users', 'action' => 'moremax'], ['class' => 'button radius', 
                             'confirm' => 'Wollen Sie die Artikelliste erweitern? Für weitere 30 Artikel werden zusätzliche 2€ Bearbeitungsgebühr fällig!']).'</li>';
                } else {
                    echo '<li>'.$this->Html->link('Artikelliste voll ('.sizeof($items).'/'.$user->maxitems.')', 
                            ['action' => ''], ['class' => 'button radius disabled']).'</li>';               
                }
            }
        }
        ?>
        <li><?= $this->Html->link(__('Liste Drucken', true), array('action' => 'drucken'), ['class' => 'button radius success']) ?></li>    
        <li><?= $this->Html->link(__('abmelden'), ['controller' => 'Users', 'action' => 'logout'], ['class' => 'button radius alert']) ?></li>        
    </ul>
</div>

<div class="items index large-10 medium-9 columns">
	<p style="margin-top: 0.5rem; margin-left: 1rem;"><b>&nbsp;Liste&nbsp;<?php 
		echo $user->nummer.': '.$user->name.' '.$user->vorname;
		if ($user->telefon != "") { echo ' ('.$user->telefon.')'; }
                echo '</b> &nbsp; ';
                if ($aendernMoeglich) {
                    echo $this->Html->link(__('Daten ändern'), ['controller' => 'Users', 'action' => 'edit'], ['class' => 'button micro round']);  
                }
        ?></p>
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Nr</th>
            <th><?= $this->Paginator->sort('bezeichnung') ?></th>
            <th><?= $this->Paginator->sort('groesse', 'Größe') ?></th>
            <th><?= $this->Paginator->sort('preis') ?></th>
            <?php if ($aendernMoeglich) { ?>
                <th class="actions"><?= __('Aktionen') ?></th>
            <?php } ?>
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
        <td><?= $i ?></td>
            <td><?= h($item->bezeichnung) ?></td>
            <td><?= h($item->groesse) ?></td>
            <td><?= $this->Number->format($item->preis, ['pattern' => '#.###,00', 'places' => 2, 'before' => '', 'after' => ' €', 'thousands' => '.', 'decimals' => ',']) ?></td>
            <?php if ($aendernMoeglich) { ?>
                <td class="actions">
                    <?= $this->Html->link(__('bearbeiten'), ['action' => 'edit', $item->id], ['class' => 'button micro round']) ?>
                    <?= $this->Form->postLink('löschen', ['action' => 'delete', $item->id], ['class' => 'button micro round', 'confirm' => __('Wollen Sie den Artikel "'.$item->bezeichnung.'" wirklich löschen?')]) ?>
                </td>
            <?php } ?>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>
    <?php
    /*
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
        </ul>
        <p><?= $this->Paginator->counter() ?></p>
    </div>
    
     */ ?>
</div>
