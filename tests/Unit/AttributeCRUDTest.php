<?php

use AOWD\Router;

test("CRUD - GET hello world attribute", function () {
    $response = cURLCall("get", "/crud-hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("CRUD GET - Hello World");
});

test("CRUD - PUT hello world attribute", function () {
    $response = cURLCall("put", "/crud-hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("CRUD PUT - Hello World");
});

test("CRUD - POST hello world attribute", function () {
    $response = cURLCall("post", "/crud-hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("CRUD POST - Hello World");
});

test("CRUD - DELETE hello world attribute", function () {
    $response = cURLCall("delete", "/crud-hello-world");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("CRUD DELETE - Hello World");
});
