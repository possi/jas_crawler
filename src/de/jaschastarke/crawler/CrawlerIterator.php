<?php

namespace de\jaschastarke\crawler;
use \Exception;

class CrawlerIterator implements \Iterator, \ArrayAccess {
    private $crawler;
    private $class;

    public function __construct(DomCrawler $crawler) {
        $this->crawler = $crawler;
        $this->class = get_class($crawler);
    }
    public function current() {
        $class = $this->class;
        return new $class($this->crawler->current(), $this->crawler->getUri());
    }
    public function key() {
        return $this->crawler->key();
    }
    public function next() {
        $this->crawler->next();
    }
    public function rewind() {
        $this->crawler->rewind();
    }
    public function valid() {
        return $this->crawler->valid();
    }

    public function offsetExists($offset) {
        if (!is_numeric($offset))
            throw new Exception("Invalid Offset");
        return $this->crawler->getNode($offset) != null;
    }
    public function offsetGet($offset) {
        if (!is_numeric($offset))
            throw new Exception("Invalid Offset");
        return $this->crawler->getOffset($offset);
    }
    public function offsetSet($offset, $value) {
        throw new Exception("Modifing an CrawlerIterator is not allowed");
    }
    public function offsetUnset($offset) {
        throw new Exception("Modifing an CrawlerIterator is not allowed");
    }
}
