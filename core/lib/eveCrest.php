<?php
/**
 * Copyright © 2016 RZN
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
/**
 * Created for CoPilot.
 * User: RZN
 * Date: 2/28/2016
 * Time: 03:34
 */


namespace CoPilot {

    use \GuzzleHttp\Client;

    class eveCrest
    {
        private static $instance;

        private $guzzle;
        private $base_url;
        public $endPoints = array();
        private $stash;

        private function __construct()
        {
            $this->base_url = 'https://public-crest.eveonline.com/';
            $this->guzzle = new Client([
                'base_uri' => $this->base_url,
                'defaults' => [
                        'verify' => APPROOT . 'cacert.pem'
                ]
            ]);

            $this->stash = Session::singleton()->stash;

            //cache the crest root
            $this->endPoints = $this->getEndPoints();
        }

        public static function singleton()
        {
            if (!isset(self::$instance))
            {
                $className = __CLASS__;
                self::$instance = new $className;
            }
            return self::$instance;
        }

        /** Caches the crest root endpoint
         * @return array
         * @throws \Exception
         */
        private function getEndPoints()
        {
            $item = $this->stash->getItem('crest_' . 'base');
            $data = $item->get();
            if (!$item->isHit())
            {
                $response = $this->guzzle->get('/');
                if($response->getStatusCode() != 200)
                {
                    throw new \Exception('Error querying crest ' . $response->getReasonPhrase());
                }

                $data = json_decode($response->getBody());

                //pre cached static
                /* This was fun but pointless, it pre-caches and fixes the arrays for any endpoint in $staticCache
                It would allow $this->endPoints->itemGroups->items[1] and so on.
                foreach(self::$staticCache as $endpoint)
                {
                    $url = $data->$endpoint->href;
                    $response =  $this->get($url, 17000);
                    $data->$endpoint->items = $this->fixArrayBS($url, $response->items);
                    while(isset($response->next))
                    {
                        $response = $this->get($response->next->href, 17000);
                        $data->$endpoint->items += $this->fixArrayBS($url, $response->items);
                    }
                }*/
                $this->stash->save($item->set($data)->expiresAfter(3600*24*30));
            }

            return $data;
        }

        private function makeCacheName($name)
        {
            return 'crest_' . str_replace('/', '_', str_replace($this->base_url, '', $name));
        }

        /**.
         * @param string $url Crest endpoint to get
         * @param int $cacheTTL cache expiry timestamp
         * @param array $params optional request params
         * @param bool $noCache Disable caching
         * @return array
         */
        public function get($url, $cacheTTL = 3600*24*30, $params = array(), $noCache = false)
        {
            $fileName = $this->makeCacheName($url);
            $item = $this->stash->getItem($fileName);
            $data = $item->get();
            if(!$item->isHit() || $noCache)
            {
                $response = $this->guzzle->get($url, $params);
                $data = json_decode($response->getBody());
                if($cacheTTL > 0)
                    $this->stash->save($item->set($data)->expiresAfter($cacheTTL));
            }

            return $data;
        }

        public function post($url, $params = array())
        {
            $response = $this->guzzle->post($url, $params);
            //TODO: error checking!
            return json_decode($response->getBody());
        }

        /**
         * @param int $id groupTypeID
         * @return int itemTypeID
         */
        public function getItemTypes($id)
        {
            //cache these in the $endpoints array to reduce loading from file cache.
            if(isset($this->endPoints->itemTypes->data) && isset($this->endPoints->itemTypes->data[$id]))
            {
                return $this->endPoints->itemTypes->data[$id];
            }
            else
            {
                $type = $this->get($this->endPoints->itemTypes->href . $id .'/');
                $this->endPoints->itemTypes->data[$id] = $type;
                return $type;
            }
        }

        /**
         * used to get an array of type ids for a given group
         * @param int $group  groupTypeIDs
         * @return array containing itemTypeIDs
         */
        public function getTypeIDsGroup($group)
        {
            $item = $this->stash->getItem('crest_groupIDS_' . $group);
            $data = $item->get();
            if(!$item->isHit())
            {
                $group = $this->get($this->endPoints->itemGroups->href.$group.'/');
                $data = array();
                foreach($group->types as $types)
                {
                    $data[count($data)] = $this->get($types->href, 17000)->id;
                }
                $this->stash->save($item->set($data)->expiresAfter(3600*24*30));
            }
            return $data;
        }

        /**
         * @param array $groups array of groupTypeIDs
         * @return array Of ItemTypeIDs
         */
        public function getTypeIDsGroups($groups = array())
        {
            $result = array();
            foreach($groups as $value)
                $result += $this->getTypeIDsGroup($value);
            return $result;
        }


        /**
         * turns array(item,item,etc) into array(itemid => item, itemid => item, etc)
         * It assumes a lot, works on groups and itemtypes
         * @param string $endPoint The endpoint url, used to parse ids from href
         * @param array $items
         * @return array
         */
        public function fixArrayBS($endPoint, $items = array())
        {
            $result = array();
            for($i = 0; $i < count($items); $i++)
            {
                $itemID = str_replace('/', '', str_replace($endPoint, '', $items[$i]->href));
                $result[$itemID] = $items[$i];
            }
            return $result;
        }
    }
}