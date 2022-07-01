<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\strategy\editbased;

use Exception;
use GTN\Logger;
use GTN\strategy\ComparisonStrategy;

abstract class EditBasedComparisonStrategy implements ComparisonStrategy {
    abstract public function getEditBasedDistance(string $first, string $second): float;

    public function getDistance(string $first, string $second): float {
        return $this->getEditBasedDistance($first, $second);
    }


    /**
     * @throws Exception
     */
    public function isSimilar(string $first, string $second, float $threshold=0.8): bool {
        if($threshold < 0.0 || $threshold > 1.0) {
            Logger::error("threshold must be in the range [0.0 - 1.0]", ['threshold' => $threshold]);
            throw new Exception("threshold must be in the range [0.0 - 1.0]");
        }

        $distance = $this->getEditBasedDistance($first, $second);

        return $distance >= $threshold;
    }
}