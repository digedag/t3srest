<?php
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Model\Match;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use System25\T3sports\Utility\ServiceRegistry;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2022 Rene Nitzsche
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
 * Sammelt zusätzliche Daten
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Match extends tx_t3rest_decorator_Base
{
    private static $cache = [];

    protected static $externals = [
        'pictures',
        'coaches',
        'teams',
        'competition',
        'matchnotes',
        'referees',
        'players'
    ];

    /**
     */
    public static function addMatchnotes($item, $configurations, $confId)
    {

        $tickerUtil = new MatchTicker();
        $matchNotes = & $tickerUtil->getTicker4Match($item);
        $notes = array();
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_MatchNote');
        foreach ($matchNotes as $note) {
            $note->unsProperty('match');
            $notes[] = $decorator->prepareItem($note, $configurations, $confId);
        }
        $item->setProperty('matchNotes', $notes);
    }

    /**
     *
     * @param Match $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public static function addPlayers($item, $configurations, $confId)
    {
        $playersHome = [];
        $playersGuest = [];
        $substitutesHome = [];
        $substitutesGuest = [];

        $profileSrv = ServiceRegistry::getProfileService();
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        // FIXME: das Spiel nochmal über die alte API laden, um an die MatchNotes zu kommen
        /* @var $match \tx_cfcleaguefe_models_match */
//        $match = tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $item->getProperty());
        $profiles = $profileSrv->loadProfiles($item->getPlayersHome());
        foreach ($profiles as $profile) {
            $playersHome[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('playersHome', $playersHome);

        $profiles = $profileSrv->loadProfiles($item->getPlayersGuest());
        foreach ($profiles as $profile) {
            $playersGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('playersGuest', $playersGuest);

        $profiles = $profileSrv->loadProfiles($item->getSubstitutesHome());
        foreach ($profiles as $profile) {
            $substitutesHome[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('substitutesHome', $substitutesHome);

        $profiles = $profileSrv->loadProfiles($item->getSubstitutesGuest());
        foreach ($profiles as $profile) {
            $substitutesGuest[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('substitutesGuest', $substitutesGuest);
    }

    /**
     *
     * @param Match $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public static function addReferees($item, $configurations, $confId)
    {
        $referees = [];
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        $referee = $item->getReferee();
        // TODO: if($referee)
        $referees['referee'] = $decorator->prepareItem($referee, $configurations, $confId);
        $profiles = $item->getAssists();
        $referees['assists'] = [];
        foreach ($profiles as $profile) {
            $referees['assists'][] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('referees', $referees);
    }

    /**
     *
     * @param Match $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public static function addCoaches($item, $configurations, $confId)
    {
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        $profileSrv = ServiceRegistry::getProfileService();
        $profiles = $profileSrv->loadProfiles($item->getCoachHome());
        $item->setProperty('coachHome', !empty($profiles) ? $decorator->prepareItem($profiles[0], $configurations, $confId) : new \stdClass());
        $profiles = $profileSrv->loadProfiles($item->getCoachGuest());
        $item->setProperty('coachGuest', !empty($profiles) ? $decorator->prepareItem($profile[0], $configurations, $confId) : new \stdClass());
    }

    /**
     * Teams laden
     *
     * @param Match $item
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

    protected function addCompetition(Match $item, $configurations, $confId)
    {
        // Der Wettbewerb sollte Schon vorhanden sein
        /* @var $decorator tx_t3srest_decorator_Competition */
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Competition');
        $comp = $item->getCompetition();
        $cKey = sprintf('comp_%d', $comp->getUid());

        if(isset(self::$cache[$cKey])) {
            // Already processed!
            $item->setProperty('competition', self::$cache[$cKey]);
            return ;
        }

        $compObj = $decorator->prepareItem($comp, $configurations, $confId);
        $item->setProperty('competition', $compObj);
        self::$cache[$cKey] = $compObj;
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
    }
}
