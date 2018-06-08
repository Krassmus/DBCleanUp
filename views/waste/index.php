<form action="<?= PluginEngine::getLink($plugin, array(), "waste/takeout") ?>" method="post">
    <table class="default">
        <caption>
            <?= _("Inkonsistente Daten") ?>
            <span class="actions">
                <?= sprintf(_("%s Tabellen durchsucht"), $count_sorms) ?>
            </span>
        </caption>
        <thead>
            <tr>
                <th><?= _("Tabelle") ?></th>
                <th><?= _("Verwaiste Einträge") ?></th>
                <th class="actions">
                    <input type="checkbox" data-proxyfor=".default tbody :checkbox">
                </th>
            </tr>
        </thead>
        <tbody>
        <? if (count($tables)) : ?>
            <? foreach ($tables as $table) : ?>
                <tr>
                    <td><?= htmlReady($table['name']) ?></td>
                    <td><?= htmlReady($table['count']) ?></td>
                    <td class="actions">
                        <input type="checkbox" name="c[]" value="<?= htmlReady($table['class']) ?>">
                    </td>
                </tr>
            <? endforeach ?>
        <? else : ?>
            <tr>
                <td colspan="100">
                    <?= _("Es wurden keine inkonsistenten Daten gefunden.") ?>
                </td>
            </tr>
        <? endif ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="100" style="text-align: right;">
                    <?= \Studip\Button::create(_("Ausgewählte Tabellen bereinigen")) ?>
                </td>
            </tr>
        </tfoot>
    </table>


</form>