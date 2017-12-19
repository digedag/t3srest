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
tx_rnbase::load('tx_t3rest_models_Provider');
tx_rnbase::load('tx_t3rest_provider_AbstractBase');
tx_rnbase::load('tx_t3rest_util_Objects');

/**
 * This is a sample REST provider for tt_news
 *
 * @author Rene Nitzsche
 */
class tx_t3srest_provider_Matches extends tx_t3rest_provider_AbstractBase
{

    protected function handleRequest($configurations, $confId)
    {
        if ($itemUid = $configurations->getParameters()->get('get')) {
            $confId = $confId . 'get.';
            $item = $this->getItem($itemUid, $configurations, $confId, array(
                tx_cfcleague_util_ServiceRegistry::getMatchService(),
                'search'
            ));
            $decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Match');
            $data = $decorator->prepareItem($item, $configurations, $confId);
        } elseif ($searchType = $configurations->getParameters()->get('search')) {
            $confId = $confId . 'search.';
            $data = $this->getItems($searchType, $configurations, $confId, array(
                tx_cfcleague_util_ServiceRegistry::getMatchService(),
                'search'
            ));
        }
        return $data;
    }

    protected function getItems($searchType, $configurations, $confId, $searchCallback)
    {
        tx_rnbase::load('tx_rnbase_filter_BaseFilter');
        $filter = tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewdata, $confId . 'defined.' . $searchType . '.filter.');
        $fields = array();
        $options = array();
        $filter->init($fields, $options, $parameters, $configurations, $this->getConfId());
        $prov = tx_rnbase::makeInstance('tx_rnbase_util_ListProvider');
        $prov->initBySearch($searchCallback, $fields, $options);
        
        $this->configurations = $configurations;
        $this->confId = $confId;
        $this->decorator = tx_rnbase::makeInstance('tx_t3srest_decorator_Match');
        $prov->iterateAll(array(
            $this,
            'loadItem'
        ));
        
        return $this->items;
    }

    public function loadItem($item)
    {
        //
        $data = $this->decorator->prepareItem($item, $this->configurations, $this->confId);
        $this->items[] = $data;
    }

    protected function getBaseClass()
    {
        return 'tx_cfcleague_models_Match';
    }

    protected function getConfId()
    {
        return 'match.';
    }
}
