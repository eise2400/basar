<div class="actions columns large-2 medium-3">
    <!-- <h3><?= __('Aktionen') ?></h3> -->
    <ul class="side-nav">
        <li><?= $this->Html->link(__('Einstellungen', true), ['controller' => 'Settings', 'action' => 'index'], ['class' => 'button radius success']) ?></li>
        <li><?= $this->Html->link(__('Listen & Benutzer', true), ['controller' => 'Users', 'action' => 'index'], ['class' => 'button radius success']) ?></li>
        <li><?= $this->Html->link(__('Abrechnung drucken', true), ['controller' => 'Users', 'action' => 'indexAbrechnung'], ['class' => 'button radius success']) ?></li>
        <li><?= $this->Html->link(__('Kassen', true), ['controller' => 'Registers', 'action' => 'index'], ['class' => 'button radius success']) ?></li>
        <li><?= $this->Html->link(__('Daten herunterladen', true), ['controller' => 'Settings', 'action' => 'export'], ['class' => 'button radius success']) ?></li>
        <li><?= $this->Html->link(__('abmelden'), ['controller' => 'Users', 'action' => 'logout'], ['class' => 'button radius alert']) ?></li>        
    </ul>
</div>
<div class="settings index large-10 medium-9 columns">
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Wert</th>
            <th>#</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($daten as $dat) {
            foreach ($dat as $bez => $zahl): ?>
        <tr>
            <td><?= h($bez) ?></td>
            <td><?= h($zahl) ?></td>
        </tr>

    <?php   endforeach;
          }  ?>
    </tbody>
    </table>
</div>