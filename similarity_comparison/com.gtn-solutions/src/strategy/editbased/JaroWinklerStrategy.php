<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\strategy\editbased;

require_once(dirname(__FILE__, 2).'/jarowinkler.php');

class JaroWinklerStrategy extends EditBasedComparisonStrategy {
    private int $minPrefixLength;
    private float $prefixScale;

    /**
     * @param int $minPrefixLength
     * @param float $prefixScale
     */
    public function __construct(int $minPrefixLength, float $prefixScale) {
        $this->minPrefixLength = $minPrefixLength;
        $this->prefixScale = $prefixScale;
    }

    public function getJaroDistance(string $first, string $second): float {
        return Jaro($first, $second);
    }

    /**
     * @param string $first
     * @param string $second
     * @return float 1 => exact match, 0 => completely different
     */
    public function getJaroWinklerDistance(string $first, string $second): float {
        return JaroWinkler($first, $second, $this->prefixScale, $this->minPrefixLength);
    }

    public function getEditBasedDistance(string $first, string $second): float {
        return $this->getJaroWinklerDistance($first, $second);
    }
}