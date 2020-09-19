<?php
    use Dependencies\Parsing\DirectoryParser as DirectoryParser;

    class DirectoryManager {

        private $basePath;
        private $directories;
        public $parser;

        public function __construct() {
            
            $this->parser = new DirectoryParser();
            $this->directories = [];

        }

        public function BindDir(string $_name, string $_path) {
            
            $parser = $this->parser;

            if ($parser->Isdirectory($_path)) {
                $this->directories[$_name] = $_path;
            }
            
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
    }