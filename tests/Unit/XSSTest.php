<?php

use AOWD\Router;

test("Get safe query parameter", function () {
    $response = cURLCall("get", "/xss?q=<script>alert('XSS')</script>");
    expect($response["headers"]["http_code"])->toBeInt()->ToBe(200);
    expect($response["response"])->toBe("&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;");
});
