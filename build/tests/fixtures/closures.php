<?php

// A class/function declared inside a closure body genuinely becomes a real
// global declaration once the closure runs - so, matching the legacy
// generator's behaviour, these are still collected rather than skipped.
// Over-inclusion in an exclude list is harmless; under-inclusion risks a
// real symbol collision.

call_user_func(function () {
    class Class_In_Closure
    {
    }
});

$fn = function () {
    function function_in_closure(): void
    {
    }
};

array_map(fn ($x) => $x, []);
