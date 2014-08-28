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
/**
 * 
 * @class Request
 * @link  http://linephp.com
 * @author Alivop[alivop.liu@gmail.com]
 * @since 1.0
 * @package 
 */
abstract class Request
{
    abstract public function parameter($name);
    abstract public function get($name);
    abstract public function post($name);
    abstract public function getServer();
    abstract public function getRemote();
    abstract public function getBrowser();
    abstract public function server($var);
    abstract public function remote($var);
    abstract public function browser($var);
    abstract public function getUploadFile($name);
}