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

test("Register via method", function () {
    $response = cURLCall("get", "/books/jane-doe/223658");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("Author: jane-doe ID: 223658");
});

test("Get request with query string", function () {
    $response = cURLCall("get", "/search/1222/wddwdwd/?q=test&limit=2");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("URI Template test");
});

test("Get request with query string 404", function () {
    $response = cURLCall("get", "/search/w/wddwdwd/?q=test2");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(404);
});
