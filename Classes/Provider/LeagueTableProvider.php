<?php

namespace System25\T3srest\Provider;

use stdClass;
use System25\T3sports\Table\Builder;
use System25\T3sports\Utility\ServiceRegistry;
use System25\T3sports\Model\Team;
use System25\T3sports\Model\Competition;
use System25\T3srest\Decorator\CompetitionDecorator;
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
class LeagueTableProvider extends AbstractBase
{

    protected function handleRequest($configurations, $confId)
    {
        $data = false;
        if ($tableAlias = $configurations->getParameters()->get('get')) {
            $tableData = $this->getLeagueTable($tableAlias, $configurations, $confId . 'get.');
            if ($tableData) {
                $data = new stdClass();
                $data->rounds = isset($tableData->rounds) ? $tableData->rounds : [];
                $decorator = tx_rnbase::makeInstance(CompetitionDecorator::class);
                $data->competition = $decorator->prepareItem($tableData->competition, $configurations, $confId . 'get.defined.' . $tableAlias . '.competition.');
            }
        }
        return $data;
    }

    protected function getConfId()
    {
        return 'leaguetable.';
    }

    protected function getBaseClass()
    {
        return Team::class;
    }

    /**
     * Lädt eine Ligatabelle.
     * Der Wettbewerb muss explizit gesetzt sein
     *
     * @param string $tableAlias string-Identifier
     * @return stdClass|null
     */
    private function getLeagueTable($tableAlias, $configurations, $confId)
    {
        $ret = null;
        // Prüfen, ob der Dienst konfiguriert ist
        $defined = $configurations->getKeyNames($confId . 'defined.');
        if (in_array($tableAlias, $defined)) {
            $ret = new stdClass();
            $compId = $configurations->get($confId . 'defined.' . $tableAlias . '.competitionSelection');
            $competition = tx_rnbase::makeInstance(Competition::class, $compId);
            $ret->competition = $competition;

            $fields = $options = [];
            $srv = ServiceRegistry::getMatchService();
            $matchTable = $srv->getMatchTableBuilder();
            $matchTable->setCompetitions($competition->getUid());
            $matchTable->setStatus('2');
            $matchTable->getFields($fields, $options);
            $matches = $srv->search($fields, $options);

            $table = Builder::buildByCompetitionAndMatches($competition, $matches, $configurations, $confId . 'defined.' . $tableAlias . '.');
            $result = $table->getTableData();
            $rounds = [];
            for ($i = 1, $max = $result->getRoundSize(); $i <= $max; $i ++) {
                $score = $result->getScores($i);
                for ($t = 0; $t < count($score); $t ++) {
                    $score[$t]['teamId'] = substr($score[$t]['teamId'], 2);
                    unset($score[$t]['team']);
                    unset($score[$t]['matches']);
                }
                $rounds[] = $score;
            }
            $ret->rounds = $rounds;
        }
        return $ret;
    }
}
