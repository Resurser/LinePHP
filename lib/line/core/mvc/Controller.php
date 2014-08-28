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
use line\core\Config;
use line\core\exception\InvalidRequestException;
use line\core\exception\IllegalAccessException;
use line\core\util\Map;
use line\io\upload\Siglefile;
use line\io\upload\Multifile;

/**
 * 控制器。
 * @class Controller
 * @link  http://linephp.com
 * @author Alivop[alivop.liu@gmail.com]
 * @since 1.0
 * @package line\core\mvc
 */
class Controller extends BaseMVC
{
    //const PATH = 'controller';
    const INDEX = 'Index';
    const METHOD = 'main';

    private $request;
    private $application;
    private $pagePath;
    private $pageName;
    private $actionSize;
    private $classPrefix;
    private $classSuffix;
    private $methodPrefix;
    private $methodSuffix;
    private $parameterMap;
    private $oneParamter;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->application = Config::$LP_PATH[Config::PATH_APP] . LP_DS;
        $this->pagePath = Config::$LP_PATH[Config::PATH_PAGE] . LP_DS;
        $this->actionSize = 2;
        $this->classPrefix = Config::$LP_SYS[Config::SYS_CLASS_PREFIX];
        $this->classSuffix = Config::$LP_SYS[Config::SYS_CLASS_SUFFIX];
        $this->methodPrefix = Config::$LP_SYS[Config::SYS_METHOD_PREFIX];
        $this->methodSuffix = Config::$LP_SYS[Config::SYS_METHOD_SUFFIX];
        $this->parameterMap = new Map();
        $this->oneParamter = false;
        spl_autoload_unregister('line\core\Config::autoLoadClass');
        spl_autoload_register(array($this, 'autoLoadControllerClass'), true, true);
    }

    public function callDefaultController()
    {
        $index = $this->classPrefix . self::INDEX . $this->classSuffix;
        if (class_exists($index)) {
            $controller = new $index;
            $this->pageName = $index;// . self::EXT;
            return $this->runMethod($controller, $this->request->url ? substr($this->request->url, 1) : null); //explode('/',$this->request->url)[1]
        } else {
            return false;
        }
    }

    /**
     * 调用可用的控制器
     * 2014-05-14 添加对于多个/分隔不符合规则的URL的处理 
     * @param string $url
     * @return boolean
     * @throws InvalidRequestException
     */
    public function callDefinedController($url)
    {
        if (strcmp("/", $url) == 0)
            return $this->callDefaultController();
        $requestAction = explode('/', $url);
        $size = count($requestAction);
        if ($size > 1 && $size < 5) {
            $name = $requestAction[1];
            $controller = $this->matchController($name);
            $this->actionSize = $size;
            if (is_object($controller)) {
                if ($size == 2) {
                    $run = $this->runMethod($controller);
                } else {
                    $mixed = $requestAction[2];
                    //$parameter = $requestAction[3];
                    if ($size == 4)
                        $this->oneParamter = true;
                    $run = $this->runMethod($controller, $mixed);
                }
                return $run;
            } else if ($controller === false) {
                //return $this->callDefaultController();
                throw new InvalidRequestException(Config::$LP_LANG['bad_request'].":".$url); //2014-05-14
            }
        } else {
            throw new InvalidRequestException(Config::$LP_LANG['bad_request'].":".$url); //2014-05-14
        }
        return true;
    }

    /**
     * @deprecated since version 1.0
     * @param type $controller
     * @param type $parameter
     * @return boolean
     * @throws IllegalAccessException
     */
    private function callDefaultAction(&$controller, $parameter = null)
    {
        if (method_exists($controller, self::METHOD)) {
            $call = true;
            if (isset($parameter)) {
                $call = call_user_func(array($controller, self::METHOD), $parameter);
            } else {
                $post = $this->getPOSTParameter();
                $call = call_user_func(array($controller, self::METHOD), count($post) > 0 ? $post : null);
            }
            if ($call === false) {
                throw new IllegalAccessException(Config::$LP_LANG['method_no_access']);
            }
            return true;
        }
        return false;
    }

    private function matchController($name)
    {
        $classPart = explode('_', $name);
        $end = $this->classPrefix . end($classPart) . $this->classSuffix;
        $className = '';
        for ($i = 0; $i < count($classPart) - 1; $i++) {
            $className .= $classPart[$i] . '\\';
        }
        $className .= $end;
        try {
            $controller = new $className;
            $this->pageName = $className ;//. self::EXT;
            return $controller;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function matchAction(&$controller, $var = null)
    {
        if (isset($var) && $var != '') {
            if (method_exists($controller, $var)) {
                return true;
            }
            if ($this->actionSize === 4) {
                throw new InvalidRequestException(Config::$LP_LANG['bad_request']);
            } else {
                //$this->getAllGETParameter();
                return false;
            }
        } else {
            return false;
        }
    }

    private function callAction(&$controller, $method = null)
    {
        //$methodParamValues = array();
        if (empty($method)) {
            $method = self::METHOD;
        }
        //$this->getAllGETParameter();
        //$this->getAllPOSTParameter();
        $methodObject = new \ReflectionMethod($controller, $method);
        if ($methodObject->isAbstract() || $methodObject->isPrivate() || $methodObject->isProtected()) {
            throw new IllegalAccessException(Config::$LP_LANG['method_no_access']);
        }
        $methodParams = $methodObject->getParameters();
        $methodParamValues = $this->fillFormalParameter($methodParams);
        $methodReturn = $methodObject->invokeArgs($controller, $methodParamValues);
        if ($methodReturn instanceof View) {
            $rp = new \ReflectionProperty($methodReturn, 'dataMap');
            $rp->setAccessible(true);
            $dataMap = $rp->getValue($methodReturn);
            $rp = new \ReflectionProperty($methodReturn, 'name');
            $rp->setAccessible(true);
            $pageName = $rp->getValue($methodReturn);
//            $pageName = $methodReturn->name;
//            $dataMap = $methodReturn->dataMap;
            if (empty($pageName)) {
                $pageName = $this->pageName;
            } 
            //else {
            //    $pageName.=self::EXT;
            //}
            return array($pageName, $dataMap);
        }
        return true;
    }

    /**
     * 2014-04-12 更改POST值设置，允许POST值为空的值。
     * @deprecated since v1.2
     */
    private function getAllPOSTParameter()
    {
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($_POST); $i++) {
            $key = $keys[$i];
            $value = $this->request->post($key);
            //if (isset($value))//2014-04-12
            $this->parameterMap->set($key, $value);
        }
    }

    /**
     * 2014-05-09 对GET提交的数据进行HTML编码，防止特殊字符引起的错误
     * 2014-05-13 重新修改数据过滤
     * 2014-05-14 增加对多个无名参数值的处理，以数组方式赋值过去。
     *            把lineGET参数赋值到GET数组中(since v1.2)
     *            原先的获取方式取消，使用系统GET方式
     * @deprecated since v1.2
     */
    private function getAllGETParameter()
    {
        $keys = array_keys($_GET);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $this->request->get($key);
            //if (isset($value))//2014-04-12
            $this->parameterMap->set($key, $value);
        }
    }

    private function runMethod($controller, $mixed = null)
    {
        $data = $this->matchAction($controller, $mixed);
        $this->getParameters();
        if ($data === false) {
            return $this->callAction($controller);
        } else if ($data === true) {
            if (!$this->oneParamter)
                $this->parameterMap->remove(0);
            return $this->callAction($controller, $mixed);
        }
    }

    /**
     * 2014-05-14 更改单个/多个无名参数值的赋值
     *            当多个无名参数时，用形参方式传递，参数赋值则是以数组方式按下标顺序赋值
     * @param type $methodParams
     * @return type
     */
    private function fillFormalParameter($methodParams)
    {
        $methodParamValues = array();
        $i = 0;
        foreach ($methodParams as $methodParam) {
            $class = $methodParam->getClass();
            if ($methodParam->isArray()) {
                $array = array();
                while ($entry = $this->parameterMap->entry()) {
                    $key = $entry->key;
                    //if(strcasecmp($key,$this->oneParamter)==0) $key = 0;
                    $array[$key] = $entry->value;
                }
                $value = $array;
            } else if (isset($class) && strcasecmp($class->getName(), 'Request') == 0) {
                $value = $this->request;
            } else if (isset($class) && strcasecmp($class->getName(), 'UploadFile') == 0) {
                $value = $this->request->getUploadFile($methodParam->getName());
                if (!isset($value)) {
                    $value = new Siglefile();
                } else {
                    if (!$value instanceof Siglefile)
                        $value = new Siglefile();
                }
            }else if (isset($class) && strcasecmp($class->getName(), 'FileArray') == 0) {
                $value = $this->request->getUploadFile($methodParam->getName());
                if (!isset($value)) {
                    $value = new Multifile();
                } else {
                    if (!$value instanceof Multifile)
                        $value = new Multifile();
                }
//            } else if ($this->parameterMap->size() == 0) {
                //$methodParamValues[] = null;
            } else {
                $value = $this->parameterMap->get($methodParam->getName());
                if (!isset($value)) {
                    if ($this->parameterMap->containsKey($i)) {//2014-05-14
                        $value = $this->parameterMap->get($i);
                        //$this->parameterMap->remove(0);
                        $i++;
                    }
                }
//                if ($this->parameterMap->containsKey($i)) {//2014-05-14
//                    $value = $this->parameterMap->get($i);
//                    //$this->parameterMap->remove(0);
//                    $i++;
//                } else {
//                    ;
//                }
            }
            $methodParamValues[] = $value;
        }
        return $methodParamValues;
    }

    private function autoLoadControllerClass($class)
    {
        $sourceLib = LP_LIBRARY_PATH . "{$class}.php";
        $sourceApp = $this->application . "{$class}.php";
        if (is_file($sourceLib)) {
            require_once($sourceLib);
        } else if (is_file($sourceApp)) {
            require_once($sourceApp);
        } else {
            $this->loadUserLineClass($class);
        }
    }

    private function loadUserLineClass($name)
    {
        $name = strtolower($name);
        if (in_array($name, Router::$LINE_CORE)) {
            $line = LP_CORE_LINE;
        } else if (in_array($name, Router::$LINE_IO)) {
            $line = LP_IO_LINE;
        } else if (in_array($name, Router::$LINE_DB)) {
            $line = LP_DB_LINE;
        } else {
            $line = LP_CORE_ABSTRACT;
        }
        $line .= "{$name}.php";
        if (is_file($line)) {
            require_once($line);
        } else {
            throw new InvalidRequestException(Config::$LP_LANG['bad_request'] . ':' . $name);
        }
    }

    private function getParameters()
    {
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $this->request->post($key);
            //if (isset($value))//2014-04-12
            $this->parameterMap->set($key, $value);
        }
        $keys = array_keys($_GET);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $this->request->get($key);
            //if (isset($value))//2014-04-12
            $this->parameterMap->set($key, $value);
        }
    }

}