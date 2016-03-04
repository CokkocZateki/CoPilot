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
 * Date: 2/29/2016
 * Time: 22:26
 */

namespace CoPilot
{

    use \GuzzleHttp\Client;

    class fuzzLib
    {
        private static $instance;
        private $guzzle;
        private $stash;
        private function __construct()
        {
            $this->guzzle =  new Client();
            $this->stash = Session::singleton()->stash;
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

        /**
         * Uses fuzzworks to query the mapDenormalize table
         * @param $locationID itemID from mapDenormalize
         * @return array
         * @throws \Exception
         */
        public function getLocation($locationID)
        {
            $item = $this->stash->getItem('central_' . strval($locationID));
            $data = $item->get();
            if(!$item->isHit())
            {
                $url = sprintf('https://www.fuzzwork.co.uk/api/mapdata.php?itemid=%s&format=json',$locationID);
                $response = $this->guzzle->get($url);
                if($response->getStatusCode() == 200)
                {
                    $data = json_decode($response->getBody());
                    $this->stash->save($item->set($data)->expiresAfter(3600*24*30));
                }
                else
                {
                    throw new \Exception('Error querying www.fuzzwork.co.uk ' . $response->getReasonPhrase());
                }
            }
            return $data;
        }
    }
}