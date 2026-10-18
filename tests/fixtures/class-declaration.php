<?php

// Isolate a real class from controller/bootstrap I/O for offline constructor tests.
function regression_load_class($path, $name, $abstract = FALSE)
{
    $tokens = token_get_all(file_get_contents($path));
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_CLASS) { continue; }
        $next = $i+1;
        while (is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) { $next++; }
        if (!is_array($tokens[$next]) || $tokens[$next][1] !== $name) { continue; }
        $code = $abstract ? 'abstract ' : '';
        $depth = 0;
        $opened = FALSE;
        for (; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $code .= is_array($token) ? $token[1] : $token;
            if ($token === '{' || (is_array($token) && in_array($token[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), TRUE))) { $depth++; $opened = TRUE; }
            if ($token === '}') {
                $depth--;
                if ($opened && $depth === 0) { eval($code); return; }
            }
        }
    }
    throw new RuntimeException('Class declaration was not found: '.$name);
}
