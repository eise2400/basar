<div class="actions columns large-2 medium-3">
    <!-- <h3><?= __('Aktionen') ?></h3> -->
    <ul class="side-nav">
        <li><?= $this->Html->link(__('ohne speichern zurück'), ['action' => 'index'], ['class' => 'button radius']) ?></li>
    </ul>
</div>
<div class="items form large-10 medium-9 columns">
    <?= $this->Form->create($item) ?>
    <fieldset>
        <legend><?= __('Artikel bearbeiten') ?></legend>
		<p><i><b>Angenommen werden:</b> gut erhaltene, moderne Herbst/Winter-Kinderbekleidung, Umstandsmode, Spielsachen, Kinderbücher, Kinderautositze, Kinderwagen und Kinderfahrzeuge.<br/> 
		<b>NICHT angenommen werden:</b> Plüschtiere, Socken, Strumpfhosen, Unterwäsche, Schlafanzüge.</i></p>
        <?php
            echo $this->Form->input('bezeichnung');
            echo $this->Form->input('groesse', ['label' => 'Größe (sofern zutreffend)']);
            echo $this->Form->input('preisdt', ['step' => '0.5', 'min' => '0.5', 'max' => '999.5', 'label' => 'Preis in € (nur ganze oder halbe Eurobeträge erlaubt)']);
        ?>
    </fieldset>
    <?= $this->Form->button(__('ändern')) ?>
    <?= $this->Form->end() ?>
</div>
