<?php

namespace Pheeque\CraftPluginsAnalyzer;

abstract class Analyzer {
    abstract public function run(callable $onProgressUpdate);
}
