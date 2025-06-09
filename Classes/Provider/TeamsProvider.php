<?php

namespace System25\T3srest\Provider;

use DMK\T3rest\Legacy\Exception\DataNotFoundException;
use System25\T3sports\Utility\ServiceRegistry;
use System25\T3sports\Model\Team;
use Sys25\RnBase\Frontend\Request\Request;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use System25\T3srest\Decorator\TeamDecorator;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2025 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

/**
 * This is a sample REST provider for T3sports teams
 * UseCases:
 * get = teamUid -> return a specific team
 * getdefined = cfc1 -> return a specific preconfigured team
 *
 * @author Rene Nitzsche
 */
class TeamsProvider extends AbstractBase
{

    private $teamDecorator;

    public function __construct(TeamDecorator $teamDecorator)
    {
        $this->teamDecorator = $teamDecorator;
    }

    protected function handleRequest($configurations, $confId)
    {
        if ($itemUid = $configurations->getParameters()->get('get')) {
            $confId = $confId . 'get.';
            $team = $this->getItem($itemUid, $configurations, $confId, array(
                ServiceRegistry::getTeamService(),
                'searchTeams'
            ));
            $decorator = $this->teamDecorator;
            $data = $decorator->prepareItem($team, $configurations, $confId);
        }
        return $data;
    }

    protected function getConfId()
    {
        return 'team.';
    }

    protected function getBaseClass()
    {
        return Team::class;
    }

    /**
     * Lädt ein einzelnes Team.
     * Erwartet wird entweder die TeamUID oder ein
     * Identifier. Letztere muss dann in der Config als Filter konfiguriert sein
     *
     * @param mixed $teamUid
     *            int oder string-Identifier
     * @return Team
     */
    public function getTeam($teamUid, $configurations, $confId)
    {
        if (intval($teamUid)) {
            $team = tx_rnbase::makeInstance(Team::class, intval($teamUid));
        }

        // Prüfen, ob der Dienst konfiguriert ist
        $defined = $configurations->getKeyNames($confId . 'defined.');
        if (in_array($teamUid, $defined)) {
            $request = new Request($configurations->getParameters(), $configurations, $confId);
            // Team per Config laden
            $filter = BaseFilter::createFilter($request, $confId . 'defined.' . $teamUid . '.filter.');
            $fields = [];
            $options = [];
            // suche initialisieren
            $filter->init($fields, $options);
            $options['forcewrapper'] = 1;
            $options['limit'] = 1;
            $teams = ServiceRegistry::getTeamService()->searchTeams($fields, $options);
            $team = ! empty($teams) ? $teams[0] : null;
        }

        if (! $team || ! $team->isValid()) {
            throw tx_rnbase::makeInstance(DataNotFoundException::class, 'Team not valid', 100);
        }

        return $team;
    }
}

