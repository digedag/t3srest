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

tx_rnbase::load('tx_t3rest_decorator_Base');
tx_rnbase::load('tx_t3rest_decorator_Simple');
tx_rnbase::load('tx_t3rest_util_DAM');


/**
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Profile extends tx_t3rest_decorator_Base {
	protected static $externals = array('pictures', 'teamnotes');

	protected function handleItemBefore($item, $configurations, $confId) {
		if($this->team)
			$item->addTeamNotes($this->team);

	}
	protected function handleItemAfter($item, $configurations, $confId) {
		unset($item->record['dam_images']);
	}
	protected function addTeamnotes($item, $configurations, $confId) {

		// Reimplementierung der TS-Config
		/*
#  tnposition =< lib.t3sports.teamnote
#  tnpicture =< lib.t3sports.teamnote
lib.t3sports.teamnote {
  source.current = 1
  tables =  tx_cfcleague_team_notes
  conf.tx_cfcleague_team_notes = CASE
  conf.tx_cfcleague_team_notes {
    1 = IMAGE
    1.file.maxW = 100
    1.file.maxH = 100
    1.file.import.cObject = USER
    1.file.import.cObject {
      userFunc=tx_dam_tsfe->fetchFileList
      refField=media
      refTable=tx_cfcleague_team_notes
    }
    2 = TEXT
    2.field = number
    default = TEXT
    default.field = comment
#    default.debugData = 1
    key.field = mediatype
		 */
		// Alle tn suchen??
		// $confId = players.record.externals.teamnotes.
		// Eventuell schon im TeamDeco setzen:
		$fields = t3lib_div::trimExplode(',', $configurations->get($confId.'fields'));
// if($item->uid == 1954){
// 	t3lib_div::debug($fields, $confId.'fields'.'_type'.' - tx_t3srest_decorator_Profile Line: '.__LINE__); // TODO: remove me
// 	t3lib_div::debug($item, $field.'_type'.' - tx_t3srest_decorator_Profile Line: '.__LINE__); // TODO: remove me
// 	exit();
// }

		foreach ($fields As $field) {
			if(!$fields) continue;
			$teamNote = tx_rnbase::makeInstance('tx_cfcleague_models_TeamNote', $item->getProperty($field));
			// Die TeamNote wird als eigenständiges Objekt ausgeliefert
			$note = new stdClass();
			// Die TeamNote enhält erstmal nur die Rohdaten. Bei Bildern müssen diese noch geladen werden.
			if($teamNote && $teamNote->isValid()) {
				$note->uid = $teamNote->getUid();
				$note->tstamp = $teamNote->getProperty('tstamp');
				$note->type = $teamNote->getMediaType();
				// Typ ermitteln
				if($teamNote->getMediaType() == 1) { // DAM-Reference
				    $pics = tx_t3srest_util_FAL::getFalPictures($teamNote->getUid(),
							'tx_cfcleague_team_notes', 'media', $configurations, $confId.$field.'.');
					//					$item->record[$field] = !empty($pics) ? $pics[0] : new stdClass();
					if(!empty($pics))
						$note->media = $pics[0];
				}
				else {
					$note->value = $teamNote->getValue();
//					$item->record[$field] = $teamNote->getValue();
				}
			}
			else {
//				$item->record[$field] = $field == 'tnpicture' ? new stdClass() : ''; // TeamNote ist ungültig
// 				if($field == 'tnpicture') // JSON erwartet ein Objekt
// 					$note->media = new stdClass()
			}

			$item->setProperty($field, $note);
			unset($item->record[$field.'_type']); // Dynamische Typen sind nicht notwendig
		}
	}
	protected function addPictures($item, $configurations, $confId) {
	    $pics = tx_t3srest_util_FAL::getFalPictures($item->getUid(), 'tx_cfcleague_profiles', 't3images', $configurations, $confId);
		$item->setProperty(pictures, $pics);
	}

	/**
	 * Wenn gesetzt, können die TeamNotes geladen werden.
	 * @param tx_cfcleague_models_Team $team
	 */
	public function setTeam($team) {
		$this->team = $team;
	}
	/**
	 * @overwrite
	 */
	protected function getExternals() {
		return self::$externals;
	}
	protected function getDecoratorId() {
		return 'profile';
	}
}

