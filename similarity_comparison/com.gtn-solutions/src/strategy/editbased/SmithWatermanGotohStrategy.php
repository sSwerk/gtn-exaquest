<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\strategy\editbased;

use GTN\strategy\SmithWatermanGotoh;
use GTN\strategy\SmithWatermanMatchMismatch;

require_once(dirname(__FILE__, 2).'/SmithWatermanGotoh.php');


class SmithWatermanGotohStrategy extends EditBasedComparisonStrategy {
    private float $matchValue;
    private float $mismatchValue;
    private float $gapValue;

    private SmithWatermanMatchMismatch $matchMismatchSubstitute;
    private SmithWatermanGotoh $smithWatermanGotoh;

    public function __construct(float $matchValue, float $mismatchValue, float $gapValue) {
        $this->matchValue = $matchValue;
        $this->mismatchValue = $mismatchValue;
        $this->gapValue = $gapValue;

        if($matchValue <= $mismatchValue) {
            throw new Exception("matchValue must be > matchValue");
        }
        if($gapValue > 0.0) {
            throw new Exception("gapValue must be <= 0");
        }

        $this->matchMismatchSubstitute = new SmithWatermanMatchMismatch($this->matchValue, $this->mismatchValue);
        $this->smithWatermanGotoh = new SmithWatermanGotoh($this->gapValue, $this->matchMismatchSubstitute);
    }

    /**
     * @param string $first
     * @param string $second
     * @return float 1 => exact match, 0 => completely different
     */
    public function getSmithWatermanGotohDistance(string $first, string $second): float {
        return $this->smithWatermanGotoh->compare($first, $second);
    }

    public function getEditBasedDistance(string $first, string $second): float {
        return $this->getSmithWatermanGotohDistance($first, $second);
    }
}