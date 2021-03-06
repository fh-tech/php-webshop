<?php

use PDOs\coupon\CouponDao;
use PDOs\User\NewUser;
use PDOs\User\UserDAO;
use Slim\Http\Request;
use Slim\Http\Response;


// Routes
$app->get('/user/', function (Request $request, Response $response, array $args) {
    $this->logger->info("GET: all users");
    $repo = new UserDAO($this->db);
    $user = $repo->getAll();
    if($user) {
        return $response
            ->withStatus(200)
            ->withJson(["success" => $user]);
    } else {
        throw new \errors\HttpServerException(404, "Users not found");
    }
})->add(new ScopedJWTAuth(["admin"]));

$app->get('/user/{id}', function (Request $request, Response $response, array $args) {

    $this->logger->info("GET: user with id " . $args['id']);
    $repo = new UserDAO($this->db);
    $user = $repo->byId($args['id']);
    if($user) {
        return $response
            ->withStatus(200)
            ->withJson(["success" => $user]);
    }else{
        throw new \errors\HttpServerException(404, "Users not found");
    }
})->add(new ScopedJWTAuth(["user"]));

$app->post("/user/", function (Request $request, Response $response, array $args) {
    $this->logger->info("POST: creating new user...");
    $body = $request->getParsedBody();

    if(!isset($body['user'])) {
        throw new \errors\HttpServerException(400, "Malformed request: Missing field user");
    }
    $repo = new \PDOs\User\UserDAO($this->db);

    $newUser = \PDOs\JsonMapper::map(NewUser::class, $body['user']);
    if($newUser->validate()) {
        $userId = $repo->insertNew($newUser);

        return $response
            ->withStatus(200)
            ->withJson(["success" => ["id" => $userId]]);
    }else{
        throw new \errors\HttpServerException(400, "Not a valid user format!");
    }
})->add(new ScopedJWTAuth(["anonymous"]));
/**
 *
 */
$app->put("/user/{id}", function (Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    $body = $request->getParsedBody();
    if(!isset($body['user'])) {
        throw new \errors\HttpServerException(400, "Malformed request: Missing field user");
    }
    $updateUser = \PDOs\JsonMapper::map("\PDOs\User\UpdateUser", $body["user"]);
    $updateUser->id = $args['id'];

    if($token->decoded['sub'] != $updateUser->id && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }

    $dao = new UserDAO($this->db);
    $dao->updateUser($updateUser);
    return $response
        ->withStatus(200)
        ->withJson(["success" => ["id" => $updateUser->id]]);
})->add(new ScopedJWTAuth(["user"]));
/**
 *
 */
$app->get("/user/{id}/paymentMethod/", function(Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    if($token->decoded['sub'] != $args['id'] && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }
    $dao = new \PDOs\User\PaymentMethodDao($this->db);
    $payment_methods = $dao->getForUser($args['id']);

    return $response->withStatus(200)
        ->withJson(["success" => $payment_methods]);

})->add(new ScopedJWTAuth(["user"]));
/**
 *
 */
$app->post("/user/{id}/paymentMethod/", function(Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    if($token->decoded['sub'] != $args['id'] && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }
    $dao = new \PDOs\User\PaymentMethodDao($this->db);

    $method = \PDOs\JsonMapper::map("\PDOs\User\PaymentMethod", $request->getParsedBody()['paymentMethod']);

    $id = $dao->insertNew($args['id'], $method);

    return $response
        ->withStatus(200)
        ->withJson(["success" => ["id" => $id]]);
});
/**
 *
 */
$app->delete("/user/{uid}/paymentMethod/{id}", function(Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    if($token->decoded['sub'] != $args['uid'] && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }
    $dao = new \PDOs\User\PaymentMethodDao($this->db);
    $dao->delete($args['uid'], $args['id']);

    return $response
        ->withStatus(200)
        ->withJson(["success" => []]);
});
/**
 *
 */
$app->get("/user/{id}/coupon/", function(Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    if($token->decoded['sub'] != $args['id'] && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }
    $dao = new CouponDao($this->db);
    $coupons = $dao->getAllForUser($args['id']);

    return $response->withStatus(200)
        ->withJson(["success" => $coupons]);

})->add(new ScopedJWTAuth(["user"]));
/**
 *
 */
$app->get("/user/{id}/invoice/",  function(Request $request, Response $response, array $args){
    $token = $request->getAttribute("token");
    if($token->decoded['sub'] != $args['id'] && !$token->has_scope(["admin"])){
        throw new \errors\HttpServerException(403, "Token does not have rights for this resource instance");
    }

    $invoiceDao = new \PDOs\order\InvoiceDao($this->db);
    $invoices = $invoiceDao->byUser(intval($args['id']));
    $invoices = $invoiceDao->getWithProduct($invoices, new \PDOs\product\ProductDao($this->db));
    return $response
        ->withStatus(200)
        ->withJson(["success" => $invoices]);

})->add(new ScopedJWTAuth(['user']));
/**
 *
 */
$app->put("/user/{id}/activate/", function (Request $request, Response $response, array $args){

    $dao = new UserDAO($this->db);
    $dao->activate(intval($args['id']));
    return $response
        ->withStatus(204);

})->add(new ScopedJWTAuth(["admin"]));
/**
 *
 */
$app->put("/user/{id}/deactivate/", function (Request $request, Response $response, array $args){

    $dao = new UserDAO($this->db);
    $dao->deactivate(intval($args['id']));
    return $response
        ->withStatus(204);

})->add(new ScopedJWTAuth(["admin"]));