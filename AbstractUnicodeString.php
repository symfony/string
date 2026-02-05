<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\String;

use Symfony\Component\String\Exception\ExceptionInterface;
use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\String\Exception\RuntimeException;

/**
 * Represents a string of abstract Unicode characters.
 *
 * Unicode defines 3 types of "characters" (bytes, code points and grapheme clusters).
 * This class is the abstract type to use as a type-hint when the logic you want to
 * implement is Unicode-aware but doesn't care about code points vs grapheme clusters.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @throws ExceptionInterface
 */
abstract class AbstractUnicodeString extends AbstractString
{
    public const NFC = \Normalizer::NFC;
    public const NFD = \Normalizer::NFD;
    public const NFKC = \Normalizer::NFKC;
    public const NFKD = \Normalizer::NFKD;

    // all ASCII letters sorted by typical frequency of occurrence
    private const ASCII = "\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";

    // the subset of folded case mappings that is not in lower case mappings
    private const FOLD_FROM = ['İ', 'µ', 'ſ', "\xCD\x85", 'ς', 'ϐ', 'ϑ', 'ϕ', 'ϖ', 'ϰ', 'ϱ', 'ϵ', 'ẛ', "\xE1\xBE\xBE", 'ß', 'ŉ', 'ǰ', 'ΐ', 'ΰ', 'և', 'ẖ', 'ẗ', 'ẘ', 'ẙ', 'ẚ', 'ẞ', 'ὐ', 'ὒ', 'ὔ', 'ὖ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'ᾐ', 'ᾑ', 'ᾒ', 'ᾓ', 'ᾔ', 'ᾕ', 'ᾖ', 'ᾗ', 'ᾘ', 'ᾙ', 'ᾚ', 'ᾛ', 'ᾜ', 'ᾝ', 'ᾞ', 'ᾟ', 'ᾠ', 'ᾡ', 'ᾢ', 'ᾣ', 'ᾤ', 'ᾥ', 'ᾦ', 'ᾧ', 'ᾨ', 'ᾩ', 'ᾪ', 'ᾫ', 'ᾬ', 'ᾭ', 'ᾮ', 'ᾯ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'ᾼ', 'ῂ', 'ῃ', 'ῄ', 'ῆ', 'ῇ', 'ῌ', 'ῒ', 'ῖ', 'ῗ', 'ῢ', 'ῤ', 'ῦ', 'ῧ', 'ῲ', 'ῳ', 'ῴ', 'ῶ', 'ῷ', 'ῼ', 'ﬀ', 'ﬁ', 'ﬂ', 'ﬃ', 'ﬄ', 'ﬅ', 'ﬆ', 'ﬓ', 'ﬔ', 'ﬕ', 'ﬖ', 'ﬗ'];
    private const FOLD_TO = ['i̇', 'μ', 's', 'ι', 'σ', 'β', 'θ', 'φ', 'π', 'κ', 'ρ', 'ε', 'ṡ', 'ι', 'ss', 'ʼn', 'ǰ', 'ΐ', 'ΰ', 'եւ', 'ẖ', 'ẗ', 'ẘ', 'ẙ', 'aʾ', 'ss', 'ὐ', 'ὒ', 'ὔ', 'ὖ', 'ἀι', 'ἁι', 'ἂι', 'ἃι', 'ἄι', 'ἅι', 'ἆι', 'ἇι', 'ἀι', 'ἁι', 'ἂι', 'ἃι', 'ἄι', 'ἅι', 'ἆι', 'ἇι', 'ἠι', 'ἡι', 'ἢι', 'ἣι', 'ἤι', 'ἥι', 'ἦι', 'ἧι', 'ἠι', 'ἡι', 'ἢι', 'ἣι', 'ἤι', 'ἥι', 'ἦι', 'ἧι', 'ὠι', 'ὡι', 'ὢι', 'ὣι', 'ὤι', 'ὥι', 'ὦι', 'ὧι', 'ὠι', 'ὡι', 'ὢι', 'ὣι', 'ὤι', 'ὥι', 'ὦι', 'ὧι', 'ὰι', 'αι', 'άι', 'ᾶ', 'ᾶι', 'αι', 'ὴι', 'ηι', 'ήι', 'ῆ', 'ῆι', 'ηι', 'ῒ', 'ῖ', 'ῗ', 'ῢ', 'ῤ', 'ῦ', 'ῧ', 'ὼι', 'ωι', 'ώι', 'ῶ', 'ῶι', 'ωι', 'ff', 'fi', 'fl', 'ffi', 'ffl', 'st', 'st', 'մն', 'մե', 'մի', 'վն', 'մխ'];

