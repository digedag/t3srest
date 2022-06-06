<?php
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Team;
use System25\T3sports\Decorator\TeamNoteDecorator;
use System25\T3sports\Model\Repository\TeamNoteRepository;
use System25\T3sports\Model\Profile;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use System25\T3sports\Model\TeamNote;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2017 Rene Nitzsche
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
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Profile extends tx_t3rest_decorator_Base
{
    private $tnDecorator;
    private $team;

    protected static $externals = [
        'pictures',
        'teamnotes'
    ];

    public function __construct()
    {
        $this->tnDecorator = new TeamNoteDecorator(new TeamNoteRepository());
    }

    protected function handleItemBefore($item, $configurations, $confId)
    {
        if ($this->team) {
            $item->addTeamNotes($this->team);
        }
    }

    /**
     *
     * @param Profile $item
     * @param ConfigurationInterface $configurations
     * @param string $confId
     */
    protected function handleItemAfter($item, $configurations, $confId)
    {
        $item->unsProperty('dam_images');
    }

    /**
     *
     * @param Profile $item
     * @param ConfigurationInterface $configurations
     * @param string $confId
     */
    protected function addTeamnotes($item, $configurations, $confId)
    {

        // Reimplementierung der TS-Config
        /*
         * # tnposition =< lib.t3sports.teamnote
         * # tnpicture =< lib.t3sports.teamnote
         * lib.t3sports.teamnote {
         * source.current = 1
         * tables = tx_cfcleague_team_notes
         * conf.tx_cfcleague_team_notes = CASE
         * conf.tx_cfcleague_team_notes {
         * 1 = IMAGE
         * 1.file.maxW = 100
         * 1.file.maxH = 100
         * 1.file.import.cObject = USER
         * 1.file.import.cObject {
         * userFunc=tx_dam_tsfe->fetchFileList
         * refField=media
         * refTable=tx_cfcleague_team_notes
         * }
         * 2 = TEXT
         * 2.field = number
         * default = TEXT
         * default.field = comment
         * # default.debugData = 1
         * key.field = mediatype
         */
        // Alle tn suchen??
        // $confId = players.record.externals.teamnotes.
        // Eventuell schon im TeamDeco setzen:
        $this->tnDecorator->addTeamNotes($item, $this->team);

        $fields = Strings::trimExplode(',', $configurations->get($confId . 'fields'));
        // if($item->uid == 1954){
        // t3lib_div::debug($fields, $confId.'fields'.'_type'.' - tx_t3srest_decorator_Profile Line: '.__LINE__); // TODO: remove me
        // t3lib_div::debug($item, $field.'_type'.' - tx_t3srest_decorator_Profile Line: '.__LINE__); // TODO: remove me
        // exit();
        // }
        foreach ($fields as $field) {
            if (! $field) {
                continue;
            }
            $teamNote = tx_rnbase::makeInstance(TeamNote::class, $item->getProperty($field));
            // Die TeamNote wird als eigenständiges Objekt ausgeliefert
            $note = new stdClass();
            // Die TeamNote enhält erstmal nur die Rohdaten. Bei Bildern müssen diese noch geladen werden.
            if ($teamNote && $teamNote->isValid()) {
                $note->uid = $teamNote->getUid();
                $note->tstamp = $teamNote->getProperty('tstamp');
                $note->type = $teamNote->getMediaType();
                // Typ ermitteln
                if ($teamNote->getMediaType() == 1) { // DAM-Reference
                    $pics = tx_t3srest_util_FAL::getFalPictures($teamNote->getUid(), 'tx_cfcleague_team_notes', 'media', $configurations, $confId . $field . '.');
                    if (! empty($pics))
                        $note->media = $pics[0];
                } else {
                    $note->value = $teamNote->getValue();
                }
            } else {
                // $item    [$field] = $field == 'tnpicture' ? new stdClass() : ''; // TeamNote ist ungültig
                // if($field == 'tnpicture') // JSON erwartet ein Objekt
                // $note->media = new stdClass()
            }

            $item->setProperty($field, $note);
            $item->unsProperty($field . '_type'); // Dynamische Typen sind nicht notwendig
        }
    }

    protected function addPictures($item, $configurations, $confId)
    {
        $pics = tx_t3srest_util_FAL::getFalPictures($item->getUid(), 'tx_cfcleague_profiles', 't3images', $configurations, $confId);
        $item->setProperty(pictures, $pics);
    }

    /**
     * Wenn gesetzt, können die TeamNotes geladen werden.
     *
     * @param Team $team
     */
    public function setTeam($team)
    {
        $this->team = $team;
    }

    /**
     * @overwrite
     */
    protected function getExternals()
    {
        return self::$externals;
    }

    protected function getDecoratorId()
    {
        return 'profile';
    }
}
