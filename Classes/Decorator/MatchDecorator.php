<?php
namespace System25\T3srest\Decorator;

use DMK\T3rest\Legacy\Decorator\BaseDecorator;
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Model\Fixture;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use System25\T3sports\Utility\ServiceRegistry;
use System25\T3srest\Utility\FALUtil;
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
 * Sammelt zusätzliche Daten
 *
 * @author Rene Nitzsche
 */
class MatchDecorator extends BaseDecorator
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

    private $matchNoteDecorator;
    private $profileDecorator;
    private $teamDecorator;

    public function __construct(
        MatchNoteDecorator $matchNoteDecorator,
        ProfileDecorator $profileDecorator,
        TeamDecorator $teamDecorator
    ) {
        $this->matchNoteDecorator = $matchNoteDecorator;
        $this->profileDecorator = $profileDecorator;
        $this->teamDecorator = $teamDecorator;
    }

    /**
     */
    public function addMatchnotes($item, $configurations, $confId)
    {

        $tickerUtil = new MatchTicker();
        $matchNotes = & $tickerUtil->getTicker4Match($item);
        $notes = [];
        $decorator = $this->matchNoteDecorator;
        foreach ($matchNotes as $note) {
            $note->unsProperty('match');
            $notes[] = $decorator->prepareItem($note, $configurations, $confId);
        }
        $item->setProperty('matchNotes', $notes);
    }

    /**
     *
     * @param Fixture $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public function addPlayers($item, $configurations, $confId)
    {
        $playersHome = [];
        $playersGuest = [];
        $substitutesHome = [];
        $substitutesGuest = [];

        $profileSrv = ServiceRegistry::getProfileService();
        $decorator = $this->profileDecorator;
        $decorator->setTeam($item->getHome());
        $profiles = $profileSrv->loadProfiles($item->getPlayersHome());
        foreach ($profiles as $idx => $profile) {
            $playersHome[] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('playersHome', $playersHome);

        $decorator->setTeam($item->getGuest());

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
     * @param Fixture $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public function addReferees($item, $configurations, $confId)
    {
        $referees = [];
        $decorator = $this->profileDecorator;
        $referee = $item->getReferee();
        // TODO: if($referee)
        $referees['referee'] = $decorator->prepareItem($referee, $configurations, $confId);

        $profileSrv = ServiceRegistry::getProfileService();
        $profiles = $profileSrv->loadProfiles($item->getAssists());
        $referees['assists'] = [];
        foreach ($profiles as $profile) {
            $referees['assists'][] = $decorator->prepareItem($profile, $configurations, $confId);
        }
        $item->setProperty('referees', $referees);
    }

    /**
     *
     * @param Fixture $item
     * @param ConfigurationInterface $configurations
     * @param ConfigurationInterface $confId
     */
    public function addCoaches($item, $configurations, $confId)
    {
        $decorator = $this->profileDecorator;
        $profileSrv = ServiceRegistry::getProfileService();
        $profiles = $profileSrv->loadProfiles($item->getCoachHome());
        $item->setProperty('coachHome', !empty($profiles) ? $decorator->prepareItem($profiles[0], $configurations, $confId) : new \stdClass());
        $profiles = $profileSrv->loadProfiles($item->getCoachGuest());
        $item->setProperty('coachGuest', !empty($profiles) ? $decorator->prepareItem($profiles[0], $configurations, $confId) : new \stdClass());
    }

    /**
     * Teams laden
     *
     * @param Fixture $item
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     */
    public function addTeams($item, $configurations, $confId)
    {
        /** @var TeamDecorator $decorator */
        $decorator = $this->teamDecorator;
        $home = $item->getHome();
        $item->setProperty('teamHome', $decorator->prepareItem($home, $configurations, $confId));
        $guest = $item->getGuest();
        $item->setProperty('teamGuest', $decorator->prepareItem($guest, $configurations, $confId));
    }

    protected function addCompetition(Fixture $item, $configurations, $confId)
    {
        // Der Wettbewerb sollte Schon vorhanden sein
        /** @var CompetitionDecorator $decorator */
        $decorator = tx_rnbase::makeInstance(CompetitionDecorator::class);
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
        $pics = FALUtil::getFalPictures($item->getUid(), 'tx_cfcleague_games', 't3images', $configurations, $confId);
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
