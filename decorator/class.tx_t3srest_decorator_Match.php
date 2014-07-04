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

tx_rnbase::load('tx_t3rest_decorator_Base');
tx_rnbase::load('tx_t3rest_util_DAM');


/**
 * Sammelt zusätzliche Daten
 * 
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Match extends tx_t3rest_decorator_Base {
	protected static $externals = array('pictures', 'coaches', 'teams', 'competition', 'matchnotes', 'referees', 'players');

	/**
	 *
	 */
	public static function addMatchnotes($item, $configurations, $confId) {

		// FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
		$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->record);
		tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
		$matchNotes =& tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
		$item->matchNotes = array();
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_MatchNote');
		foreach($matchNotes As $note) {
			unset($note->match);
			$item->matchNotes[] = $decorator->prepareItem($note, $configurations, $confId);
		}
	}
	public static function addPlayers($item, $configurations, $confId) {
		$item->playersHome = array();
		$item->playersGuest = array();
		$item->substitutesHome = array();
		$item->substitutesGuest = array();
		
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
		// FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
		$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->record);
		$profiles = $match->getPlayersHome();
		foreach($profiles As $profile) {
			$item->playersHome[] = $decorator->prepareItem($profile, $configurations, $confId);
		}
		$profiles = $match->getPlayersGuest();
		foreach($profiles As $profile) {
			$item->playersGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
		}
		$profiles = $match->getSubstitutesHome();
		foreach($profiles As $profile) {
			$item->substitutesHome[] = $decorator->prepareItem($profile, $configurations, $confId);
		}
			$profiles = $match->getSubstitutesGuest();
		foreach($profiles As $profile) {
			$item->substitutesGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
		}
// 		t3lib_div::debug($item, 'tx_t3srest_decorator_Match: '.__LINE__);
// 		exit();
		
	}
	public static function addReferees($item, $configurations, $confId) {
		$item->referees = array();
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
		// FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
		$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->record);
		$referee = $match->getReferee();
		//TODO: if($referee)
			$item->referees['referee'] = $decorator->prepareItem($referee, $configurations, $confId);
		$profiles = $match->getAssists();
		$item->referees['assists'] = array();
		foreach($profiles As $profile) {
			$item->referees['assists'][] = $decorator->prepareItem($profile, $configurations, $confId);
		}
	}
	public static function addCoaches($item, $configurations, $confId) {
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
		// FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
		$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->record);
		$item->coachHome = $decorator->prepareItem($match->getCoachHome(), $configurations, $confId);
		$item->coachGuest = $decorator->prepareItem($match->getCoachGuest(), $configurations, $confId);
	}
	/**
	 * Teams laden
	 *
	 * @param tx_cfcleague_models_Match $team
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 */
	public static function addTeams($item, $configurations, $confId) {
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Team');
		$home = $item->getHome();
		$item->teamHome = $decorator->prepareItem($home, $configurations, $confId);
		$guest = $item->getGuest();
		$item->teamGuest = $decorator->prepareItem($guest, $configurations, $confId);
	}
	protected function addCompetition($item, $configurations, $confId) {
		// Der Wettbewerb sollte Schon vorhanden sein
		$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Competition');
		$comp = $item->getCompetition();
		$item->competition = $decorator->prepareItem($comp, $configurations, $confId);
	}
	protected function addPictures($item, $configurations, $confId) {
		$pics = tx_t3rest_util_DAM::getDamPictures($item->getUid(), 'tx_cfcleague_games', 'dam_images', $configurations, $confId);
		$item->pictures = $pics;
	}

	/**
	 * @overwrite
	 */
	protected function getExternals() {
		return self::$externals;
	}
	protected function getDecoratorId() {
		return 'match';
	}
	protected function handleItemBefore($item, $configurations, $confId) {
		$item->initResult();
	}
	protected function handleItemAfter($item, $configurations, $confId) {
		unset($item->_teamHome);
		unset($item->_teamGuest);

		if(!($item->competition instanceof stdClass))
			unset($item->competition);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_decorator_Team.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_decorator_Team.php']);
}
