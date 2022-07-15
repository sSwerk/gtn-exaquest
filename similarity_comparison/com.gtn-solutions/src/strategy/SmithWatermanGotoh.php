<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * ===
 * Changelog:
 *  - 04/2020: initial implementation based on https://stackoverflow.com/questions/16925150/php-string-comparison-and-similarity-index/38236357#38236357
 *  - 05/2020: update to PHP 8.0
 */
namespace GTN\strategy;

/**
 * based on https://stackoverflow.com/questions/16925150/php-string-comparison-and-similarity-index/38236357#38236357
 */
class SmithWatermanGotoh {
    private float $gapValue;
    private SmithWatermanMatchMismatch $substitution;

    /**
     * Constructs a new Smith Waterman metric.
     *
     * @param float $gapValue
     *            a non-positive gap penalty
     * @param null $substitution
     *            a substitution function
     */
    public function __construct(float $gapValue=-0.5, $substitution=null)
    {
        if($gapValue > 0.0) {
            throw new Exception("gapValue must be <= 0");
        }
        //if(empty($substitution)) throw new Exception("substitution is required");
        if (empty($substitution)) {
            $this->substitution = new SmithWatermanMatchMismatch(1.0, -2);
        }
        else {
            $this->substitution = $substitution;
        }
        $this->gapValue = $gapValue;
    }

    public function compare($a, $b) : float
    {
        if (empty($a) && empty($b)) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $maxDistance = min(mb_strlen($a), mb_strlen($b))
                * max($this->substitution->max(), $this->gapValue);
        return $this->calculateSmithWatermanGotoh($a, $b) / $maxDistance;
    }

    private function calculateSmithWatermanGotoh($s, $t) : float
    {
        $v0 = [];
        $v1 = [];
        $t_len = mb_strlen($t);
        $max = $v0[0] = max(0, $this->gapValue, $this->substitution->compare($s, 0, $t, 0));

        for ($j = 1; $j < $t_len; $j++) {
            $v0[$j] = max(0, $v0[$j - 1] + $this->gapValue,
                    $this->substitution->compare($s, 0, $t, $j));

            $max = max($max, $v0[$j]);
        }

        // Find maximum value
        for ($i = 1, $iMax = mb_strlen($s); $i < $iMax; $i++) {
            $v1[0] = max(0, $v0[0] + $this->gapValue, $this->substitution->compare($s, $i, $t, 0));

            $max = max($max, $v1[0]);

            for ($j = 1; $j < $t_len; $j++) {
                $v1[$j] = max(0, $v0[$j] + $this->gapValue, $v1[$j - 1] + $this->gapValue,
                        $v0[$j - 1] + $this->substitution->compare($s, $i, $t, $j));

                $max = max($max, $v1[$j]);
            }

            for ($j = 0; $j < $t_len; $j++) {
                $v0[$j] = $v1[$j];
            }
        }

        return $max;
    }
}

class SmithWatermanMatchMismatch
{
    private float $matchValue;
    private float $mismatchValue;

    /**
     * Constructs a new match-mismatch substitution function. When two
     * characters are equal a score of <code>matchValue</code> is assigned. In
     * case of a mismatch a score of <code>mismatchValue</code>. The
     * <code>matchValue</code> must be strictly greater then
     * <code>mismatchValue</code>
     *
     * @param float matchValue
     *            value when characters are equal
     * @param float mismatchValue
     *            value when characters are not equal
     */
    public function __construct($matchValue, $mismatchValue) {
        if($matchValue <= $mismatchValue) {
            throw new Exception("matchValue must be > matchValue");
        }

        $this->matchValue = $matchValue;
        $this->mismatchValue = $mismatchValue;
    }

    public function compare($a, $aIndex, $b, $bIndex) : float {
        return ($a[$aIndex] === $b[$bIndex] ? $this->matchValue
                : $this->mismatchValue);
    }

    public function max() : float {
        return $this->matchValue;
    }

    public function min() : float {
        return $this->mismatchValue;
    }
}

/*
$x = new SmithWatermanMatchMismatch(1.0, -2.0);
$o = new SmithWatermanGotoh(-0.5, $x);
echo $o->compare("LEGENDARY","BARNEY STINSON").PHP_EOL;
echo $o->compare("atest","Btest").PHP_EOL;
echo $o->compare("atest","atest").PHP_EOL;
*/