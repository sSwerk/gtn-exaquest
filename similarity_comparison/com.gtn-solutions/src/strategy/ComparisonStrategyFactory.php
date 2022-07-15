<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\strategy;

use GTN\strategy\editbased\JaroWinklerStrategy;
use GTN\strategy\editbased\SmithWatermanGotohStrategy;

class ComparisonStrategyFactory {

    /**
     * Jaro–Winkler similarity uses a prefix scale p which gives more favorable ratings to strings that match from the beginning
     * for a set prefix length.
     * The prefix scale should not exceed 1/minPrefixLength, otherwise the similarity may be greater than 1, i.e. for a
     * prefix length of 4, the scale should not exceed 0.25
     *
     *
     * @param int $minPrefixLength
     * @param float $prefixScale
     * @return JaroWinklerStrategy
     */
    public static function createJaroWinklerStrategy(int $minPrefixLength=4, float $prefixScale=0.1): JaroWinklerStrategy {
        return new JaroWinklerStrategy($minPrefixLength, $prefixScale);
    }

    /**
     * @param float $matchValue value when characters are equal (must be greater than mismatchValue)
     * @param float $mismatchValue penalty when characters are not equal
     * @param float $gapValue a non-positive gap penalty
     */
    public static function createSmithWatermanGotohStrategy(float $matchValue=1.0, float $mismatchValue=-2.0, float $gapValue=-0.5): SmithWatermanGotohStrategy {
        return new SmithWatermanGotohStrategy($matchValue,$mismatchValue,$gapValue);
    }
}