<?php

declare(strict_types=1);

include_once(dirname(__DIR__) . '/php/autoload.php');

use Oeuvres\Kit\Xml;
use Oeuvres\Kit\Logger;
use Psr\Log\LogLevel;

/**
 * This is not a kind of unit test, because human evaluation of
 * a good export requires for now a lot more than some 
 * automated assertions.
 */
Tei2simpleTest::run();
class Tei2simpleTest
{
    static private $formats = array(
        "article",
        "dc",
        "iramuteq",
        "markdown",
        "toc",
    );
    static public function run()
    {
        $logger = new Logger(LogLevel::DEBUG);
        $logger->info("Test simple TEI exports");
        foreach(self::$formats as $format) {
            $class = "Oeuvres\\Teinte\\Tei2".$format;
            echo "    ",$class::NAME,": \t",$class::LABEL,"\n";
        }

        Xml::setLogger($logger);
        $srcFile = __DIR__.'/blanqui1866_prise-armes.xml';
        $dom = Xml::dom($srcFile);
        foreach(self::$formats as $format)
        {
            $class = "Oeuvres\\Teinte\\Tei2" . $format;
            $tei2 = new $class($logger);
            $tei2->toUri($dom, $tei2->dstFile($srcFile, __DIR__ . "/out"));
        }
    }
}

// EOF