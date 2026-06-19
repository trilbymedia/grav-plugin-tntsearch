<?php

namespace TeamTNT\TNTSearch\Stemmer;

/**
 * Takes a word and reduces it to its Dutch stem using the Porter stemmer
 * algorithm.
 *
 * References:
 *  - https://snowballstem.org/algorithms/dutch/stemmer.html
 *  - https://snowballstem.org/texts/r1r2.html
 *
 * Usage:
 *  $stem = DutchStemmer::stem($word);
 */
class DutchStemmer implements Stemmer
{
    /**
     * R1 / R2 region boundaries, expressed as character offsets from the
     * start of the (post-prelude) word. Per the Snowball convention these
     * are fixed once `markRegions()` has run and do NOT change as suffixes
     * are deleted from the end of the word.
     */
    private static $p1;
    private static $p2;

    private static $cache = array();

    /**
     * Dutch vowels (after accent removal). Note: 'è' is preserved as a
     * vowel per the Snowball spec; the temporary uppercase markers 'I'
     * and 'Y' are NOT in this set (they count as non-vowels).
     */
    private static $vowels = array('a', 'e', 'i', 'o', 'u', 'y', 'è');

    /**
     * Set by step 2 (e_ending) when an 'e' was actually removed. Used by
     * step 3b to decide whether the suffix 'bar' should be deleted.
     */
    private static $eFound = false;

    /**
     * Gets the stem of $word.
     *
     * @param string $word
     * @return string
     */
    public static function stem($word)
    {
        $word = mb_strtolower($word, 'UTF-8');

        // Check for invalid characters
        preg_match('#.#u', $word);
        if (preg_last_error() !== 0) {
            throw new \InvalidArgumentException(
                "Word '$word' seems to be errornous. Error code from preg_last_error(): "
                . preg_last_error()
            );
        }

        if (!isset(self::$cache[$word])) {
            self::$cache[$word] = self::getStem($word);
        }

        return self::$cache[$word];
    }

    /**
     * @param string $word
     * @return string
     */
    private static function getStem($word)
    {
        $word = self::prelude($word);
        self::markRegions($word);

        $word = self::standardSuffix($word);

        return self::postlude($word);
    }

    /**
     * Prelude:
     *  - Remove all umlaut and acute accents (ä ë ï ö ü á é í ó ú).
     *    Note: 'è' is kept (it counts as a vowel).
     *  - Put initial y, and y after a vowel, into upper case 'Y'.
     *  - Put i between vowels into upper case 'I'.
     *
     * @param string $word
     * @return string
     */
    private static function prelude($word)
    {
        // Remove umlaut and acute accents
        $word = str_replace(
            array('ä', 'ë', 'ï', 'ö', 'ü', 'á', 'é', 'í', 'ó', 'ú'),
            array('a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'),
            $word
        );

        $vstr = implode('', self::$vowels);

        // Initial y -> Y
        $word = preg_replace('#^y#u', 'Y', $word);

        // y after a vowel -> Y
        $word = preg_replace('#([' . $vstr . '])y#u', '$1Y', $word);

        // i between vowels -> I (loop because matches can overlap)
        do {
            $word = preg_replace(
                '#([' . $vstr . '])i([' . $vstr . '])#u',
                '$1I$2',
                $word,
                -1,
                $count
            );
        } while ($count > 0);

        return $word;
    }

    /**
     * Postlude: turn I and Y back into lower case.
     *
     * @param string $word
     * @return string
     */
    private static function postlude($word)
    {
        return str_replace(array('I', 'Y'), array('i', 'y'), $word);
    }

