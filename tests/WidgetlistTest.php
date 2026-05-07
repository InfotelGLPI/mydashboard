<?php

namespace GlpiPlugin\Mydashboard\Tests;

use GlpiPlugin\Mydashboard\Reports\Change;
use GlpiPlugin\Mydashboard\Reports\Contract;
use GlpiPlugin\Mydashboard\Reports\Event;
use GlpiPlugin\Mydashboard\Reports\KnowbaseItem;
use GlpiPlugin\Mydashboard\Reports\Planning;
use GlpiPlugin\Mydashboard\Reports\Problem;
use GlpiPlugin\Mydashboard\Reports\Project;
use GlpiPlugin\Mydashboard\Reports\ProjectTask;
use GlpiPlugin\Mydashboard\Reports\Reminder;
use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Custom;
use GlpiPlugin\Mydashboard\Reports\Reports_Funnel;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Map;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Mydashboard\Reports\RSSFeed;
use GlpiPlugin\Mydashboard\Reports\Ticket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie que chaque classe widget déclarée dans Widgetlist::getList()
 * est chargeable sans erreur critique et expose une liste de widgets valide.
 *
 * Équivalent PHP de l'action AJAX « load-widgets ».
 */
class WidgetlistTest extends TestCase
{
    /**
     * Classes déclarées dans Widgetlist::getList() qui implémentent getWidgetsForItem().
     *
     * @return array<string, array{class-string}>
     */
    public static function widgetClassProvider(): array
    {
        return [
            'Reports_Bar'    => [Reports_Bar::class],
            'Reports_Pie'    => [Reports_Pie::class],
            'Reports_Line'   => [Reports_Line::class],
            'Reports_Table'  => [Reports_Table::class],
            'Reports_Funnel' => [Reports_Funnel::class],
            'Reports_Map'    => [Reports_Map::class],
            'Reports_Custom' => [Reports_Custom::class],
            'Ticket'         => [Ticket::class],
            'Reminder'       => [Reminder::class],
            'Planning'       => [Planning::class],
            'Event'          => [Event::class],
            'Problem'        => [Problem::class],
            'Change'         => [Change::class],
            'RSSFeed'        => [RSSFeed::class],
            'Project'        => [Project::class],
            'ProjectTask'    => [ProjectTask::class],
            'Contract'       => [Contract::class],
            'KnowbaseItem'   => [KnowbaseItem::class],
        ];
    }

    /**
     * Aucune erreur fatale ne doit se produire lors du chargement de la classe.
     *
     * @param class-string $classname
     */
    #[DataProvider('widgetClassProvider')]
    public function testWidgetClassExists(string $classname): void
    {
        $this->assertTrue(
            class_exists($classname),
            "Impossible de charger la classe widget : $classname"
        );
    }

    /**
     * Chaque classe doit exposer getWidgetsForItem() — contrat de l'action load-widgets.
     *
     * @param class-string $classname
     */
    #[DataProvider('widgetClassProvider')]
    public function testWidgetClassImplementsGetWidgetsForItem(string $classname): void
    {
        $this->assertTrue(
            method_exists($classname, 'getWidgetsForItem'),
            "$classname n'implémente pas la méthode getWidgetsForItem()"
        );
    }

    /**
     * getWidgetsForItem() doit retourner un tableau non vide sans lever d'exception.
     *
     * @param class-string $classname
     */
    #[DataProvider('widgetClassProvider')]
    public function testGetWidgetsForItemReturnsNonEmptyArray(string $classname): void
    {
        $instance = new $classname();
        $widgets  = $instance->getWidgetsForItem();

        $this->assertIsArray(
            $widgets,
            "$classname::getWidgetsForItem() doit retourner un tableau"
        );
        $this->assertNotEmpty(
            $widgets,
            "$classname::getWidgetsForItem() ne doit pas retourner un tableau vide"
        );
    }
}
