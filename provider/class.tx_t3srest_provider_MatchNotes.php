<?php
use System25\T3sports\Utility\ServiceRegistry;
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\MatchNote;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2023 Rene Nitzsche
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
 * This is a sample REST provider for MatchNotes.
 * Als Parameter wird ein Spiel und Optional eine Zeitangabe
 * erwartet.
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_provider_MatchNotes extends tx_t3srest_provider_AbstractBase
{

    protected function handleRequest($configurations, $confId)
    {
        if ($itemUid = $configurations->getParameters()->get('get')) {
            $confId = $confId . 'get.';
            $item = $this->getItem($itemUid, $configurations, $confId, [
                ServiceRegistry::getMatchService(),
                'search'
            ]);
            // Zu dem Spiel werden nun die eigentlichen MatchNotes geladen
            // Wir holen immer alle Notes, weil die Daten korrekt aufgebaut werden müssen
            $tickerUtil = new MatchTicker();
            $matchNotes = & $tickerUtil->getTicker4Match($item);

            if ($configurations->get($confId . 'sorting') != 'asc') {
                $matchNotes = array_reverse($matchNotes);
            }
            $data = [];
            $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_MatchNote');
            $minMinute = $configurations->getParameters()->getInt('minute');
            foreach ($matchNotes as $note) {
                /* @var $note MatchNote */
                if (intval($note->getProperty('minute')) < $minMinute) {
                    continue;
                }
                $note->unsProperty('match');
                $data[] = $decorator->prepareItem($note, $configurations, $confId);
            }
        }
        return $data;
    }

    public function loadItem($item)
    {
        //
        $data = $this->decorator->prepareItem($item, $this->configurations, $this->confId);
        $this->items[] = $data;
    }

    protected function getBaseClass()
    {
        return Fixture::class;
    }

    protected function getConfId()
    {
        return 'matchnote.';
    }
}