    /**
     * Compute and store p1 and p2 for the current (post-prelude) word.
     *
     * R1 starts after the first non-vowel that follows a vowel, with the
     * extra constraint that p1 must be at least 3.
     *
     * IMPORTANT: per the Snowball spec (and as implemented in NLTK / the
     * Snowball-generated C code), R2 is searched starting from the
     * *unadjusted* R1 boundary — i.e. the original vowel-then-non-vowel
     * position, not the position after the "p1 >= 3" adjustment. The
     * adjustment only applies to R1.
     *
     * Per the Snowball spec the vowel grouping `v` is 'aeiouyè'; the
     * markers 'I' and 'Y' are NOT vowels here.
     *
     * @param string $word
     */
    private static function markRegions($word)
    {
        $len = mb_strlen($word, 'UTF-8');
        self::$p1 = $len;
        self::$p2 = $len;

        $rawP1 = self::findVNV($word, 0, $len);
        if ($rawP1 === null) {
            return;
        }
        // R1 is the position of the unadjusted boundary
        self::$p1 = max($rawP1, 3);

        // p2 is found from the *raw* (unadjusted) p1, not from the
        // possibly-adjusted self::$p1.
        $p2 = self::findVNV($word, $rawP1, $len);
        if ($p2 === null) {
            return;
        }
        self::$p2 = $p2;
    }

    /**
     * Helper: find the position immediately after the first vowel-then-
     * non-vowel transition in $word, starting at $from (inclusive) and up
     * to $to (exclusive). Returns null if no such transition exists.
     *
     * @param string $word
     * @param int $from
     * @param int $to
     * @return int|null
     */
    private static function findVNV($word, $from, $to)
    {
        $i = $from;
        // Scan past non-vowels until we find a vowel
        while ($i < $to && !self::isVowel(mb_substr($word, $i, 1, 'UTF-8'))) {
            $i++;
        }
        // Then scan past vowels until we find a non-vowel
        while ($i < $to && self::isVowel(mb_substr($word, $i, 1, 'UTF-8'))) {
            $i++;
        }
        if ($i >= $to) {
            return null;
        }
        // $word[$i] is the non-vowel; region starts after it
        return $i + 1;
    }

    /**
     * Is $ch a vowel per the Snowball spec's `v` grouping (aeiouyè)?
     *
     * @param string $ch single character
     * @return bool
     */
    private static function isVowel($ch)
    {
        return in_array($ch, self::$vowels, true);
    }

    /**
     * Does the suffix of $word fall within R1?
     * (i.e. does the suffix start at or after position p1?)
     *
     * @param string $word
     * @param string $suffix
     * @return bool
     */
    private static function inR1($word, $suffix)
    {
        $suffixStart = mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8');
        return $suffixStart >= self::$p1;
    }

    /**
     * Does the suffix of $word fall within R2?
     *
     * @param string $word
     * @param string $suffix
     * @return bool
     */
    private static function inR2($word, $suffix)
    {
        $suffixStart = mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8');
        return $suffixStart >= self::$p2;
    }

    /**
     * Undouble the ending: if the word ends in kk, dd or tt, remove the
     * last letter.
     *
     * @param string $word
     * @return string
     */
    private static function undouble($word)
    {
        if (preg_match('#(kk|dd|tt)$#u', $word)) {
            $word = mb_substr($word, 0, -1, 'UTF-8');
        }
        return $word;
    }

    /**
     * e_ending: delete final 'e' if in R1 and preceded by a non-vowel,
     * then undouble. Sets the e_found flag if an 'e' was deleted.
     *
     * Per the Snowball spec, the vowel grouping `v` here does NOT include
     * the temporary uppercase markers 'I' and 'Y' — those count as
     * non-vowels for suffix precondition checks.
     *
     * @param string $word
     * @return string
     */
    private static function eEnding($word)
    {
        self::$eFound = false;

        if (mb_substr($word, -1, 1, 'UTF-8') !== 'e') {
            return $word;
        }
        if (!self::inR1($word, 'e')) {
            return $word;
        }

        $len = mb_strlen($word, 'UTF-8');
        if ($len < 2) {
            return $word;
        }
        $prev = mb_substr($word, -2, 1, 'UTF-8');
        if (self::isVowel($prev)) {
            return $word;
        }

        $word = mb_substr($word, 0, -1, 'UTF-8');
        self::$eFound = true;

        return self::undouble($word);
    }

