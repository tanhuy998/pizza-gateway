<?php
    namespace Application;

    use Dependencies\Parsing\DirectoryParser as DirectoryParser;
use Exception;

class DirectoryManager {

        private $basePath;
        private $directories;
        public $parser;

        public function __construct(string $basePath) {
            
            $this->parser = new DirectoryParser();
            $this->directories = [];

        }

        public function Bind(string $_name, string $_path) {
            
            $parser = $this->parser;

            if ($parser->Isdirectory($_path)) {
                $this->directories[$_name] = $_path;

                return;
            }
            
            throw new Exception();
        }

        public function Has(string $_name) {
            return isset($this->directories[$_name]);
        }

        public function Get(string $_name) {

            if (isset($this->directories[$_name])) {

                return $this->directories[$_name];
            }

            return null;
        }

        public function GetFullDirectory(string $_name) {
            
            if ($this->Has($_name)) {

                return $this->basePath . $this->Get($_name);
            }

            return null;
        }
    }