    // the subset of https://github.com/unicode-org/cldr/blob/master/common/transforms/Latin-ASCII.xml that is not in NFKD
    private const TRANSLIT_FROM = ['Æ', 'Ð', 'Ø', 'Þ', 'ß', 'æ', 'ð', 'ø', 'þ', 'Đ', 'đ', 'Ħ', 'ħ', 'ı', 'ĸ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'ŉ', 'Ŋ', 'ŋ', 'Œ', 'œ', 'Ŧ', 'ŧ', 'ƀ', 'Ɓ', 'Ƃ', 'ƃ', 'Ƈ', 'ƈ', 'Ɖ', 'Ɗ', 'Ƌ', 'ƌ', 'Ɛ', 'Ƒ', 'ƒ', 'Ɠ', 'ƕ', 'Ɩ', 'Ɨ', 'Ƙ', 'ƙ', 'ƚ', 'Ɲ', 'ƞ', 'Ƣ', 'ƣ', 'Ƥ', 'ƥ', 'ƫ', 'Ƭ', 'ƭ', 'Ʈ', 'Ʋ', 'Ƴ', 'ƴ', 'Ƶ', 'ƶ', 'Ǆ', 'ǅ', 'ǆ', 'Ǥ', 'ǥ', 'ȡ', 'Ȥ', 'ȥ', 'ȴ', 'ȵ', 'ȶ', 'ȷ', 'ȸ', 'ȹ', 'Ⱥ', 'Ȼ', 'ȼ', 'Ƚ', 'Ⱦ', 'ȿ', 'ɀ', 'Ƀ', 'Ʉ', 'Ɇ', 'ɇ', 'Ɉ', 'ɉ', 'Ɍ', 'ɍ', 'Ɏ', 'ɏ', 'ɓ', 'ɕ', 'ɖ', 'ɗ', 'ɛ', 'ɟ', 'ɠ', 'ɡ', 'ɢ', 'ɦ', 'ɧ', 'ɨ', 'ɪ', 'ɫ', 'ɬ', 'ɭ', 'ɱ', 'ɲ', 'ɳ', 'ɴ', 'ɶ', 'ɼ', 'ɽ', 'ɾ', 'ʀ', 'ʂ', 'ʈ', 'ʉ', 'ʋ', 'ʏ', 'ʐ', 'ʑ', 'ʙ', 'ʛ', 'ʜ', 'ʝ', 'ʟ', 'ʠ', 'ʣ', 'ʥ', 'ʦ', 'ʪ', 'ʫ', 'ᴀ', 'ᴁ', 'ᴃ', 'ᴄ', 'ᴅ', 'ᴆ', 'ᴇ', 'ᴊ', 'ᴋ', 'ᴌ', 'ᴍ', 'ᴏ', 'ᴘ', 'ᴛ', 'ᴜ', 'ᴠ', 'ᴡ', 'ᴢ', 'ᵫ', 'ᵬ', 'ᵭ', 'ᵮ', 'ᵯ', 'ᵰ', 'ᵱ', 'ᵲ', 'ᵳ', 'ᵴ', 'ᵵ', 'ᵶ', 'ᵺ', 'ᵻ', 'ᵽ', 'ᵾ', 'ᶀ', 'ᶁ', 'ᶂ', 'ᶃ', 'ᶄ', 'ᶅ', 'ᶆ', 'ᶇ', 'ᶈ', 'ᶉ', 'ᶊ', 'ᶌ', 'ᶍ', 'ᶎ', 'ᶏ', 'ᶑ', 'ᶒ', 'ᶓ', 'ᶖ', 'ᶙ', 'ẚ', 'ẜ', 'ẝ', 'ẞ', 'Ỻ', 'ỻ', 'Ỽ', 'ỽ', 'Ỿ', 'ỿ', '©', '®', '₠', '₢', '₣', '₤', '₧', '₺', '₹', 'ℌ', '℞', '㎧', '㎮', '㏆', '㏗', '㏞', '㏟', '¼', '½', '¾', '⅓', '⅔', '⅕', '⅖', '⅗', '⅘', '⅙', '⅚', '⅛', '⅜', '⅝', '⅞', '⅟', '〇', '‘', '’', '‚', '‛', '“', '”', '„', '‟', '′', '″', '〝', '〞', '«', '»', '‹', '›', '‐', '‑', '‒', '–', '—', '―', '︱', '︲', '﹘', '‖', '⁄', '⁅', '⁆', '⁎', '、', '。', '〈', '〉', '《', '》', '〔', '〕', '〘', '〙', '〚', '〛', '︑', '︒', '︹', '︺', '︽', '︾', '︿', '﹀', '﹑', '﹝', '﹞', '｟', '｠', '｡', '､', '×', '÷', '−', '∕', '∖', '∣', '∥', '≪', '≫', '⦅', '⦆'];
    private const TRANSLIT_TO = ['AE', 'D', 'O', 'TH', 'ss', 'ae', 'd', 'o', 'th', 'D', 'd', 'H', 'h', 'i', 'q', 'L', 'l', 'L', 'l', '\'n', 'N', 'n', 'OE', 'oe', 'T', 't', 'b', 'B', 'B', 'b', 'C', 'c', 'D', 'D', 'D', 'd', 'E', 'F', 'f', 'G', 'hv', 'I', 'I', 'K', 'k', 'l', 'N', 'n', 'OI', 'oi', 'P', 'p', 't', 'T', 't', 'T', 'V', 'Y', 'y', 'Z', 'z', 'DZ', 'Dz', 'dz', 'G', 'g', 'd', 'Z', 'z', 'l', 'n', 't', 'j', 'db', 'qp', 'A', 'C', 'c', 'L', 'T', 's', 'z', 'B', 'U', 'E', 'e', 'J', 'j', 'R', 'r', 'Y', 'y', 'b', 'c', 'd', 'd', 'e', 'j', 'g', 'g', 'G', 'h', 'h', 'i', 'I', 'l', 'l', 'l', 'm', 'n', 'n', 'N', 'OE', 'r', 'r', 'r', 'R', 's', 't', 'u', 'v', 'Y', 'z', 'z', 'B', 'G', 'H', 'j', 'L', 'q', 'dz', 'dz', 'ts', 'ls', 'lz', 'A', 'AE', 'B', 'C', 'D', 'D', 'E', 'J', 'K', 'L', 'M', 'O', 'P', 'T', 'U', 'V', 'W', 'Z', 'ue', 'b', 'd', 'f', 'm', 'n', 'p', 'r', 'r', 's', 't', 'z', 'th', 'I', 'p', 'U', 'b', 'd', 'f', 'g', 'k', 'l', 'm', 'n', 'p', 'r', 's', 'v', 'x', 'z', 'a', 'd', 'e', 'e', 'i', 'u', 'a', 's', 's', 'SS', 'LL', 'll', 'V', 'v', 'Y', 'y', '(C)', '(R)', 'CE', 'Cr', 'Fr.', 'L.', 'Pts', 'TL', 'Rs', 'x', 'Rx', 'm/s', 'rad/s', 'C/kg', 'pH', 'V/m', 'A/m', ' 1/4', ' 1/2', ' 3/4', ' 1/3', ' 2/3', ' 1/5', ' 2/5', ' 3/5', ' 4/5', ' 1/6', ' 5/6', ' 1/8', ' 3/8', ' 5/8', ' 7/8', ' 1/', '0', '\'', '\'', ',', '\'', '"', '"', ',,', '"', '\'', '"', '"', '"', '<<', '>>', '<', '>', '-', '-', '-', '-', '-', '-', '-', '-', '-', '||', '/', '[', ']', '*', ',', '.', '<', '>', '<<', '>>', '[', ']', '[', ']', '[', ']', ',', '.', '[', ']', '<<', '>>', '<', '>', ',', '[', ']', '((', '))', '.', ',', '*', '/', '-', '/', '\\', '|', '||', '<<', '>>', '((', '))'];

    private static array $transliterators = [];
    private static array $tableZero;
    private static array $tableWide;

    public static function fromCodePoints(int ...$codes): static
    {
        $string = '';

        foreach ($codes as $code) {
            if (0x80 > $code %= 0x200000) {
                $string .= \chr($code);
            } elseif (0x800 > $code) {
                $string .= \chr(0xC0 | $code >> 6).\chr(0x80 | $code & 0x3F);
            } elseif (0x10000 > $code) {
                $string .= \chr(0xE0 | $code >> 12).\chr(0x80 | $code >> 6 & 0x3F).\chr(0x80 | $code & 0x3F);
            } else {
                $string .= \chr(0xF0 | $code >> 18).\chr(0x80 | $code >> 12 & 0x3F).\chr(0x80 | $code >> 6 & 0x3F).\chr(0x80 | $code & 0x3F);
            }
        }

        return new static($string);
    }

    /**
     * Generic UTF-8 to ASCII transliteration.
     *
     * Install the intl extension for best results.
     *
     * @param string[]|\Transliterator[]|\Closure[] $rules See "*-Latin" rules from Transliterator::listIDs()
     */
    public function ascii(array $rules = []): self
    {
        $str = clone $this;
        $s = $str->string;
        $str->string = '';

        array_unshift($rules, 'nfd');
        $rules[] = 'latin-ascii';

        if (\function_exists('transliterator_transliterate')) {
            $rules[] = 'any-latin/bgn';
        }

        $rules[] = 'nfkd';
        $rules[] = '[:nonspacing mark:] remove';

        while (\strlen($s) - 1 > $i = strspn($s, self::ASCII)) {
            if (0 < --$i) {
                $str->string .= substr($s, 0, $i);
                $s = substr($s, $i);
            }

            if (!$rule = array_shift($rules)) {
                $rules = []; // An empty rule interrupts the next ones
            }

            if ($rule instanceof \Transliterator) {
                $s = $rule->transliterate($s);
            } elseif ($rule instanceof \Closure) {
                $s = $rule($s);
            } elseif ($rule) {
                if ('nfd' === $rule = strtolower($rule)) {
                    normalizer_is_normalized($s, self::NFD) ?: $s = normalizer_normalize($s, self::NFD);
                } elseif ('nfkd' === $rule) {
                    normalizer_is_normalized($s, self::NFKD) ?: $s = normalizer_normalize($s, self::NFKD);
                } elseif ('[:nonspacing mark:] remove' === $rule) {
                    $s = preg_replace('/\p{Mn}++/u', '', $s);
                } elseif ('latin-ascii' === $rule) {
                    $s = str_replace(self::TRANSLIT_FROM, self::TRANSLIT_TO, $s);
                } elseif ('de-ascii' === $rule) {
                    $s = preg_replace("/([AUO])\u{0308}(?=\p{Ll})/u", '$1e', $s);
                    $s = str_replace(["a\u{0308}", "o\u{0308}", "u\u{0308}", "A\u{0308}", "O\u{0308}", "U\u{0308}"], ['ae', 'oe', 'ue', 'AE', 'OE', 'UE'], $s);
                } elseif (\function_exists('transliterator_transliterate')) {
                    if (null === $transliterator = self::$transliterators[$rule] ??= \Transliterator::create($rule)) {
                        if ('any-latin/bgn' === $rule) {
                            $rule = 'any-latin';
                            $transliterator = self::$transliterators[$rule] ??= \Transliterator::create($rule);
                        }

                        if (null === $transliterator) {
                            throw new InvalidArgumentException(\sprintf('Unknown transliteration rule "%s".', $rule));
                        }

                        self::$transliterators['any-latin/bgn'] = $transliterator;
                    }

                    $s = $transliterator->transliterate($s);
                }
            } elseif (!\function_exists('iconv')) {
                $s = preg_replace('/[^\x00-\x7F]/u', '?', $s);
            } else {
                $previousLocale = setlocale(\LC_CTYPE, 0);
                try {
                    setlocale(\LC_CTYPE, 'C');
                    $s = @preg_replace_callback('/[^\x00-\x7F]/u', static function ($c) {
                        $c = (string) iconv('UTF-8', 'ASCII//TRANSLIT', $c[0]);

                        if ('' === $c && '' === iconv('UTF-8', 'ASCII//TRANSLIT', '²')) {
                            throw new \LogicException(\sprintf('"%s" requires a translit-able iconv implementation, try installing "gnu-libiconv" if you\'re using Alpine Linux.', static::class));
                        }

                        return 1 < \strlen($c) ? ltrim($c, '\'`"^~') : ('' !== $c ? $c : '?');
                    }, $s);
                } finally {
                    setlocale(\LC_CTYPE, $previousLocale);
                }
            }
        }

        $str->string .= $s;

        return $str;
    }

    public function camel(): static
    {
        $str = clone $this;
        $str->string = str_replace(' ', '', preg_replace_callback('/\b.(?!\p{Lu})/u', static function ($m) {
            static $i = 0;

            return 1 === ++$i ? ('İ' === $m[0] ? 'i̇' : mb_strtolower($m[0], 'UTF-8')) : mb_convert_case($m[0], \MB_CASE_TITLE, 'UTF-8');
        }, preg_replace('/[^\pL0-9]++/u', ' ', $this->string)));

        return $str;
    }

    /**
     * @return int[]
     */
    public function codePointsAt(int $offset): array
    {
        $str = $this->slice($offset, 1);

        if ('' === $str->string) {
            return [];
        }

        $codePoints = [];

        foreach (preg_split('//u', $str->string, -1, \PREG_SPLIT_NO_EMPTY) as $c) {
            $codePoints[] = mb_ord($c, 'UTF-8');
        }

        return $codePoints;
    }

    public function folded(bool $compat = true): static
    {
        $str = clone $this;

        if (!$compat || !\defined('Normalizer::NFKC_CF')) {
            $str->string = normalizer_normalize($str->string, $compat ? \Normalizer::NFKC : \Normalizer::NFC);
            $str->string = mb_strtolower(str_replace(self::FOLD_FROM, self::FOLD_TO, $str->string), 'UTF-8');
        } else {
            $str->string = normalizer_normalize($str->string, \Normalizer::NFKC_CF);
        }

        return $str;
    }

    public function join(array $strings, ?string $lastGlue = null): static
    {
        $str = clone $this;

        $tail = null !== $lastGlue && 1 < \count($strings) ? $lastGlue.array_pop($strings) : '';
        $str->string = implode($this->string, $strings).$tail;

        if (!preg_match('//u', $str->string)) {
            throw new InvalidArgumentException('Invalid UTF-8 string.');
        }

        return $str;
    }

    public function lower(): static
    {
        $str = clone $this;
        $str->string = mb_strtolower(str_replace('İ', 'i̇', $str->string), 'UTF-8');

        return $str;
    }

    public function match(string $regexp, int $flags = 0, int $offset = 0): array
    {
        $match = ((\PREG_PATTERN_ORDER | \PREG_SET_ORDER) & $flags) ? 'preg_match_all' : 'preg_match';

        if ($this->ignoreCase) {
            $regexp .= 'i';
        }

        set_error_handler(static fn ($t, $m) => throw new InvalidArgumentException($m));

        try {
            if (false === $match($regexp.'u', $this->string, $matches, $flags | \PREG_UNMATCHED_AS_NULL, $offset)) {
                throw new RuntimeException('Matching failed with error: '.preg_last_error_msg());
            }
        } finally {
            restore_error_handler();
        }

        return $matches;
    }

    public function normalize(int $form = self::NFC): static
    {
        if (!\in_array($form, [self::NFC, self::NFD, self::NFKC, self::NFKD])) {
            throw new InvalidArgumentException('Unsupported normalization form.');
        }

        $str = clone $this;
        normalizer_is_normalized($str->string, $form) ?: $str->string = normalizer_normalize($str->string, $form);

        return $str;
    }

    public function padBoth(int $length, string $padStr = ' '): static
    {
        if ('' === $padStr || !preg_match('//u', $padStr)) {
            throw new InvalidArgumentException('Invalid UTF-8 string.');
        }

        $pad = clone $this;
        $pad->string = $padStr;

        return $this->pad($length, $pad, \STR_PAD_BOTH);
    }

    public function padEnd(int $length, string $padStr = ' '): static
    {
        if ('' === $padStr || !preg_match('//u', $padStr)) {
            throw new InvalidArgumentException('Invalid UTF-8 string.');
        }

        $pad = clone $this;
        $pad->string = $padStr;

        return $this->pad($length, $pad, \STR_PAD_RIGHT);
    }

    public function padStart(int $length, string $padStr = ' '): static
    {
        if ('' === $padStr || !preg_match('//u', $padStr)) {
            throw new InvalidArgumentException('Invalid UTF-8 string.');
        }

        $pad = clone $this;
        $pad->string = $padStr;

        return $this->pad($length, $pad, \STR_PAD_LEFT);
    }

    public function replaceMatches(string $fromRegexp, string|callable $to): static
    {
        if ($this->ignoreCase) {
            $fromRegexp .= 'i';
        }

        if (\is_array($to) || $to instanceof \Closure) {
            $replace = 'preg_replace_callback';
            $to = static function (array $m) use ($to): string {
                $to = $to($m);

                if ('' !== $to && (!\is_string($to) || !preg_match('//u', $to))) {
                    throw new InvalidArgumentException('Replace callback must return a valid UTF-8 string.');
                }

                return $to;
            };
        } elseif ('' !== $to && !preg_match('//u', $to)) {
            throw new InvalidArgumentException('Invalid UTF-8 string.');
        } else {
            $replace = 'preg_replace';
        }

        set_error_handler(static fn ($t, $m) => throw new InvalidArgumentException($m));

        try {
            if (null === $string = $replace($fromRegexp.'u', $to, $this->string)) {
                $lastError = preg_last_error();

                foreach (get_defined_constants(true)['pcre'] as $k => $v) {
                    if ($lastError === $v && str_ends_with($k, '_ERROR')) {
                        throw new RuntimeException('Matching failed with '.$k.'.');
                    }
                }

                throw new RuntimeException('Matching failed with unknown error code.');
            }
        } finally {
            restore_error_handler();
        }

        $str = clone $this;
        $str->string = $string;

        return $str;
    }

    public function reverse(): static
    {
        $str = clone $this;
        $str->string = implode('', array_reverse(preg_split('/(\X)/u', $str->string, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY)));

        return $str;
    }

    public function snake(): static
    {
        $str = $this->camel();
        $str->string = mb_strtolower(preg_replace(['/(\p{Lu}+)(\p{Lu}\p{Ll})/u', '/([\p{Ll}0-9])(\p{Lu})/u'], '\1_\2', $str->string), 'UTF-8');

        return $str;
    }

    public function title(bool $allWords = false): static
    {
        $str = clone $this;

        $limit = $allWords ? -1 : 1;

        $str->string = preg_replace_callback('/\b./u', static fn (array $m): string => mb_convert_case($m[0], \MB_CASE_TITLE, 'UTF-8'), $str->string, $limit);

        return $str;
    }

    public function trim(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): static
    {
        if (" \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}" !== $chars && !preg_match('//u', $chars)) {
            throw new InvalidArgumentException('Invalid UTF-8 chars.');
        }
        $chars = preg_quote($chars);

        $str = clone $this;
        $str->string = preg_replace("{^[$chars]++|[$chars]++$}uD", '', $str->string);

        return $str;
    }

    public function trimEnd(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): static
    {
        if (" \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}" !== $chars && !preg_match('//u', $chars)) {
            throw new InvalidArgumentException('Invalid UTF-8 chars.');
        }
        $chars = preg_quote($chars);

        $str = clone $this;
        $str->string = preg_replace("{[$chars]++$}uD", '', $str->string);

        return $str;
    }

    public function trimPrefix($prefix): static
    {
        if (!$this->ignoreCase) {
            return parent::trimPrefix($prefix);
        }

        $str = clone $this;

        if ($prefix instanceof \Traversable) {
            $prefix = iterator_to_array($prefix, false);
        } elseif ($prefix instanceof parent) {
            $prefix = $prefix->string;
        }

        $prefix = implode('|', array_map('preg_quote', (array) $prefix));
        $str->string = preg_replace("{^(?:$prefix)}iuD", '', $this->string);

        return $str;
    }

    public function trimStart(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): static
    {
        if (" \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}" !== $chars && !preg_match('//u', $chars)) {
            throw new InvalidArgumentException('Invalid UTF-8 chars.');
        }
        $chars = preg_quote($chars);

        $str = clone $this;
        $str->string = preg_replace("{^[$chars]++}uD", '', $str->string);

        return $str;
    }

    public function trimSuffix($suffix): static
    {
        if (!$this->ignoreCase) {
            return parent::trimSuffix($suffix);
        }

        $str = clone $this;

        if ($suffix instanceof \Traversable) {
            $suffix = iterator_to_array($suffix, false);
        } elseif ($suffix instanceof parent) {
            $suffix = $suffix->string;
        }

        $suffix = implode('|', array_map('preg_quote', (array) $suffix));
        $str->string = preg_replace("{(?:$suffix)$}iuD", '', $this->string);

        return $str;
    }

    public function upper(): static
    {
        $str = clone $this;
        $str->string = mb_strtoupper($str->string, 'UTF-8');

        return $str;
    }

    public function width(bool $ignoreAnsiDecoration = true): int
    {
        $width = 0;
        $s = str_replace(["\x00", "\x05", "\x07"], '', $this->string);

        if (str_contains($s, "\r")) {
            $s = str_replace(["\r\n", "\r"], "\n", $s);
        }

        if (!$ignoreAnsiDecoration) {
            $s = preg_replace('/[\p{Cc}\x7F]++/u', '', $s);
        }

        foreach (explode("\n", $s) as $s) {
            if ($ignoreAnsiDecoration) {
                $s = preg_replace('/(?:\x1B(?:
                    \[ [\x30-\x3F]*+ [\x20-\x2F]*+ [\x40-\x7E]
                    | [P\]X^_] .*? \x1B\\\\
                    | [\x41-\x7E]
                )|[\p{Cc}\x7F]++)/xu', '', $s);
            }

            $lineWidth = $this->wcswidth($s);

            if ($lineWidth > $width) {
                $width = $lineWidth;
            }
        }

        return $width;
    }

