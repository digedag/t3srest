<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2016 Rene Nitzsche
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


tx_rnbase::load('tx_t3rest_models_Provider');
tx_rnbase::load('tx_t3rest_provider_AbstractBase');
tx_rnbase::load('tx_t3rest_util_Objects');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_t3srest_util_FAL');
tx_rnbase::load('tx_rnbase_util_Logger');



/**
 * This is a sample REST provider for T3sports teams
 * UseCases:
 * get = teamUid -> return a specific team
 * getdefined = cfc1 -> return a specific preconfigured team
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_provider_LeagueTable extends tx_t3rest_provider_AbstractBase {

	protected function handleRequest($configurations, $confId) {
		if($tableAlias = $configurations->getParameters()->get('get')) {
			$tableData = $this->getLeagueTable($tableAlias, $configurations, $confId.'get.');
			if($tableData) {
//				$data = $tableData->rounds;
				$data->rounds = $tableData->rounds;
				$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Competition');
				$data->competition = $decorator->prepareItem($tableData->competition, $configurations, $confId.'get.defined.'.$tableAlias.'.competition.');
			}

		}
		return $data;
	}

	protected function getConfId() {
		return 'leaguetable.';
	}
	protected function getBaseClass() {
		return 'tx_cfcleague_models_Team';
	}

	/**
	 * Lädt eine Ligatabelle. Der Wettbewerb muss explizit gesetzt sein
	 *
	 * @param string $tableAlias string-Identifier
	 * @return tx_cfcleague_models_Team
	 */
	private function getLeagueTable($tableAlias, $configurations, $confId) {

		$ret = false;
		// Prüfen, ob der Dienst konfiguriert ist
		$defined = $configurations->getKeyNames($confId.'defined.');
		if(in_array($tableAlias, $defined)) {
			$ret = new stdClass();
			$compId = $configurations->get($confId.'defined.'.$tableAlias.'.competitionSelection');
			$competition = tx_rnbase::makeInstance('tx_cfcleague_models_Competition', $compId);
			$ret->competition = $competition;

			$srv = tx_cfcleague_util_ServiceRegistry::getMatchService();
			$matchTable = $srv->getMatchTableBuilder();
			$matchTable->setCompetitions($competition->getUid());
			$matchTable->setStatus('2');
			$matchTable->getFields($fields, $options);
			$matches = $srv->search($fields, $options);

			tx_rnbase::load('tx_cfcleaguefe_table_Builder');
			$table = tx_cfcleaguefe_table_Builder::buildByCompetitionAndMatches($competition, $matches, $configurations, $confId.'defined.'.$tableAlias.'.');
			$result = $table->getTableData();
			$rounds = array();
			for($i=1, $max= $result->getRoundSize(); $i<=$max; $i++) {
				$score = $result->getScores($i);
				for($t=0; $t < count($score); $t++) {
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

