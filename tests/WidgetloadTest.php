<?php

namespace GlpiPlugin\Mydashboard\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Mydashboard\Widget;

/**
 * Test d'intégration équivalent à l'action AJAX ajax/loadWidgets.php.
 *
 * Vérifie que Widget::getCompleteWidgetList(true) s'exécute sans erreur critique
 * et retourne une liste de widgets valide avec session et base de données actives.
 */
class WidgetloadTest extends DbTestCase
{
    public function testGetCompleteWidgetListReturnsNonEmptyArray(): void
    {
        $this->login('glpi', 'glpi');

        $widgets = Widget::getCompleteWidgetList(true);

        $this->assertIsArray(
            $widgets,
            'Widget::getCompleteWidgetList() doit retourner un tableau'
        );
        $this->assertNotEmpty(
            $widgets,
            'Widget::getCompleteWidgetList() ne doit pas retourner un tableau vide'
        );
    }

    public function testGetCompleteWidgetListEntryStructure(): void
    {
        $this->login('glpi', 'glpi');

        $widgets = Widget::getCompleteWidgetList(true);

        foreach ($widgets as $gsId => $entry) {
            $this->assertArrayHasKey(
                'class',
                $entry,
                "L'entrée $gsId doit contenir une clé 'class'"
            );
            $this->assertArrayHasKey(
                'id',
                $entry,
                "L'entrée $gsId doit contenir une clé 'id'"
            );
            $this->assertNotEmpty(
                $entry['class'],
                "La classe du widget $gsId ne doit pas être vide"
            );
            $this->assertNotEmpty(
                $entry['id'],
                "L'identifiant du widget $gsId ne doit pas être vide"
            );
        }
    }
}
