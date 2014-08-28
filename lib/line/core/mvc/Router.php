<?php

/*
 *  LinePHP Framework ( http://linephp.com )
 *  
 *                                 THE LICENSE
 * ==========================================================================
 * Copyright (c) 2014 LinePHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ==========================================================================
 */

namespace line\core\mvc;

use line\core\request\Request;
use line\core\response\Response;

/**
 * 
 * @class Router
 * @link  http://linephp.com
 * @author Alivop[alivop.liu@gmail.com]
 * @since 1.0
 * @package line\core\mvc
 */
class Router extends BaseMVC
{

    private $request;
    public static $LINE_CORE;
    public static $LINE_IO;
    public static $LINE_DB;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->initUserLine();
    }

    public function dispatcher()
    {
        $controller = new Controller($this->request);
        $url = $this->request->url;
//        if (isset($url)) {
        $status = $controller->callDefinedController($url);
//        } else {
//            $status = $controller->callDefaultController();
//        }
        if (is_array($status)) {
            $response = new Response($status[1], $status[0]);
            $response->render();
        }
    }

    private function initUserLine()
    {
        self::$LINE_CORE = array('view', 'page', 'data', 'arraylist', 'set', 'map', 'request', 'stringutil');
        self::$LINE_IO = array('file');
        self::$LINE_DB = array('dbfactory', 'result', 'statement');
    }

}