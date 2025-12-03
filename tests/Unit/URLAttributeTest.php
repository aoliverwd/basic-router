<?php

use AOWD\Router;

test("User and order ID URL attributes", function () {
    $response = cURLCall("get", "/users/23/orders/55789");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("User: 23 Order: 55789");
});

test("User attribute ID fallback", function () {
    $response = cURLCall("get", "/users/23");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("User: foo");
});
