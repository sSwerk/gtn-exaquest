<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN;

require_once(dirname(__FILE__, 3).'/vendor/autoload.php');

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MLogger;

class Logger {
    protected static MLogger $INSTANCE;

    /**
     * return logger singleton
     *
     * @return MLogger
     */
    public static function getLogger() : MLogger
    {
        if (!isset(self::$INSTANCE)) {
            self::configureInstance();
        }

        return self::$INSTANCE;
    }

    /**
     * Configure Monolog to use a console log
     *
     */
    protected static function configureInstance() : void
    {
        $stream = new StreamHandler('php://stdout', MLogger::DEBUG);
        $logger = new MLogger('gtn-jku-similarity');
        $logger->pushHandler($stream);

        self::$INSTANCE = $logger;
    }

    public static function debug($message, array $context = []): void {
        self::getLogger()->debug($message, $context);
    }

    public static function info($message, array $context = []): void {
        self::getLogger()->info($message, $context);
    }

    public static function notice($message, array $context = []): void {
        self::getLogger()->notice($message, $context);
    }

    public static function warning($message, array $context = []): void {
        self::getLogger()->warning($message, $context);
    }

    public static function error($message, array $context = []): void {
        self::getLogger()->error($message, $context);
    }

    public static function critical($message, array $context = []): void {
        self::getLogger()->critical($message, $context);
    }

    public static function alert($message, array $context = []): void {
        self::getLogger()->alert($message, $context);
    }

}