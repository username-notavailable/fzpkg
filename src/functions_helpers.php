<?php

function htmx_link_url(string $class, string $classMethod, array $params = []) {
    return route('fz_htmx_link', array_merge(['__fz_c__' => $class, '__fz_cm__' => $classMethod], $params));
}