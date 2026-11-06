<?php

test('OpenAPI specificaties komt overeen met de snaphot', function () {
    exec('vendor/bin/openapi src -o tests/fixtures/openapi.yaml', $output, $return_var);
    $response = file_get_contents(__DIR__ . '/fixtures/openapi.yaml');
    expect($response)->toMatchSnapshot();
});
