<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Tests;

use GlpiPlugin\Mydashboard\Menu;
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
     * Toutes les classes déclarées dans Widgetlist::getList().
     * Utilisé pour vérifier le chargement de classe et la présence de getWidgetsForItem().
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
     * Classes dont getWidgetsForItem() construit sa liste sans interroger la base.
     *
     * Ces classes filtrent désormais leurs widgets sur des droits : Criteria::canReadTickets()
     * pour les widgets d'assistance, Session::haveRight() pour les autres. Les deux passent
     * par Session::haveRight(), qui lit $DB->isSlave() avant de consulter le profil — donc
     * une erreur fatale hors base. Le test les exécute via Session::callAsSystem(), qui
     * désactive ces vérifications en amont de tout accès à $DB : la liste complète est
     * alors déclarée, ce que cette suite vérifie.
     *
     * Les autres classes appellent $DB->request() dans getWidgetsForItem() même sans
     * contrôle de droit, et relèvent d'un test d'intégration avec base de données.
     *
     * @return array<string, array{class-string}>
     */
    public static function staticWidgetClassProvider(): array
    {
        return [
            'Reports_Bar'    => [Reports_Bar::class],
            'Reports_Pie'    => [Reports_Pie::class],
            'Reports_Line'   => [Reports_Line::class],
            'Reports_Table'  => [Reports_Table::class],
            'Reports_Funnel' => [Reports_Funnel::class],
            'Reports_Map'    => [Reports_Map::class],
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
            "Impossible de charger la classe widget : $classname",
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
            "$classname n'implémente pas la méthode getWidgetsForItem()",
        );
    }

    /**
     * getWidgetsForItem() doit retourner un tableau non vide sans lever d'exception.
     * Limité aux classes dont la méthode ne dépend pas de $DB ou de Session.
     * Les autres classes (Ticket, Reminder, Planning…) sont couvertes en intégration.
     *
     * @param class-string $classname
     */
    #[DataProvider('staticWidgetClassProvider')]
    public function testGetWidgetsForItemReturnsNonEmptyArray(string $classname): void
    {
        $instance = new $classname();
        $widgets  = \Session::callAsSystem(static fn() => $instance->getWidgetsForItem());

        $this->assertIsArray(
            $widgets,
            "$classname::getWidgetsForItem() doit retourner un tableau",
        );
        $this->assertNotEmpty(
            $widgets,
            "$classname::getWidgetsForItem() ne doit pas retourner un tableau vide",
        );
    }

    /**
     * Les widgets d'assistance agrègent glpi_tickets sans aucune clause d'acteur. Un profil
     * qui ne détient que Ticket::READMY ne doit donc pas se les voir proposer : c'est le
     * filtrage porté par Criteria::canReadTickets(), que rien ne couvrait jusqu'ici.
     */
    public function testHelpdeskWidgetsAreHiddenWithoutTicketRead(): void
    {
        $db_backup      = $GLOBALS['DB'] ?? null;
        $session_backup = $_SESSION ?? [];

        // Ici les droits sont bien évalués, donc Session::haveRight() lit $DB->isSlave().
        // Une instance non connectée suffit : isSlave() ne lit que la propriété $slave,
        // false par défaut. Toute requête réelle échouerait, ce qui est le comportement
        // voulu dans une suite unitaire.
        $GLOBALS['DB'] = new class extends \DBmysql {
            public function __construct() {}
        };
        $_SESSION['glpiactiveprofile'] = [\Ticket::$rightname => \Ticket::READMY];

        try {
            // Ces trois classes ne déclarent que des widgets de tickets.
            foreach ([Reports_Line::class, Reports_Pie::class, Reports_Map::class] as $classname) {
                $instance = new $classname();

                $this->assertSame(
                    [],
                    $instance->getWidgetsForItem(),
                    "$classname ne doit déclarer aucun widget sans droit de lecture des tickets",
                );
            }

            // Reports_Bar garde son widget d'inventaire, qui compte des ordinateurs.
            $bar = new Reports_Bar();

            $this->assertArrayNotHasKey(
                Menu::$HELPDESK,
                $bar->getWidgetsForItem(),
                "Reports_Bar ne doit déclarer aucun widget d'assistance sans droit de lecture des tickets",
            );
        } finally {
            $GLOBALS['DB'] = $db_backup;
            $_SESSION      = $session_backup;
        }
    }
}
