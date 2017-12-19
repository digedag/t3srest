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

/**
 * Sammelt zusätzliche Daten
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_decorator_Competition extends tx_t3rest_decorator_Base
{

    protected static $externals = array(
        'logo'
    );

    // public function prepareItem($item, $configurations, $confId) {
    // $ret = parent::prepareItem($item, $configurations, $confId);
    // t3lib_div::debug($ret, 'tx_t3srest_decorator_Competition: '.__LINE__);
    // exit();
    // return $ret;
    // }
    
    /**
     * Team ein Logo zuordnen
     *
     * @param tx_cfcleague_models_Competition $team
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     */
    public static function addLogo($item, $configurations, $confId)
    {
        // 1. Bild direkt zugeordnet
        $pics = tx_t3srest_util_FAL::getFalPictures($item->getUid(), 'tx_cfcleague_competition', 'logo', $configurations, $confId);
        $item->setProperty('logo', ! empty($pics) ? $pics[0] : null);
    }

    protected function getIgnoreFields()
    {
        if (! self::$ignoreFields)
            self::$ignoreFields = array_merge(tx_t3rest_util_Objects::getIgnoreFields(), array(
                'match_keys',
                'teams',
                'internal_name'
            ));
        return self::$ignoreFields;
    }

    private static $ignoreFields;

    /**
     * @overwrite
     */
    protected function getExternals()
    {
        return self::$externals;
    }

    protected function getDecoratorId()
    {
        return 'competition';
    }
}
