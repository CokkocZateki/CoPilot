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
 * Date: 2/21/2016
 * Time: 22:41
 */
    require_once APPROOT . 'controllerbase.php';
    require_once APPROOT . 'lib/dotlanLib.php';
    require_once APPROOT . 'lib/zkillLib.php';
    require_once APPROOT . 'lib/eveCrest.php';
    require_once APPROOT . 'lib/eveCentralLib.php';


    class core implements CoPilot\iController
    {
        private $context = null;
        private $zkill = null;
        private $dotlan = null;
        private $crest = null;


        public function __construct()
        {
            $this->context = ['page' => ['title' => 'CoPilot']];
            $this->zkill = new \CoPilot\zkillLib();
            $this->dotlan = new \CoPilot\dotlanLib();
            $this->crest = CoPilot\eveCrest::singleton();

        }

        public function __toString()
        {
            return __CLASS__;
        }

        public function getContext()
        {
            return $this->context;

        }

        public function index($typeID = null)
        {
            $readme = file_get_contents(dirname(APPROOT) . DIRECTORY_SEPARATOR . 'readme.md');
            $parsedown = new Parsedown();
             $this->context['doc'] = $parsedown->parse($readme);
        }

        public function character($id)
        {
            $this->context = ["id" => $id];
        }


        public function corporation($id)
        {
            $this->context = ["id" => $id];
        }

        public function alliance($id)
        {
            $this->context = ["id" => $id];
        }

        public function ship($id)
        {
            $this->context = ["id" => $id];
        }

        public function system($systemID)
        {
            if (empty($systemID))
                return;
            //stick this here so after login we can return
            \CoPilot\Session::singleton()->location = "core/system/" . $systemID;

            //parse intel stuffs
            $this->context['zkill'] = $this->zkill->parseKills($this->zkill->getSystemKills($systemID));
            //system stats
            $this->context['zkill']['stats'] = $this->zkill->getSystemStats($systemID);
            $this->context['dotlan'] = $this->dotlan->getSystem($systemID);
            //last 50 kills
            $this->context['zkill']['kills'] = array_slice($this->context['zkill']['kills'], 0, 50);


            //Get trade hub routes only if in known space
            if( $this->context['zkill']['stats']['info']['solarSystemSecurity'] > 0) {
                $systems = array(30000142, 30002187, 30002659, 30002053);//jita, amarr, dodixie, hek
                $this->context['route'] = array();

                foreach($systems as $system)
                {
                    if( $system != $systemID)//Skip the current system
                        array_push( $this->context['route'], \CoPilot\eveCentralLib::singleton()->getRoute($systemID, $system));
                }
            }

        }

        public function region($id)
        {
            $this->context = ["id" => $id];
        }
    }

