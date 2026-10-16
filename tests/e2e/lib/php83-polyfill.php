<?php
// Only used when the machine running these checks has PHP older than 8.4 (the app itself needs 8.4).
// Symfony 8 calls request_parse_body() for PUT/PATCH/DELETE forms; older PHP does not have it.
if (!function_exists('request_parse_body')) {
    function request_parse_body(?array $options = null): array
    {
        parse_str((string) file_get_contents('php://input'), $post);
        return [$post, []];
    }
}