    /**
     * en_ending: delete the suffix if in R1 and preceded by a valid
     * en-ending (a non-vowel, and not the trigraph 'gem'), then undouble.
     *
     * @param string $word
     * @param string $suffix the suffix to remove ('en' or 'ene')
     * @return string
     */
    private static function enEnding($word, $suffix)
    {
        if (!self::inR1($word, $suffix)) {
            return $word;
        }

        $beforeSuffix = mb_substr(
            $word,
            0,
            mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8'),
            'UTF-8'
        );
        $blen = mb_strlen($beforeSuffix, 'UTF-8');
        if ($blen < 1) {
            return $word;
        }

        // Must be preceded by a non-vowel
        $prev = mb_substr($beforeSuffix, -1, 1, 'UTF-8');
        if (self::isVowel($prev)) {
            return $word;
        }

        // ...and not preceded by 'gem'
        if ($blen >= 3 && mb_substr($beforeSuffix, -3, 3, 'UTF-8') === 'gem') {
            return $word;
        }

        return self::undouble($beforeSuffix);
    }

    /**
     * Step 1 helper for s / se: delete if in R1 and preceded by a valid
     * s-ending (a non-vowel other than 'j').
     *
     * @param string $word
     * @param string $suffix 's' or 'se'
     * @return string
     */
    private static function sEnding($word, $suffix)
    {
        if (!self::inR1($word, $suffix)) {
            return $word;
        }

        $beforeSuffix = mb_substr(
            $word,
            0,
            mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8'),
            'UTF-8'
        );
        if (mb_strlen($beforeSuffix, 'UTF-8') < 1) {
            return $word;
        }

        $prev = mb_substr($beforeSuffix, -1, 1, 'UTF-8');
        // Valid s-ending = non-vowel other than 'j'
        if (self::isVowel($prev) || $prev === 'j') {
            return $word;
        }

        return $beforeSuffix;
    }

    /**
     * Apply the standard suffix steps (1, 2, 3a, 3b, 4) on the word.
     *
     * @param string $word
     * @return string
     */
    private static function standardSuffix($word)
    {
        // ---- Step 1 ----
        // Find the longest matching suffix among:
        //   heden  -> replace with 'heid' if in R1
        //   en, ene -> en_ending (longest first, so 'ene' before 'en')
        //   s, se   -> delete if in R1 and preceded by a valid s-ending
        if (self::endsWith($word, 'heden')) {
            if (self::inR1($word, 'heden')) {
                $word = mb_substr($word, 0, -5, 'UTF-8') . 'heid';
            }
        } elseif (self::endsWith($word, 'ene')) {
            $word = self::enEnding($word, 'ene');
        } elseif (self::endsWith($word, 'en')) {
            $word = self::enEnding($word, 'en');
        } elseif (self::endsWith($word, 'se')) {
            $word = self::sEnding($word, 'se');
        } elseif (self::endsWith($word, 's')) {
            $word = self::sEnding($word, 's');
        }

        // ---- Step 2 ----
        $word = self::eEnding($word);

        // ---- Step 3a: heid ----
        if (self::endsWith($word, 'heid') && self::inR2($word, 'heid')) {
            // Not preceded by 'c'
            $beforeLen = mb_strlen($word, 'UTF-8') - 4;
            $prev = $beforeLen >= 1
                ? mb_substr($word, $beforeLen - 1, 1, 'UTF-8')
                : '';
            if ($prev !== 'c') {
                $word = mb_substr($word, 0, -4, 'UTF-8');

                // Treat a preceding 'en' as in step 1(b)
                if (self::endsWith($word, 'en')) {
                    $word = self::enEnding($word, 'en');
                }
            }
        }

        // ---- Step 3b: d-suffixes ----
        // Search longest: lijk, baar, end, ing, bar, ig
        if (self::endsWith($word, 'lijk')) {
            if (self::inR2($word, 'lijk')) {
                $word = mb_substr($word, 0, -4, 'UTF-8');
                $word = self::eEnding($word);
            }
        } elseif (self::endsWith($word, 'baar')) {
            if (self::inR2($word, 'baar')) {
                $word = mb_substr($word, 0, -4, 'UTF-8');
            }
        } elseif (self::endsWith($word, 'end')) {
            $word = self::endIngSuffix($word);
        } elseif (self::endsWith($word, 'ing')) {
            $word = self::endIngSuffix($word);
        } elseif (self::endsWith($word, 'bar')) {
            if (self::inR2($word, 'bar') && self::$eFound) {
                $word = mb_substr($word, 0, -3, 'UTF-8');
            }
        } elseif (self::endsWith($word, 'ig')) {
            if (self::inR2($word, 'ig')) {
                // Not preceded by 'e'
                $beforeLen = mb_strlen($word, 'UTF-8') - 2;
                $prev = $beforeLen >= 1
                    ? mb_substr($word, $beforeLen - 1, 1, 'UTF-8')
                    : '';
                if ($prev !== 'e') {
                    $word = mb_substr($word, 0, -2, 'UTF-8');
                }
            }
        }

        // ---- Step 4: undouble vowel ----
        return self::undoubleVowel($word);
    }

