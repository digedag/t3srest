<?php

namespace System25\T3srest\Provider;

use DMK\T3rest\Legacy\Exception\DataNotFoundException;
use DMK\T3rest\Legacy\Model\ProviderModel;
use DMK\T3rest\Legacy\Provider\IProvider;
use Sys25\RnBase\Frontend\Request\Request;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
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
 * This is a sample REST provider for tt_news.
 *
 * @author Rene Nitzsche
 */
abstract class AbstractBase implements IProvider
{
    public function execute(ProviderModel $provData)
    {
        $configurations = $provData->getConfigurations();
        $confId = $this->getConfId();
        $data = $this->handleRequest($configurations, $confId);
        if (false === $data) {
            $data = ['unsupported' => 1];
        }

        return $data;
    }

    /**
     * Lädt einen einzelnen Datensatz. Erwartet wird entweder die UID oder ein
     * Identifier. Letzterer muss dann in der Config als Filter konfiguriert sein.
     *
     * @param mixed $itemUid int oder string-Identifier
     * @param \Sys25\RnBase\Configuration\ConfigurationInterface $configurations
     * @param string $confId wird bei defined angepaßt
     *
     * @return \Sys25\RnBase\Domain\Model\BaseModel
     */
    public function getItem($itemUid, $configurations, &$confId, $searchCallback)
    {
        if (intval($itemUid)) {
            $item = tx_rnbase::makeInstance($this->getBaseClass(), intval($itemUid));
        } else {
            // Prüfen, ob der Dienst konfiguriert ist
            $defined = $configurations->getKeyNames($confId.'defined.');
            if (in_array($itemUid, $defined)) {
                $confId = $confId.'defined.'.$itemUid.'.';
                $request = new Request($configurations->getParameters(), $configurations, $confId);

                // Item per Config laden
                $filter = BaseFilter::createFilter($request, $confId . 'filter.');
                $fields = [];
                $options = [];
                //suche initialisieren
                $filter->init($fields, $options);
                $options['forcewrapper'] = 1;
                $options['limit'] = 1;
                $items = call_user_func($searchCallback, $fields, $options);
                $item = !empty($items) ? $items[0] : null;
            }
        }

        if (!$item || !$item->isValid()) {
            throw tx_rnbase::makeInstance(DataNotFoundException::class, 'Item not valid', 100);
        }

        return $item;
    }

    abstract protected function handleRequest($configurations, $confId);

    abstract protected function getConfId();

    abstract protected function getBaseClass();
}
