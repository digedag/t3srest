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
tx_rnbase::load('Tx_Rnbase_Utility_Strings');
tx_rnbase::load('tx_t3srest_util_FAL');
tx_rnbase::load('tx_rnbase_util_TSFAL');



/**
 * Sammelt zusätzliche Daten
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Team extends tx_t3rest_decorator_Base
{

    protected static $externals = array(
        'pictures',
        'logo',
        'players',
        'coaches',
        'supporters'
    );

    public function addPlayers($team, $configurations, $confId)
    {
        $this->addProfiles($team, $configurations, $confId, 'players');
    }

    public function addCoaches($team, $configurations, $confId)
    {
        $this->addProfiles($team, $configurations, $confId, 'coaches');
    }

    public function addSupporters($team, $configurations, $confId)
    {
        $this->addProfiles($team, $configurations, $confId, 'supporters');
    }

    /**
     * Hinzufügen der Spieler des Teams.
     *
     * @param tx_cfcleaguefe_models_team $team
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     *            Config-String für den Wrap der Profile
     * @param string $joinCol
     *            Name der Teamspalte mit den Profilen players, coaches, supporters
     */
    private function addProfiles($team, $configurations, $confId, $joinCol)
    {
        // $srv = tx_cfcleague_util_ServiceRegistry::getProfileService();
        $srv = tx_cfcleaguefe_util_ServiceRegistry::getProfileService();
        $fields['PROFILE.UID'][OP_IN_INT] = $team->getProperty($joinCol);
        $options = array();
        tx_rnbase_util_SearchBase::setConfigFields($fields, $configurations, $confId . 'fields.');
        tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, $confId . 'options.');
        $children = $srv->search($fields, $options);
        if (! empty($children) && ! array_key_exists('orderby', $options)) { // Default sorting
            $children = $this->sortProfiles($children, $team->getProperty($joinCol));
        }
        $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Profile');
        $decorator->setTeam($team);

        $team->$joinCol = array();
        foreach ($children as $child) {
            $data = $decorator->prepareItem($child, $configurations, $confId);
            array_push($team->$joinCol, $data);
        }
        $team->setProperty($joinCol, $team->$joinCol);
    }

    /**
     * Sortiert die Profile nach der Reihenfolge im Team
     *
     * @param array $profiles
     * @param string $sortArr
     * @return array
     */
    private function sortProfiles(&$profiles, $sortArr)
    {
        $sortArr = array_flip(Tx_Rnbase_Utility_Strings::intExplode(',', $sortArr));
        foreach ($profiles as $profile) {
            $sortArr[$profile->uid] = $profile;
        }
        $ret = array();
        foreach ($sortArr as $profile) {
            $ret[] = $profile;
        }
        return $ret;
    }

    /**
     * Team ein Logo zuordnen
     *
     * @param tx_cfcleague_models_Team $team
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     */
    public static function addLogo($team, $configurations, $confId)
    {
        // 1. Bild direkt zugeordnet
        $pics = tx_t3srest_util_FAL::getFalPictures($team->getUid(), 'tx_cfcleague_teams', 'logo', $configurations, $confId);
        if (empty($pics) && intval($team->getProperty('logo'))) {
            // 2. Schritt Feld logo
            $picCfg = $configurations->getKeyNames($confId);
            $refUid = $team->getProperty('logo');

            $fileRef = tx_rnbase_util_TSFAL::getFileReferenceById($refUid);
            if($fileRef && $fileRef->getOriginalFile()) {
                /* @var $fileObject \TYPO3\CMS\Core\Resource\File */
                $fileObject = $fileRef->getOriginalFile();
                $media = tx_rnbase::makeInstance('tx_rnbase_model_media', $fileObject);
                $pic = tx_t3srest_util_FAL::convertFal2StdClass($media->getProperty(), $configurations, $confId, $picCfg);
                $pics = [$pic];
            }
        }
        if (empty($pics) && $team->getClubUid()) {
            // 3. Schritt
            $pics = tx_t3srest_util_FAL::getFalPictures($team->getClubUid(), 'tx_cfcleague_club', 'logo', $configurations, $confId);
            // Am Club können mehrere Logos hängen. Wir nehmen nur das erste
            if (count($pics) > 1) {
                $pics = [$pics[0]];
            }
        }
        $team->setProperty('logo', ! empty($pics) ? $pics[0] : null);
    }

    protected function addPictures($team, $configurations, $confId)
    {
        $pics = tx_t3srest_util_FAL::getFalPictures($team->getUid(), 'tx_cfcleague_teams', 't3images', $configurations, $confId);
        $team->setProperty('pictures', $pics);
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
        return 'team';
    }
}