    /**
     * Step 3b helper for 'end' / 'ing':
     *  - delete if in R2
     *  - if then preceded by 'ig': delete 'ig' if in R2 and not preceded
     *    by 'e', otherwise undouble the ending
     *
     * @param string $word ends in 'end' or 'ing'
     * @return string
     */
    private static function endIngSuffix($word)
    {
        $suffix = mb_substr($word, -3, 3, 'UTF-8'); // 'end' or 'ing'

        if (!self::inR2($word, $suffix)) {
            return $word;
        }

        $word = mb_substr($word, 0, -3, 'UTF-8');

        if (self::endsWith($word, 'ig')) {
            if (self::inR2($word, 'ig')) {
                $beforeLen = mb_strlen($word, 'UTF-8') - 2;
                $prev = $beforeLen >= 1
                    ? mb_substr($word, $beforeLen - 1, 1, 'UTF-8')
                    : '';
                if ($prev !== 'e') {
                    return mb_substr($word, 0, -2, 'UTF-8');
                }
            }
            // 'ig' present but conditions not met -> undouble instead
            return self::undouble($word);
        }

        return self::undouble($word);
    }

    /**
     * Step 4: undouble final vowel in CVD pattern.
     *
     * If the word ends with: non-vowel + double(a|e|o|u) + non-vowel
     * (where the trailing non-vowel is not the marker 'I'), remove one
     * of the doubled vowels. Examples: maan -> man, brood -> brod.
     *
     * @param string $word
     * @return string
     */
    private static function undoubleVowel($word)
    {
        $len = mb_strlen($word, 'UTF-8');
        if ($len < 4) {
            return $word;
        }

        $c  = mb_substr($word, $len - 4, 1, 'UTF-8'); // non-vowel
        $v1 = mb_substr($word, $len - 3, 1, 'UTF-8'); // vowel
        $v2 = mb_substr($word, $len - 2, 1, 'UTF-8'); // same vowel
        $d  = mb_substr($word, $len - 1, 1, 'UTF-8'); // non-vowel, not 'I'

        if (self::isVowel($c)) {
            return $word;
        }
        if ($v1 !== $v2 || !in_array($v1, array('a', 'e', 'o', 'u'), true)) {
            return $word;
        }
        if (self::isVowel($d) || $d === 'I') {
            return $word;
        }

        // Remove one of the doubled vowels
        return mb_substr($word, 0, $len - 3, 'UTF-8') . $v2 . $d;
    }

    /**
     * Helper: does $word end with $suffix?
     *
     * @param string $word
     * @param string $suffix
     * @return bool
     */
    private static function endsWith($word, $suffix)
    {
        $sl = mb_strlen($suffix, 'UTF-8');
        $wl = mb_strlen($word, 'UTF-8');
        if ($sl > $wl) {
            return false;
        }
        return mb_substr($word, $wl - $sl, $sl, 'UTF-8') === $suffix;
    }
}
