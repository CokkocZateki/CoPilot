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
 * Date: 2/24/2016
 * Time: 00:39
 */

namespace CoPilot
{
    use \GuzzleHttp\Client;
    class dotlanLib
    {
        private $baseUrl;
        private $stash;

        public function __construct()
        {
            $this->baseUrl = 'http://evemaps.dotlan.net/';
            $this->stash = Session::singleton()->stash;
        }

        public function getStats($path, $id)
        {
            $client = new Client();
            $requestUrl = sprintf("%s%s%s", $this->baseUrl, $path, $id ? strval($id):'');

            $response = $client->get($requestUrl);

            return $response->getBody();
        }


        public function getSystem($id)
        {
            /** make sure we don't already have this cached */
            $item = $this->stash->getItem('dotlan' . 'stats_system_' . $id);
            $data = $item->get();

            if(!$item->isHit())
            {
                //not cached so rebuild
                //get the page's html
                $html = $this->getStats('system/', $id);

                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = false;
                libxml_use_internal_errors(true);
                $dom->loadHTML($html);

                /** Stolen from wormhole.es, thank you */
                $nodes = $dom->getElementsByTagName('td');
                for ($i = 0; $i < $nodes->length; $i++) {
                    $td = $nodes->item($i);
                    if (stristr($td->nodeValue, "Jumps 1h/24h")) {
                        // Node - Jumps 1h/24h
                        $data["Jumps_1hr"] = is_numeric($nodes->item($i + 1)->nodeValue) ? (int)$nodes->item($i + 1)->nodeValue : -1;
                        $data["Jumps_24hr"] = is_numeric($nodes->item($i + 2)->nodeValue) ? (int)$nodes->item($i + 2)->nodeValue : -1;
                    } elseif (stristr($td->nodeValue, "Ship Kills")) {
                        // Node - Ship Kills 1h/24h
                        $data["ShipKills_1hr"] = is_numeric($nodes->item($i + 1)->nodeValue) ? (int)$nodes->item($i + 1)->nodeValue : -1;
                        $data["ShipKills_24hr"] = is_numeric($nodes->item($i + 2)->nodeValue) ? (int)$nodes->item($i + 2)->nodeValue : -1;
                    } elseif (stristr($td->nodeValue, "NPC Kills")) {
                        // Node - NPC Kills 1h/24h
                        $data["NPCKills_1hr"] = is_numeric($nodes->item($i + 1)->nodeValue) ? (int)$nodes->item($i + 1)->nodeValue : -1;
                        $data["NPCKills_24hr"] = is_numeric($nodes->item($i + 2)->nodeValue) ? (int)$nodes->item($i + 2)->nodeValue : -1;
                    } elseif (stristr($td->nodeValue, "Pod Kills")) {
                        // Node - Pod Kills 1h/24h
                        $data["PodKills_1hr"] = is_numeric($nodes->item($i + 1)->nodeValue) ? (int)$nodes->item($i + 1)->nodeValue : -1;
                        $data["PodKills_24hr"] = is_numeric($nodes->item($i + 2)->nodeValue) ? (int)$nodes->item($i + 2)->nodeValue : -1;
                    }
                }
                $item->set($data);
                $item->expiresAfter(3600);
                $this->stash->save($item);
            }
            return $data;
        }
    }
}