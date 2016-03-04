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
 * Time: 04:15
 */
require_once APPROOT . 'controllerbase.php';
require_once APPROOT . 'lib/eveCrest.php';
require_once APPROOT . 'config.php';

use \GuzzleHttp\Client;

class sso implements CoPilot\iController
{
    private $context = null;
    private $crest;
    private $session;

    public function __construct()
    {
        $this->context = ['page' => ['title' => 'CoPilot']];
        $this->crest = CoPilot\eveCrest::singleton();
        $this->session = \CoPilot\Session::singleton();
    }


    public function __toString()
    {
        return __CLASS__;
    }

    public function getContext()
    {
        return $this->context['session'] = $this->session->CrestData;

    }

    public function index()
    {
        return $this->context;
    }

    public function logout()
    {
        $this->session->destroy();
        $this->session = \CoPilot\Session::singleton();

        header('Location: '. Configuration::baseURL . $this->session->location);
    }

    public function login()
    {
        $url = 'https://login.eveonline.com/oauth/authorize/?response_type=code';
        $url = $url . '&redirect_uri=' . Configuration::callbackURL;
        $url = $url . '&client_id=' . Configuration::clientID;
        $url = $url . '&scope='. Configuration::scopes;
        $url = $url . '&state=' . $this->session->sessionID;
        header('Location: ' . $url);
    }

    public function sso()
    {
        $sso_state = $_GET['state'];
        $sso_code = $_GET['code'];

        if($sso_state != $this->session->sessionID)
        {
            //TODO: logging and better bitching
            throw new Exception('Session Mismatch in sso/sso/');
        }

        $config['headers'] = array( "Authorization" => "Basic ".base64_encode(Configuration::clientID.":".Configuration::secretKey));
        $config['query'] =   array("grant_type" => "authorization_code", "code" => $sso_code);

        $basic = \CoPilot\eveCrest::singleton()->post(\CoPilot\eveCrest::singleton()->endPoints->authEndpoint->href, $config);

        unset($config);
        $config['headers'] = array("Authorization" => "Bearer ". $basic->access_token);

        $bearer = \CoPilot\eveCrest::singleton()->get('https://login.eveonline.com/oauth/verify', 0, $config, true);

        $crestData = ['access_token' => $basic->access_token,
                      'expires_in' => time() + $basic->expires_in - 20,
                      'refresh_token' => $basic->refresh_token,
                      'characterID' => $bearer->CharacterID,
                      'characterName' => $bearer->CharacterName];

        $this->session->CrestData = json_encode($crestData);

        header('Location: '. Configuration::baseURL . $this->session->location);
    }
}