    private function pad(int $len, self $pad, int $type): static
    {
        $sLen = $this->length();

        if ($len <= $sLen) {
            return clone $this;
        }

        $padLen = $pad->length();
        $freeLen = $len - $sLen;
        $len = $freeLen % $padLen;

        switch ($type) {
            case \STR_PAD_RIGHT:
                return $this->append(str_repeat($pad->string, intdiv($freeLen, $padLen)).($len ? $pad->slice(0, $len) : ''));

            case \STR_PAD_LEFT:
                return $this->prepend(str_repeat($pad->string, intdiv($freeLen, $padLen)).($len ? $pad->slice(0, $len) : ''));

            case \STR_PAD_BOTH:
                $freeLen /= 2;

                $rightLen = ceil($freeLen);
                $len = $rightLen % $padLen;
                $str = $this->append(str_repeat($pad->string, intdiv($rightLen, $padLen)).($len ? $pad->slice(0, $len) : ''));

                $leftLen = floor($freeLen);
                $len = $leftLen % $padLen;

                return $str->prepend(str_repeat($pad->string, intdiv($leftLen, $padLen)).($len ? $pad->slice(0, $len) : ''));

            default:
                throw new InvalidArgumentException('Invalid padding type.');
        }
    }

    /**
     * Based on https://github.com/jquast/wcwidth, a Python implementation of https://www.cl.cam.ac.uk/~mgk25/ucs/wcwidth.c.
     */
    private function wcswidth(string $string): int
    {
        $width = 0;
        $lastChar = null;
        $lastWidth = null;

        foreach (preg_split('//u', $string, -1, \PREG_SPLIT_NO_EMPTY) as $c) {
            $codePoint = mb_ord($c, 'UTF-8');

            if (0 === $codePoint // NULL
                || 0x034F === $codePoint // COMBINING GRAPHEME JOINER
                || (0x200B <= $codePoint && 0x200F >= $codePoint) // ZERO WIDTH SPACE to RIGHT-TO-LEFT MARK
                || 0x2028 === $codePoint // LINE SEPARATOR
                || 0x2029 === $codePoint // PARAGRAPH SEPARATOR
                || (0x202A <= $codePoint && 0x202E >= $codePoint) // LEFT-TO-RIGHT EMBEDDING to RIGHT-TO-LEFT OVERRIDE
                || (0x2060 <= $codePoint && 0x2063 >= $codePoint) // WORD JOINER to INVISIBLE SEPARATOR
            ) {
                continue;
            }

            // Non printable characters
            if (32 > $codePoint // C0 control characters
                || (0x07F <= $codePoint && 0x0A0 > $codePoint) // C1 control characters and DEL
            ) {
                return -1;
            }

            if (0xFE0F === $codePoint) {
                if (null !== $lastChar && 1 === $lastWidth && preg_match('/\p{Emoji}/u', $lastChar)) {
                    ++$width;
                    $lastWidth = 2;
                }

                continue;
            }

            self::$tableZero ??= require __DIR__.'/Resources/data/wcswidth_table_zero.php';

            if ($codePoint >= self::$tableZero[0][0] && $codePoint <= self::$tableZero[$ubound = \count(self::$tableZero) - 1][1]) {
                $lbound = 0;
                while ($ubound >= $lbound) {
                    $mid = floor(($lbound + $ubound) / 2);

                    if ($codePoint > self::$tableZero[$mid][1]) {
                        $lbound = $mid + 1;
                    } elseif ($codePoint < self::$tableZero[$mid][0]) {
                        $ubound = $mid - 1;
                    } else {
                        continue 2;
                    }
                }
            }

            self::$tableWide ??= require __DIR__.'/Resources/data/wcswidth_table_wide.php';

            if ($codePoint >= self::$tableWide[0][0] && $codePoint <= self::$tableWide[$ubound = \count(self::$tableWide) - 1][1]) {
                $lbound = 0;
                while ($ubound >= $lbound) {
                    $mid = floor(($lbound + $ubound) / 2);

                    if ($codePoint > self::$tableWide[$mid][1]) {
                        $lbound = $mid + 1;
                    } elseif ($codePoint < self::$tableWide[$mid][0]) {
                        $ubound = $mid - 1;
                    } else {
                        $width += 2;
                        $lastChar = $c;
                        $lastWidth = 2;

                        continue 2;
                    }
                }
            }

            ++$width;
            $lastChar = $c;
            $lastWidth = 1;
        }

        return $width;
    }
}
