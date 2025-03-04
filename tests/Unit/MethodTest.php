<?php

use AOWD\Router;

function cURLCall(string $method = "get", string $endpoint = "/"): array
{
    $ch = curl_init("http://localhost:50967" . $endpoint);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HEADER => true,
        CURLOPT_HEADER => false,
    ]);

    $response = curl_exec($ch);
    $header = curl_getinfo($ch);

    curl_close($ch);

    return [
        "response" => $response,
        "headers" => $header,
    ];
}

test("Not Set GET, PUT, POST and DELETE", function () {
    $router = new Router();
    expect($router->checkRoute("get", "/test"))->toBeFalse();
    expect($router->checkRoute("put", "/test"))->toBeFalse();
    expect($router->checkRoute("post", "/test"))->toBeFalse();
    expect($router->checkRoute("delete", "/test"))->toBeFalse();
});

test("Register GET, PUT, POST and DELETE", function () {
    $router = new Router();
    $router->register("get", "/get", fn() => "foo");
    $router->register("put", "/put", fn() => "foo");
    $router->register("post", "/post", fn() => "foo");
    $router->register("delete", "/delete", fn() => "foo");

    expect($router->checkRoute("get", "/get"))->toBeCallable();
    expect($router->checkRoute("put", "/put"))->toBeCallable();
    expect($router->checkRoute("post", "/post"))->toBeCallable();
    expect($router->checkRoute("delete", "/delete"))->toBeCallable();
});

test("Endpoint with trailing slash", function () {
    $router = new Router();
    $router->register("get", "/get/", fn() => "foo");

    expect($router->checkRoute("get", "/get"))->toBeCallable();

    $response_get = cURLCall("get", "/test/");
    expect($response_get["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response_get["response"])->toBe("get test");
});

test("Unregister Route", function () {
    $router = new Router();
    $router->register("get", "/get", fn() => "foo");
    $result = $router->unregister("get", "/get");

    expect($result)->toBeTrue();
});

test("200 GET, PUT, POST and DELETE", function () {
    $response_get = cURLCall("get", "/test");
    $response_put = cURLCall("put", "/test");
    $response_post = cURLCall("post", "/test");
    $response_delete = cURLCall("delete", "/test");

    expect($response_get["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response_get["response"])->toBe("get test");

    expect($response_put["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response_put["response"])->toBe("put test");

    expect($response_post["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response_post["response"])->toBe("post test");

    expect($response_delete["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response_delete["response"])->toBe("delete test");
});

test("404 GET, PUT, POST and DELETE", function () {
    $response_get = cURLCall("get", "/get");
    $response_put = cURLCall("put", "/put");
    $response_post = cURLCall("post", "/post");
    $response_delete = cURLCall("delete", "/delete");

    expect($response_get["headers"]["http_code"])->toBeInt()->ToBe(404);
    expect($response_put["headers"]["http_code"])->toBeInt()->ToBe(404);
    expect($response_post["headers"]["http_code"])->toBeInt()->ToBe(404);
    expect($response_delete["headers"]["http_code"])->toBeInt()->ToBe(404);
});

test("404 Error page content", function () {
    $response = cURLCall("get", "/");

    expect($response["headers"]["http_code"])->toBeInt()->ToBe(404);
    expect($response["response"])->toBe("page not found");
});

test("501 Test", function () {
    $response_1 = cURLCall("head", "/");
    $response_2 = cURLCall("patch", "/");

    expect($response_1["headers"]["http_code"])->toBeInt()->ToBe(501);
    expect($response_2["headers"]["http_code"])->toBeInt()->ToBe(501);
});

test("Query String", function () {
    $response = cURLCall("get", "/querystring?foo=bar");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("bar");
});

test("Regular Expression", function () {
    $response = cURLCall("get", "/test/13216255/foo");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("regex");
});

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
