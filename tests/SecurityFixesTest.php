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

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Mydashboard\CheckSchema;
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\Preference;
use GlpiPlugin\Mydashboard\Profile as MydashboardProfile;

/**
 * Rights of the configuration, the schema check and the preferences
 */
class SecurityFixesTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login('glpi', 'glpi');
        MydashboardProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    }

    /**
     * The schema check tab has no table: the configuration row cannot be read or written
     * through it with the dashboard right
     */
    public function testTheSchemaCheckIsNotAnItemOfTheConfigurationTable(): void
    {
        $this->assertNotInstanceOf(\CommonDBTM::class, new CheckSchema());
        $this->assertFalse(CheckSchema::canCreate());
        $this->assertFalse(CheckSchema::canUpdate());
        $this->assertFalse(CheckSchema::canPurge());
    }

    /**
     * A preference row is the one of a user: nobody else reads or changes it
     */
    public function testAPreferenceRowBelongsToItsUser(): void
    {
        $own   = (int) \Session::getLoginUserID();
        $other = (int) getItemByTypeName(\User::class, 'tech', true);

        // The dashboard right of the session (createFirstAccess() writes the profile only)
        $_SESSION['glpiactiveprofile']['plugin_mydashboard'] = CREATE | UPDATE;
        $preference = new Preference();
        $own_input   = ['id' => $own];
        $other_input = ['id' => $other];
        $this->assertTrue($preference->can(-1, UPDATE, $own_input));
        $this->assertFalse($preference->can(-1, UPDATE, $other_input));
        $this->assertFalse($preference->can(-1, CREATE, $other_input));
    }

    /**
     * Impact colors are printed in style attributes: only a CSS hex value is used
     */
    public function testImpactColorsAreCheckedWhenRead(): void
    {
        global $DB;

        $config = new Config();
        $config->getFromDB(1);
        $saved = $config->fields['impact_1'];

        $DB->update(Config::getTable(), ['impact_1' => 'red;background:url(x)'], ['id' => 1]);
        $config->getFromDB(1);
        $this->assertSame('', $config->getImpactColor(1));

        $DB->update(Config::getTable(), ['impact_1' => '#ff0000'], ['id' => 1]);
        $config->getFromDB(1);
        $this->assertSame('#ff0000', $config->getImpactColor(1));

        $DB->update(Config::getTable(), ['impact_1' => $saved], ['id' => 1]);
    }
}
