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
 * Date: 2/23/2016
 * Time: 03:40
 */

namespace CoPilot
{
    require_once APPROOT . 'lib/fuzzLib.php';
    require_once APPROOT . 'lib/eveCrest.php';

    class zkillLib
    {
        private $url;
        private $stash;

        public function __construct()
        {
            $this->url = 'https://zkillboard.com/api/';
            $this->stash = Session::singleton()->stash;

        }

        public function getStats($path, $id, $cacheTime = 3600)
        {
            $requestUrl = sprintf("%s%s%s", $this->url, $path, $id ? strval($id).'/':'');

            $cacheKey = 'zkill_' . str_replace('/', '_', $path) . $id;
            $item = $this->stash->getItem($cacheKey);
            $data = $item->get();
            if(!$item->isHit())
            {
                $guzzle = new \GuzzleHttp\Client();
                $response = $guzzle->get($requestUrl);
                if($response->getStatusCode() != 200)
                {
                    throw new \Exception('Error querying zkill ' . $response->getReasonPhrase());
                }
                $data = json_decode($response->getBody(), true);
                $item->set($data);
                $item->expiresAfter($cacheTime);
                $this->stash->save($item);
            }
            return $data;
        }

        /**
         * Gets the last 200 kills for a given systemID
         * @param $systemID
         * @return array
         * @throws \Exception
         */
        public function getSystemKills($systemID)
        {
            return $this->getStats('limit/200/no-items/solarSystemID/', $systemID);
        }

        /**
         * Get stats for a given systemID
         * @param $systemID
         * @return array
         * @throws \Exception
         */
        public function getSystemStats($systemID)
        {
            //little clean up to make access easier for h2o
            $data = $this->getStats('stats/solarSystemID/', $systemID);
            unset($data['allTimeSum']);
            unset($data['months']);
            unset($data['groups']);
            foreach($data['topAllTime'] as $key => $value)
            {
                $data['top'][$value['type']] = $value['data'];
            }
            $data['info'] += $data['top']['system'][0];
            unset($data['topAllTime']);
            unset($data['top']['system']);
            return $data;
        }

        public function parseKills($killData)
        {
            //Arrays of typeIDs for matching
            //all control towers
            $posTypeIDs = eveCrest::singleton()->getTypeIDsGroup(365);
            //not currently using these
            //$posGunTypeIDs = eveCrest::singleton()->getTypeIDsGroups([417, 426, 430, 449]);
            //$posModTypeIDs = eveCrest::singleton()->getTypeIDsGroups([411, 363, 397, 404, 413, 438, 439, 440, 441, 443, 444, 471, 837, 1106]);
            //Carrier 547 Dreadnought 485
            $capTypeIDs = eveCrest::singleton()->getTypeIDsGroup(547) + eveCrest::singleton()->getTypeIDsGroup(485);
            //Sentry guns and concord
            $securityTypeIDs = eveCrest::singleton()->getTypeIDsGroups([99, 301]);


            $corps =[];
            $chars = [];
            $concord = [];
            $caps = [];
            $pos = [];

            //time limit
            $CutOffTime = (time() - (3600 * 24 * 30)); //30days ago (ish)

            foreach( $killData as $key => $kill)
            {
                //add time stamp while we're here
                $killTimestamp = \DateTime::createFromFormat('Y-m-d H:i:s', $kill['killTime'])->getTimestamp();
                $killData[$key]['killTimestamp'] = $killTimestamp;

                //add location name
                $locationID = null;
                $locationName = 'Unknown';

                //figure out where this went down
                if( isset($kill['zkb']['locationID']) )
                {
                    //new kills normally have a locationID
                    $locationID = $kill['zkb']['locationID'];
                    //use fuzzworks to look up the location in mapDenormalize so we don't have to have a db
                    $result = fuzzLib::singleton()->getLocation($locationID)[0];
                    //stargates do not have an itemName
                    if(isset($result->itemname))
                        $locationName = $result->itemname;
                    else
                        $locationName = $result->typename;

                    //add the location to the original kill data
                    $killData[$key]['locationName'] = $locationName;
                }

                //stamp victim
                $kill['victim']['locationID'] = $locationID;
                $kill['victim']['locationName'] = $locationName;
                $kill['victim']['killTimestamp'] = $killTimestamp;

                //yes it happens
                if($kill['victim']['corporationID'] > 0 && $killTimestamp >= $CutOffTime) {
                    array_push($corps, $kill['victim']);
                }

                //Towers, sentries etc do not have a character
                if($kill['victim']['characterID'] > 0 && $killTimestamp >= $CutOffTime) {
                    array_push($chars, $kill['victim']);
                }

                //go through the attackers the same way
                foreach($kill['attackers'] as $attacker)
                {
                    //stamp attackers
                    $attacker['locationID'] = $locationID;
                    $attacker['locationName'] = $locationName;
                    $attacker['killTimestamp'] = $killTimestamp;

                    //stats
                    if ($attacker['corporationID'] > 0 && $killTimestamp >= $CutOffTime){
                        array_push($corps, $attacker);
                    }
                    if($attacker['characterID'] > 0 && $killTimestamp >= $CutOffTime){
                        array_push($chars, $attacker);
                    }

                    //if this was a pos doing the shooting save it
                    if(in_array($attacker['shipTypeID'], $posTypeIDs))//pos kills
                    {
                        array_push($pos, $attacker);
                    }
                    // save anyone flying a cap
                    else if(in_array($attacker['shipTypeID'], $capTypeIDs))//cap kills
                    {
                        $attacker['killID'] = $kill['killID'];
                        array_push($caps, $attacker);
                    }

                    //Adds anyone killed by concord or sentry guns with in the time limit
                    if($killTimestamp >= $CutOffTime &&
                      (in_array($attacker['shipTypeID'], $securityTypeIDs) || $attacker['corporationID'] == 1000125))//concord
                    {
                        array_push($concord, $kill['victim']);
                    }
                }
            }
            //wrap everything up in a friendly array to send back but not before filtering a few things
            return ['kills' => $killData,
                     'intel' => [ 'pos' => $this->arrayunique($pos, 'locationID'), //filter dupe locations
                                  'caps' => $this->arrayunique($caps, 'characterID'), //filter dupe chars
                                  'chars' => $this->arrayunique($chars, 'characterID'), //filter dupe chars
                                  'corps' => $this->arrayunique($corps, 'corporationID'), //same shit
                                  'concord' => $this->arrayunique($concord, 'characterID')]]; //don't need to know doodman got concorded 10 times today
        }


        private function arrayunique($array, $key)
        {
            $result = array();
            $count = 0;
            $key_temp = array();

            foreach($array as $value)
            {
                if(!in_array($value[$key], $key_temp))
                {
                    $key_temp[$count] = $value[$key];
                    $result[$count] = $value;
                }
                $count++;
            }
            return $result;
        }

    }
}