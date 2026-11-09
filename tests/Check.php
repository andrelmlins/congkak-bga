<?php

/**
 * Tiny helpers shared by the plain-PHP tests (no framework, no PHPUnit). Run all of them with:
 * `for t in tests/*Test.php; do php "$t" || exit 1; done`
 */

declare(strict_types=1);

$failures = 0;
$checks = 0;

function check(string $what, bool $ok): void
{
    global $failures, $checks;
    $checks++;
    if (!$ok) {
        $failures++;
        echo "FAIL: $what\n";
    }
}

/** Compares with `==` after normalizing int and string keys, and prints both sides when they differ. */
function checkSame(string $what, $expected, $actual): void
{
    $ok = $expected == $actual;
    check($what, $ok);
    if (!$ok) {
        echo '  expected: ' . json_encode($expected) . "\n  actual:   " . json_encode($actual) . "\n";
    }
}

function finish(string $name): void
{
    global $failures, $checks;
    echo $failures === 0 ? "OK $name ($checks checks)\n" : "$name: $failures of $checks checks failed\n";
    exit($failures === 0 ? 0 : 1);
}

/** A board with the given seeds in kampongs 1 to 7 of each player, and the rumahs, everything else empty. */
function board(array $kampongs, array $rumah = []): array
{
    $houses = [];
    foreach ($kampongs as $playerId => $seeds) {
        $houses[$playerId] = ['kampong' => array_combine(range(1, 7), $seeds), 'rumah' => $rumah[$playerId] ?? 0];
    }

    return $houses;
}
