<?php
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
tx_rnbase::load('tx_t3rest_decorator_Base');
tx_rnbase::load('tx_t3srest_util_FAL');

/**
 * Sammelt zusätzliche Daten
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Match extends tx_t3rest_decorator_Base
{

    protected static $externals = array(
        'pictures',
        'coaches',
        'teams',
        'competition',
        'matchnotes',
        'referees',
        'players'
    );

    /**
     */
    public static function addMatchnotes($item, $configurations, $confId)
    {
        
        // FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
        $match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->getProperty());
        tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
        $matchNotes = & tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
        $notes = array();
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_MatchNote');
        foreach ($matchNotes as $note) {
            unset($note->match);
            $notes[] = $decorator->prepareItem($note, $configurations, $confId);
        }
        $item->setProperty('matchNotes', $notes);
    }

    public static function addPlayers($item, $configurations, $confId)
    {
        $playersHome = array();
        $playersGuest = array();
        $substitutesHome = array();
        $substitutesGuest = array();
        
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        // FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
        $match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->getProperty());
        $profiles = $match->getPlayersHome();
        foreach ($profiles as $profile) {
            $playersHome[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('playersHome', $playersHome);
        
        $profiles = $match->getPlayersGuest();
        foreach ($profiles as $profile) {
            $item->playersGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
            $playersGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('playersGuest', $playersGuest);
        
        $profiles = $match->getSubstitutesHome();
        foreach ($profiles as $profile) {
            $substitutesHome[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('substitutesHome', $substitutesHome);
        
        $profiles = $match->getSubstitutesGuest();
        foreach ($profiles as $profile) {
            $substitutesGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('substitutesGuest', $substitutesGuest);
        // t3lib_div::debug($item, 'tx_t3srest_decorator_Match: '.__LINE__);
        // exit();
    }

    public static function addReferees($item, $configurations, $confId)
    {
        $referees = array();
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        // FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
        $match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->getProperty());
        $referee = $match->getReferee();
        // TODO: if($referee)
        $referees['referee'] = $decorator->prepareItem($referee, $configurations, $confId);
        $profiles = $match->getAssists();
        $referees['assists'] = array();
        foreach ($profiles as $profile) {
            $referees['assists'][] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('referees', $referees);
    }

    public static function addCoaches($item, $configurations, $confId)
    {
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        // FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
        $match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->getProperty());
        $item->setProperty('coachHome', $decorator->prepareItem($match->getCoachHome(), $configurations, $confId));
        $item->setProperty('coachGuest', $decorator->prepareItem($match->getCoachGuest(), $configurations, $confId));
    }

    /**
     * Teams laden
     *
     * @param tx_cfcleague_models_Match $team
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     */
    public static function addTeams($item, $configurations, $confId)
    {
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Team');
        $home = $item->getHome();
        $item->setProperty('teamHome', $decorator->prepareItem($home, $configurations, $confId));
        $guest = $item->getGuest();
        $item->setProperty('teamGuest', $decorator->prepareItem($guest, $configurations, $confId));
    }

    protected function addCompetition($item, $configurations, $confId)
    {
        // Der Wettbewerb sollte Schon vorhanden sein
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Competition');
        $comp = $item->getCompetition();
        $item->setProperty('competition', $decorator->prepareItem($comp, $configurations, $confId));
    }

    protected function addPictures($item, $configurations, $confId)
    {
        $pics = tx_t3srest_util_FAL::getFalPictures($item->getUid(), 'tx_cfcleague_games', 't3images', $configurations, $confId);
        $item->setProperty('pictures', $pics);
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
        return 'match';
    }

    protected function handleItemBefore($item, $configurations, $confId)
    {
        $item->initResult();
    }

    protected function handleItemAfter($item, $configurations, $confId)
    {
        unset($item->_teamHome);
        unset($item->_teamGuest);
        
        if (! ($item->competition instanceof stdClass))
            unset($item->competition);
    }
}
