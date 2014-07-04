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


/**
 * This is a sample REST provider for MatchNotes.
 * Als Parameter wird ein Spiel und Optional eine Zeitangabe
 * erwartet.
 * 
 * @author Rene Nitzsche
 */
class tx_t3srest_provider_MatchNotes extends tx_t3rest_provider_AbstractBase {

	protected function handleRequest($configurations, $confId) {
		if($itemUid = $configurations->getParameters()->get('get')) {
			$confId = $confId.'get.';
			$item = $this->getItem($itemUid, $configurations, $confId, array(tx_cfcleague_util_ServiceRegistry::getMatchService(),'search'));
			// Zu dem Spiel werden nun die eigentlichen MatchNotes geladen
			// Wir holen immer alle Notes, weil die Daten korrekt aufgebaut werden müssen
			// FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
			$match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->record);
			tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
			$matchNotes =& tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
			if($configurations->get($confId.'sorting') != 'asc')
				$matchNotes = array_reverse($matchNotes);
			$data = array();
			$decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_MatchNote');
			$minMinute = $configurations->getParameters()->getInt('minute');
			foreach($matchNotes As $note) {
				if(intval($note->record['minute']) < $minMinute)
					continue;
				unset($note->match);
				$data[] = $decorator->prepareItem($note, $configurations, $confId);
			}
		}
		return $data;
	}

	public function loadItem($item) {
		//
		$data = $this->decorator->prepareItem($item, $this->configurations, $this->confId);
		$this->items[] = $data;
	}
	protected function getBaseClass() {
		return 'tx_cfcleague_models_Match';
	}
	protected function getConfId() {
		return 'matchnote.';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_provider_Matches.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3srest/provider/class.tx_t3srest_provider_Matches.php']);
}
