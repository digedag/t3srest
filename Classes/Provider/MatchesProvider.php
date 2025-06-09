<?php

namespace System25\T3srest\Provider;

use System25\T3sports\Utility\ServiceRegistry;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Configuration\ConfigurationInterface;
use Sys25\RnBase\Frontend\Request\Request;
use Sys25\RnBase\Frontend\Marker\ListProvider;
use System25\T3sports\Model\Fixture;
use System25\T3srest\Decorator\MatchDecorator;
use Throwable;
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
 *
 * @author Rene Nitzsche
 */
class MatchesProvider extends AbstractBase
{
    private $matchDecorator;

    public function __construct(MatchDecorator $matchDecorator)
    {
        $this->matchDecorator = $matchDecorator;
    }

    protected function handleRequest($configurations, $confId)
    {
        if ($itemUid = $configurations->getParameters()->get('get')) {
            $confId = $confId . 'get.';
            $item = $this->getItem($itemUid, $configurations, $confId, [
                ServiceRegistry::getMatchService(),
                'search'
            ]);
            $decorator = $this->matchDecorator;
            try {
                $data = $decorator->prepareItem($item, $configurations, $confId);
            } catch (Throwable $e) {
                echo 'FEHLER: ' . $e->getMessage() . '<br />';
            }
        } elseif ($searchType = $configurations->getParameters()->get('search')) {
            $confId = $confId . 'search.';
            $data = $this->getItems($searchType, $configurations, $confId, array(
                ServiceRegistry::getMatchService(),
                'search'
            ));
        }
        return $data;
    }

    protected function getItems($searchType, ConfigurationInterface $configurations, $confId, $searchCallback)
    {
        $request = new Request($configurations->getParameters(), $configurations, $confId);
        $filter = BaseFilter::createFilter($request, $confId . 'defined.' . $searchType . '.filter.');
        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        $prov = tx_rnbase::makeInstance(ListProvider::class);
        $prov->initBySearch($searchCallback, $fields, $options);

        $decorator = tx_rnbase::makeInstance(MatchDecorator::class);
        $items = [];
        $prov->iterateAll([
            $this,
            function ($item) use (&$items, $decorator, $configurations, $confId) {
                $data = $decorator->prepareItem($item, $configurations, $confId);
                $items[] = $data;
            }
        ]);

        return $items;
    }

    protected function getBaseClass()
    {
        return Fixture::class;
    }

    protected function getConfId()
    {
        return 'match.';
    }
}
