<?php

namespace com\digitalefotografien\crawler;

class ShopImageCrawler extends ShopCrawler {
    public $target_dir = "target";

    private $parse_products = null;
    public function setParseProduct($cb) {
        $this->parse_products = $cb;
    }
    private $get_images = null;
    public function setGetImages($cb) {
        $this->get_images = $cb;
    }
    
    /**
     * Duplikats-Prüfung auf bereits geparste URLs.
     *
     * Wenn true, werden alle Produkte übersprungen, deren URL bereits geparsed wurde.
     * (berücksichtigt die index.csv)
     */
    public $dup_url = false;
    
    /**
     * Duplikats-Prüfung auf Arikel-Nummer.
     *
     * Wenn true, werden alle Produkte übersprungen, deren Artikel-Nummer bereits geparsed wurde.
     * (berücksichtigt die index.csv)
     */
    public $dup_number = false;
    
    public function run() {
        if ($this->dup_url || $this->dup_number)
            $this->loadIndex();
    
        foreach ($this->product_urls as $url) {
            if (is_array($url)) {
                $row = $url;
                $url = $row['url'];
            } else {
                $row = array(
                    'url' => $url,
                );
            }
            if ($this->dup_url && in_array($url, $this->dups['url']))
                continue;
            if ($this->dup_number && isset($row['number']) && in_array($row['number'], $this->dups['number']))
                continue;
            $this->startTime($url);
            $crawler = $this->client->request('GET', $url);
            $t = call_user_func($this->parse_products, $crawler);
            if (!$t) {
                if (!isset($row['number']))
                    $row['number'] = 'not found';
                $row['images'] = 'e';
            } else {
                $row = array_merge($row, $t);
                $id = isset($row['number']) ? $row['number'] : sprintf("%x", crc32($url));
                
                if ($this->dup_number && in_array($id, $this->dups['number'])) {
                    $row['images'] = 'd';
                } else {
                    $images = call_user_func($this->get_images, $crawler);
                    $row['images'] = count($images);
                    if (count($images)) {
                        if (!is_dir($dir = ($this->target_dir . DIRECTORY_SEPARATOR . $id)))
                            mkdir($dir);
                        $filenames = array();
                        foreach ($images as $img) {
                            $fn = basename($img);
                            while (isset($filenames[$fn]))
                                $fn = preg_replace("/(?:#(\d+))?\.(\w+)+/e", "'#'.($1 + 1).'.$2'", $fn);
                            $this->downloadImage($dir . DIRECTORY_SEPARATOR . $fn, $img);
                        }
                    }
                }
            }
            $this->logProduct($row);
            $this->endTime($row['number']." ".$row['images']);
        }
    }
    
    private $index_fp = null;
    public function _sortColumnPriority($a, $b) {
        $a = $this->logColumnPriority($a);
        $b = $this->logColumnPriority($b);
        if (is_int($a) && is_int($b)) {
            return ($a == $b) ? 0 : ($a < $b ? -1 : 1);
        } elseif (is_int($a)) {
            return -1;
        } elseif (is_int($b)) {
            return 1;
        } else {
            return strcasecmp($a, $b);
        }
    }
    private function logColumnPriority($c) {
        switch ($c) {
            case 'number':
                return 0;
            case 'images':
                return 1;
            case 'url':
                return 2;
            default:
                return $c;
        }
    }
    public function logProduct($row) {
        uksort($row, array($this, '_sortColumnPriority'));
        if (!isset($this->index_fp)) {
            $this->index_fp = fopen($file = ($this->target_dir . DIRECTORY_SEPARATOR . "index.csv"), 'a');
            if (!is_file($file) || filesize($file) == 0)
                fputcsv($this->index_fp, array_keys($row));
        }
        fputcsv($this->index_fp, $row);
        foreach (array_keys($this->dups) as $i => $x) {
            $this->dups[$x][] = isset($row[$i]) ? $row[$i] : null;
        }
    }
    
    private $dups = array('number' => array(), 'url' => array());
    protected function loadIndex() {
        $this->startTime("Loading index...");
        $t = microtime(1);
        
        if (file_exists($f = $this->target_dir . DIRECTORY_SEPARATOR . "index.csv")) {
            $fp = fopen($f, 'r');
            $h = fgetcsv($fp);
            foreach ($h as $x) {
                $this->dups[$x] = array();
            }
            while ($row = fgetcsv($fp)) {
                foreach ($h as $i => $x) {
                    $this->dups[$x][] = isset($row[$i]) ? $row[$i] : null;
                }
            }
            fclose($fp);
        }
        $this->endTime(count($this->dups['number']));
    }
    
    protected function downloadImage($target, $url) {
        if (!file_exists($target))
            file_put_contents($target, $this->client->rawRequest($url));
    }
    
    public function __destruct() {
        if ($this->index_fp != null) {
            fclose($this->index_fp);
            $this->index_fp = null;
        }
    }
}
