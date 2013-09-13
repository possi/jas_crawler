<?php

namespace de\jaschastarke\crawler;
use Symfony\Component\BrowserKit\Request;

use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\Client;

class CurlClient extends Client {
    private $_curl = null;
    public $debug = false;
    private $_last = array(
        'ih' => null,
        'ic' => null,
        'oh' => null,
        'oc' => null,
    );
    
    protected function _curlInit() {
        if ($this->_curl == null) {
            $this->_curl = curl_init();
            curl_setopt_array($this->_curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "Connection: keep-alive",
                ),
            ));
        }
        return $this->_curl;
    }
    public function get($uri) {
        return $this->rawRequest(new Request($uri, 'GET'));
    }
    public function rawRequest(Request $request) {
        $url = $request->getUri();
        $method = strtoupper($request->getMethod());
        
        $this->_curlInit();
        curl_setopt_array($this->_curl, array(
            /*CURLOPT_POST => $method == 'POST',
            CURLOPT_PUT => $method == 'PUT',
            CURLOPT_HTTPGET => $method == 'GET',
            CURLOPT_CUSTOMREQUEST => in_array($method, array('GET', 'POST', 'PUT')) ? $method : null,*/
            CURLOPT_URL => $url,
        ));
        if ($this->debug) {
            $this->_last = array(
                'ih' => null,
                'ic' => null,
                'oh' => null,
                'oc' => null,
            );
            curl_setopt($this->_curl, CURLINFO_HEADER_OUT, true);
            /*$tfp = fopen('php://memory', 'rw');
            curl_setopt($this->_curl, CURLOPT_WRITEHEADER, $tfp);*/
            $oh =& $this->_last['oh'];
            curl_setopt($this->_curl, CURLOPT_HEADERFUNCTION, function ($curl, $str) use (&$oh) {
                $oh .= $str;
                return strlen($str);
            });
        }
        switch ($method) {
            case 'POST':
                curl_setopt($this->_curl, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($this->_curl, CURLOPT_PUT, true);
                break;
            case 'GET':
                curl_setopt($this->_curl, CURLOPT_HTTPGET, true);
                break;
            default:
                curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        
        if ($request->getParameters()) {
            $values = $request->getParameters();
            foreach ($values as $key => &$val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $values[$key.'.'.$k] = $v;
                    }
                    unset($values[$key]);
                }
            }
            curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $values);
        }
        if ($request->getContent()) {
            throw new Exception("NYI");
            curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $request->getContent());
            $this->_last['ic'] = $request->getContent();
        }
        
        $r = curl_exec($this->_curl);
        
        if ($this->debug) {
            $this->_last['ih'] = curl_getinfo($this->_curl, CURLINFO_HEADER_OUT);
            
            /*rewind($tfp);
            $this->_last['oh'] = fread($tfp, 4096);
            fclose($tfp);*/
            $this->_last['oc'] = $r;
        }
        
        $ct = strtolower(curl_getinfo($this->_curl, CURLINFO_CONTENT_TYPE));
        if ($ct && ($ct == 'application/x-gzip' || $ct = 'application/gzip'))
            $r = file_get_contents("compress.zlib://data://text/plain;base64,".base64_encode($r));
        return $r;
    }
    public function getCurlResource() {
        return $this->_curlInit();
    }
    public function setOpt($key, $value = null) {
        if (is_array($key)) {
            curl_setopt_array($this->_curlInit(), $key);
        } else {
            curl_setopt($this->_curlInit(), $key, $value);
        }
    }
    protected function doRequest($request) {
        $url = $request->getUri();
        if (preg_match("#^(https?|ftp)://#", $url)) {
            return new Response($this->rawRequest($request));
        } else {
            if ($request->getMethod() != 'GET')
                throw new \Exception("From non-http/ftp-protocol only GET is supported");
            return new Response(file_get_contents($url));
        }
    }
    public function __destruct() {
        if ($this->_curl != null) {
            curl_close($this->_curl);
            $this->_curl = null;
        }
    }

    public static $CLASS_Crawler = '\de\jaschastarke\crawler\DomCrawler';
    /**
     * Creates a crawler.
     *
     * @param string $uri A uri
     * @param string $content Content for the crawler to use
     * @param string $type Content type
     *
     * @return Crawler
     */
    protected function createCrawlerFromContent($uri, $content, $type) {
        $crawler = new static::$CLASS_Crawler(null, $uri);
        $crawler->addContent($content, $type);

        return $crawler;
    }
    
    const HTTP_HEADER = 1;
    const HTTP_CONTENT = 2;
    const HTTP_FULL = 3;
    public function _getRequest($part = self::HTTP_FULL) {
        if ($part & self::HTTP_HEADER)
            echo $this->_last['ih']."\n";
        if ($part & self::HTTP_CONTENT)
            echo $this->_last['ic']."\n";
    }
    public function _getResponse($part = self::HTTP_FULL) {
        if ($part & self::HTTP_HEADER)
            echo $this->_last['oh']."\n";
        if ($part & self::HTTP_CONTENT)
            echo $this->_last['oc']."\n";
    }
    public function _printDebug() {
        echo "HTTP-Request:\n";
        echo $this->_getRequest()."\n\n";
        echo "HTTP-Response:\n";
        echo $this->_getResponse()."\n\n";
    }
}
