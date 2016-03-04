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
 * Date: 2/18/2016
 * Time: 23:57
 */


namespace CoPilot
{
    require_once APPROOT . 'lib/eveCrest.php';
    require_once APPROOT . 'controllerbase.php';
    require_once APPROOT . 'lib/h2o.php';
    require_once APPROOT . 'config.php';
    require_once APPROOT . 'lib/h2oFilters.php';

    /**
     * Class Controller
     * @package CoPilot
     */
    class Controller
    {
        /** @var null The requested Controller Class */
        private $controllerClass = null;

        /** @var null The method of the above requested controller */
        private $controllerAction = null;

        /** @var null Parameters to pass to the above method from the url*/
        private $controllerParams = null;

        private $acceptJson = false;


        /**
         * Controller constructor.
         */
        public function __construct()
        {
            $this->acceptJson = (explode(',', $_SERVER['HTTP_ACCEPT'])[0] == 'application/json' ||
                                 explode(',', $_SERVER['CONTENT_TYPE'])[0] == 'application/json') ? true:false;

            /** Load the session */
            require APPROOT . 'session.php';

            /** TODO: Session and ip matching */

            /** Split the requested url into controllerClass, Action and Params */
            $this->splitUrl();

            /** Check that the requested controller exists*/
            if (!file_exists(APPROOT . 'modules/' . $this->controllerClass . '.php'))
            {
                /** send 404 */
                header("HTTP/1.0 404 Not Found");
                exit();
            }

            //require and load the requested controller
            require APPROOT . 'modules/' . $this->controllerClass . '.php';

            $this->controllerClass = new $this->controllerClass();

            //make sure the requested action is valid
            if (method_exists($this->controllerClass, $this->controllerAction))
            {
                if (!empty($this->controllerParams))
                {
                    $this->controllerClass->{$this->controllerAction}($this->controllerParams);
                    /*call_user_func_array(array($this->controllerClass, $this->controllerAction),
                                         $this->controllerParams);*/
                }
                else
                {
                    $this->controllerClass->{$this->controllerAction}();
                }
            }
            else
            {
                /** controllerAction is not a member of the class so force index */
                $this->controllerParams = $this->controllerAction;
                $this->controllerAction = 'index'; //insures the template gets loaded
                if (strlen($this->controllerParams) == 0)
                {
                    $this->controllerClass->index();
                }
                else
                {
                    $this->controllerClass->index($this->controllerParams);
                }
            }

            //auto load the template based on the requested controller
            $template = APPROOT . 'templates/' . $this->controllerClass . '.' . $this->controllerAction . '.html';

            if (file_exists($template))
            {
                /** load the template */
                $h2o = H2o($template);

                $context = $this->controllerClass->getContext();

                echo $h2o->render($context);
            }
            else
            {
                /** json functions do not have templates */
                header('Content-Type: application/json');
                echo json_encode($this->controllerClass->getContext());
            }

        }

        private function splitUrl()
        {
            if (isset($_GET['url']))
            {
                $url = trim($_GET['url'], '/');
                $url = filter_var($url, FILTER_SANITIZE_URL);
                $url = explode('/', $url);
            }
            else
            {
                $url = array();
            }

            $this->controllerClass = isset($url[0]) ? $url[0] : 'core';
            $this->controllerAction = isset($url[1]) ? $url[1] : 'index';
            $this->controllerParams = isset($url[2]) ? $url[2] : '';
        }

    }
}