<?php

namespace PHPUnuhi\Components\Validator;

use PHPUnuhi\Bundles\Spelling\SpellCheckerInterface;
use PHPUnuhi\Models\Translation\TranslationSet;

class Validator
{

    /**
     * @var SpellCheckerInterface|null
     */
    private $spellChecker;

    /**
     * @param SpellCheckerInterface|null $spellChecker
     */
    public function __construct(?SpellCheckerInterface $spellChecker)
    {
        $this->spellChecker = $spellChecker;
    }


    /**
     * @param TranslationSet $set
     * @return bool
     */
    public function validate(TranslationSet $set): bool
    {
        $isValid = true;

        $allKeys = $set->getAllTranslationKeys();

        foreach ($set->getLocales() as $locale) {

            $localeKeys = $locale->getTranslationKeys();

            # verify if our current locale has the same structure
            # as our global suite keys list
            $structureValid = $this->isStructureEqual($localeKeys, $allKeys);

            if (!$structureValid) {

                echo "Found different structure in this file: " . PHP_EOL;
                echo "  - " . $locale->getFilename() . PHP_EOL;

                $filtered = $this->getDiff($localeKeys, $allKeys);

                foreach ($filtered as $key) {
                    echo '           [x]: ' . $key . PHP_EOL;
                }
                echo PHP_EOL;

                $isValid = false;
            }
        }


        foreach ($set->getLocales() as $locale) {
            foreach ($locale->getTranslations() as $translation) {

                if ($translation->isEmpty()) {
                    echo "Found empty translation in this file: " . PHP_EOL;
                    echo "  - " . $locale->getFilename() . PHP_EOL;
                    echo '           [x]: ' . $translation->getKey() . PHP_EOL;
                    echo PHP_EOL;
                    $isValid = false;
                }
            }
        }


        if ($this->spellChecker !== null) {
            foreach ($set->getLocales() as $locale) {
                foreach ($locale->getTranslations() as $translation) {

                    $isValid = $this->spellChecker->validateSpelling($translation->getValue(), $locale->getName());

                    if (!$isValid) {
                        echo "Found misspelled translation in this file: " . PHP_EOL;
                        echo "  - " . $locale->getFilename() . PHP_EOL;
                        echo '           [x]: ' . $translation->getKey() . ': ' . $translation->getValue() . PHP_EOL;
                        echo PHP_EOL;
                        $isValid = false;
                    }
                }
            }

        }
        return $isValid;
    }


    /**
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    private function isStructureEqual($a, $b)
    {
        return (is_array($b)
            && is_array($a)
            && count($a) == count($b)
            && array_diff($a, $b) === array_diff($b, $a)
        );
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @return array<mixed>
     */
    private function getDiff(array $a, array $b): array
    {
        $diffA = array_diff($a, $b);
        $diffB = array_diff($b, $a);

        return array_merge($diffA, $diffB);
    }
}
