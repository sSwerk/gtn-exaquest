<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\strategy;

interface ComparisonStrategy {
    /**
     * Compares the given strings with each other and returns a normalized float that indicates the computed similarity, where
     * lower values indicate non-similarity.
     *
     * @param string $first
     * @param string $second
     * @return float normalized similarity, range [0.0 - 1.0]
     */
    public function getDistance(string $first, string $second): float;

    /**
     * Returns true, if and only if the computed distance metric between the two strings are greater than or equal to
     * the given threshold.
     * A given threshold of 0 will always return true, a threshold of 1 will only return true, if the strings are identical
     *
     * @param float $threshold allowed range [0.0 - 1.0], if 0.0 -> return always true, if 1.0 only true iff it is a perfect match
     * @param string $first
     * @param string $second
     * @return bool true, iff the calculated distance between $first and $second is >= $threshold
     */
    public function isSimilar(string $first, string $second, float $threshold=0.8): bool;
}