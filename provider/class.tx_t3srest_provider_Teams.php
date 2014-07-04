<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Rene Nitzsche
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

tx_rnbase::load('tx_t3rest_models_Provider');
tx_rnbase::load('tx_t3rest_provider_AbstractBase');
tx_rnbase::load('tx_t3rest_util_Objects');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_t3rest_util_DAM');
tx_rnbase::load('tx_rnbase_util_Logger');



/**
 * This is a sample REST provider for T3sports teams
 * UseCases:
 * get = teamUid -> return a specific team
 * getdefined = cfc1 -> return a specific preconfigured team
 * 
 * @author Rene Nitzsche
 */
class tx_t3srest_provider_Teams extends tx_t3rest_provider_AbstractBase {

	protected function handleRequest($configurations, $confId) {
		if($itemUid = $configurations->getParameters()->get('get')) {
			$confId = $confId.'get.';
			$team = $this->getItem($itemUid, $configurations, $confId, array(tx_cfcleague_util_ServiceRegistry::getTeamService(),'searchTeams'));
//			$team = $this->getTeam($teamUid, $configurations, $confId.'get.');
			$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Team');
			$data = $decorator->prepareItem($team, $configurations, $confId);
		}
		return $data;
	}

	protected function getConfId() {
		return 'team.';
	}
	protected function getBaseClass() {
		return 'tx_cfcleague_models_Team';
	}

	/**
	 * Lädt ein einzelnes Team. Erwartet wird entweder die TeamUID oder ein 
	 * Identifier. Letztere muss dann in der Config als Filter konfiguriert sein
	 *
	 * @param mixed $teamUid int oder string-Identifier
	 * @return tx_cfcleague_models_Team
	 */
	public function getTeam($teamUid, $configurations, $confId) {
		if(intval($teamUid)) {
			$team = tx_rnbase::makeInstance('tx_cfcleague_models_Team', intval($teamUid));
		}

		// Prüfen, ob der Dienst konfiguriert ist
		$defined = $configurations->getKeyNames($confId.'defined.');
		if(in_array($teamUid, $defined)) {
			// Team per Config laden
			$filter = tx_rnbase_filter_BaseFilter::createFilter($configurations->getParameters(), $configurations, null, $confId.'defined.'.$teamUid.'.filter.');
			$fields = array();
			$options = array();
			//suche initialisieren
			$filter->init($fields, $options);
			$options['forcewrapper'] = 1;
			$options['limit'] = 1;
			$teams = tx_cfcleague_util_ServiceRegistry::getTeamService()->searchTeams($fields, $options);
			$team = !empty($teams) ? $teams[0] : null;
		}

		if(!$team || !$team->isValid())
			throw tx_rnbase::makeInstance('tx_t3rest_exeption_DataNotFound', 'Team not valid', 100);

		return $team;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_provider_Teams.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_provider_Teams.php']);
}
