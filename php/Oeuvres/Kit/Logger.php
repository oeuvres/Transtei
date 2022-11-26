<?php
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Kit;

use \DateTime;
use Psr\Log\{AbstractLogger, InvalidArgumentException, LogLevel, LoggerInterface, NullLogger};

/**
 * A static logger
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
abstract class Logger extends AbstractLogger
{
    /** Logging level by name */
    private const LEVEL_VERBOSITY = [
        LogLevel::EMERGENCY => 1,
        LogLevel::ALERT => 2,
        LogLevel::CRITICAL => 3,
        LogLevel::ERROR => 4,
        LogLevel::WARNING => 5,
        LogLevel::NOTICE => 6,
        LogLevel::INFO => 7,
        LogLevel::DEBUG => 8,
    ];
    
    /** Default prefix for message */
    private string $prefix = "[{level}] "; 
    /** Default suffix for message (ex: clossing tag forr html) */
    private string $suffix = ""; 
    /** Default level of message to output */
    private int $verbosity = 4;
    /** For a duration */
    private $start_time = 0;

    public function __construct(
        ?string $level = LogLevel::ERROR, 
        ?string $prefix = "[{level}] "
    ) {
        $this->level($level);
        $this->prefix($prefix);
    }

    /**
     * Get/Set level
     */
    public function level(?string $level = null)
    {
        if (!$level) {
            $key = array_search ($this->verbosity, self::LEVEL_VERBOSITY);
            return $key;
        }
        $verbosity = self::verbosity($level);
        $this->verbosity = $verbosity;
    }

    /**
     * Get a verbosity int from a string level 
     */
    protected static function verbosity(string $level):int
    {
        if (!isset(self::LEVEL_VERBOSITY[$level])) {
            // :o) impossible to log 
            throw new InvalidArgumentException(
                sprintf('Unknown log level "%s"', $level)
            );
            return self::LEVEL_VERBOSITY[LogLevel::ERROR];
        }
        return self::LEVEL_VERBOSITY[$level];
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function log($level, $message, array $context = []): bool
    {
        // if message is empty, do nothing
        if($message === true) return false;
        if($message === null) return false;
        if(is_string($message) && trim($message) === '') return false;

        $verbosity = self::verbosity($level);
        if ($verbosity > $this->verbosity) return false;

        $date = new DateTime();
        $context['level'] = $level;
        $context['datetime'] = $date->format('Y-m-d H:i:s');
        $context['time'] = $date->format('H:i:s');
        $mess = $this->interpolate($this->prefix . $message . $this->suffix, $context);
        
        $this->write($level, $mess);
        return true;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     */
    private function interpolate(string $message, array $context): string
    {
        // limit message size (in case of bad param)
        $message = substr($message, 0, 512);
        if (\strpos($message, '{') === false) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (\is_object($val) && \method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object '.\get_class($val).']';
            } else {
                $replacements["{{$key}}"] = '['.\gettype($val).']';
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Get/Set prefix for log message
     */
    public function prefix(?string $prefix = null)
    {
        if ($prefix === null) {
            return $this->prefix;
        }
        $this->prefix = $prefix;
    }

    /**
     * Set suffix for log message
     */
    public function suffix(?string $suffix = null)
    {
        if ($suffix === null) {
            return $this->suffix;
        }
        $this->suffix = $suffix;
    }

    /**
     * Where to write log message ?
     */
    abstract protected function write($level, string $message);

}