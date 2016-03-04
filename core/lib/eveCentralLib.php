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
 * Date: 3/3/2016
 * Time: 00:02
 */

namespace CoPilot {
    use \GuzzleHttp\Client;

    class eveCentralLib
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


        public function getRoute($startSystemID, $endSystemID)
        {
            if(!isset($startSystemID) || !isset($endSystemID))
            {
                return [];
            }

            $item = $this->stash->getItem('central_' . strval($startSystemID) . '_' . strval($endSystemID));
            $data = $item->get();
            if(!$item->isHit())
            {
                $url = sprintf('http://api.eve-central.com/api/route/from/%s/to/%s',$startSystemID, $endSystemID);
                $response = $this->guzzle->get($url);
                if($response->getStatusCode() == 200)
                {
                    $data = json_decode($response->getBody());
                    $this->stash->save($item->set($data)->expiresAfter(3600*24*30));
                }
                else
                {
                    throw new \Exception('Error querying http://api.eve-central.com ' . $response->getReasonPhrase());
                }
            }
            return $data;
        }
    }
}