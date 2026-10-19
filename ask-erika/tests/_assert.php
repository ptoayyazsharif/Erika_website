<?php
/** Tiny assertion helper shared by all test scripts. */
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function assert_true($cond, string $label): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  \033[32mPASS\033[0m  $label\n"; }
    else       { $GLOBALS['__fail']++; echo "  \033[31mFAIL\033[0m  $label\n"; }
}

function assert_eq($a, $b, string $label): void {
    assert_true($a === $b, $label . "  (got " . var_export($a, true) . ", want " . var_export($b, true) . ")");
}

function assert_summary(): void {
    $p = $GLOBALS['__pass']; $f = $GLOBALS['__fail'];
    echo "\n" . ($f === 0 ? "\033[32m" : "\033[31m") . "$p passed, $f failed\033[0m\n";
    if ($f > 0) exit(1);
}
