<?php

namespace GlpiPlugin\Mydashboard\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Mydashboard\Profile as MydashboardProfile;
use GlpiPlugin\Mydashboard\Widget;

/**
 * Test d'intégration équivalent à l'action AJAX ajax/loadWidgets.php.
 *
 * Vérifie que Widget::getCompleteWidgetList(true) s'exécute sans erreur critique
 * et retourne une liste de widgets valide avec session et base de données actives.
 */
class WidgetloadTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login('glpi', 'glpi');
        // Le profil actif du test peut différer de celui utilisé par glpi:plugin:install en CLI.
        // On accorde explicitement les droits plugin_mydashboard = CREATE+UPDATE (= 6)
        // pour que ProfileAuthorizedWidget::getAuthorizedListForProfile() retourne false
        // (accès illimité) plutôt que [] (tout filtré).
        MydashboardProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    }

    public function testGetCompleteWidgetListReturnsNonEmptyArray(): void
    {
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
        $widgets = Widget::getCompleteWidgetList(true);

        $this->assertNotEmpty(
            $widgets,
            'Widget::getCompleteWidgetList() ne doit pas retourner un tableau vide (prérequis de structure)'
        );

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
