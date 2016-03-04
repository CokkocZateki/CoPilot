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
 * Date: 2/19/2016
 * Time: 12:15
 */
namespace CoPilot
{

    use Stash\Driver\FileSystem;
    use Stash\Pool;
    use Stash\Drivers;

    class Session
    {
        private static $instance;

        public $sessionID = '';
        public $stash = null;

        /**
         * Session constructor.
         */
        private function __construct()
        {
            if (ini_get('session.auto_start') ==0)
            {
                session_start();
            }
            $this->sessionID = session_id();
            $driver = new FileSystem(array('path' => APPROOT . 'cache/'));
            $this->stash = new Pool($driver);
        }

        /**
         *
         */
        public function __destruct()
        {
            session_write_close();
        }

        /**
         * @return self
         */
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
         *
         */
        public function destroy()
        {
            session_unset();
            session_destroy();
            self::$instance = null;
        }

        /**
         *
         */
        public function __clone()
        {
            trigger_error('Can not clone ' . __CLASS__, E_USER_ERROR);
        }

        /**
         * @param $name
         * @return mixed
         */
        public function __get($name)
        {
            if (!isset($_SESSION[$name]))
            {
                $_SESSION[$name] = null;
            }
            return $_SESSION[$name];
        }

        /**
         * @param $name
         * @param $value
         * @return mixed
         */
        public function __set($name, $value)
        {
            return ($_SESSION[$name] = $value);
        }

        /**
         * @param $name
         * @return bool
         */
        public function __isset($name)
        {
            return isset($_SESSION[$name]) || isset($this->$name);
        }
    }
}?>