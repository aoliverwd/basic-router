<?php

use AOWD\Router;

test("GET hello world attribute", function () {
    $response = cURLCall("get", "/hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("GET - Hello World");
});

test("POST hello world attribute", function () {
    $response = cURLCall("post", "/hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("POST - Hello World");
});


test("Get second segment via attribute call", function () {
    $response = cURLCall("get", "/hello-world/segment/123");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("segment");
});

test("GET hello world attribute with middleware", function () {
    $response = cURLCall("get", "/hello-world-middleware");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("Hello World GET");
});

test("GET hello world attribute with multiple middleware", function () {
    $response = cURLCall("get", "/hello-world-multi-middleware");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("The best Hello World GET");
});
