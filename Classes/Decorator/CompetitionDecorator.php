<?php

namespace System25\T3srest\Decorator;

use DMK\T3rest\Legacy\Decorator\BaseDecorator;
use DMK\T3rest\Legacy\Utility\Objects;
use System25\T3sports\Model\Competition;
use System25\T3srest\Utility\FALUtil;

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
class CompetitionDecorator extends BaseDecorator
{

    protected static $externals = [
        'logo'
    ];

    /**
     * Team ein Logo zuordnen
     *
     * @param Competition $item
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     */
    public static function addLogo($item, $configurations, $confId)
    {
        // 1. Bild direkt zugeordnet
        $pics = FALUtil::getFalPictures($item->getUid(), 'tx_cfcleague_competition', 'logo', $configurations, $confId);
        $item->setProperty('logo', ! empty($pics) ? $pics[0] : null);
    }

    protected function getIgnoreFields($configurations, $confId)
    {
        if (! self::$ignoreFields) {
            self::$ignoreFields = array_merge(Objects::getIgnoreFields(), [
                'match_keys',
                'teams',
                'internal_name'
            ]);
        }
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
