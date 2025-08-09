<?php

use AOWD\Router;

test("Get segment", function () {
    $response = cURLCall("get", "/segment/123");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("segment");
});

test("Get last segment", function () {
    $response = cURLCall("get", "/last/segment/123");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("123");
});

test("Get second segment", function () {
    $response = cURLCall("get", "/second/segment/123");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("segment");
});
