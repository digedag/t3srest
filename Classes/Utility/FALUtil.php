<?php

namespace System25\T3srest\Utility;

use stdClass;
use Sys25\RnBase\Utility\Misc;
use Sys25\RnBase\Utility\TSFAL;

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
 * Utilty methods for FAL
 *
 * @author Rene Nitzsche
 */
class FALUtil
{

    public static function getFalPictures($refUid, $refTable, $refField, $configurations, $confId, $fields = array())
    {
        $picCfg = $configurations->getKeyNames($confId);
        if (empty($fields))
            $fields = [
                'uid',
                'title',
                'file_hash',
                'tstamp'
            ];
        $ret = [];
        $files = TSFAL::fetchFiles($refTable, $refUid, $refField);
        foreach ($files as $uid => $media) {
            $ret[] = self::convertFal2StdClass($media->getProperty(), $configurations, $confId, $picCfg, $fields);
        }
        return $ret;
    }

    public static function convertFal2StdClass($record, $configurations, $confId, $picCfg, $fields = array())
    {
        if (empty($fields))
            $fields = [
                'uid',
                'title',
                'file_hash',
                'tstamp'
            ];
        $data = new stdClass();
        $filepath = $record['file_path'] . $record['file_name'];
        $data->filepath = $filepath;
        $server = Misc::getIndpEnv('TYPO3_SITE_URL');
        $data->absFilepath = $server . $filepath;
        $record['file'] = $filepath;
        // Bild skalieren
        $cObj = $configurations->getCObj(1);
        $cObj->data = $record;
        foreach ($picCfg as $picName) {
            $data->$picName = $cObj->cObjGetSingle($configurations->get($confId . $picName), $configurations->get($confId . $picName . '.'));
            if ($data->$picName) {
                $data->$picName = $server . $data->$picName;
            }
        }
        foreach ($fields as $fieldName) {
            $data->$fieldName = $record[$fieldName];
        }
        return $data;
    }
}

