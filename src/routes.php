<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

//$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
//    // Sample log message
//    $this->logger->info("Slim-Skeleton '/' route");
//
//    // Render index view
//    return $this->renderer->render($response, 'index.phtml', $args);
//});


$app->post('/user/auth', function (Request $request, Response $response, array  $args) {
    $user = ORM::for_table('users')->where(array(
        'login' => $request->getParsedBody()['login'],
        'password' => md5($request->getParsedBody()['password'])
    ))->find_one();
    if ($user) {
        return $response->withJson($user->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->post('/client/auth', function (Request $request, Response $response, array  $args) {
    $user = ORM::for_table('clients')->where(array(
        'email' => $request->getParsedBody()['email'],
        'password' => md5($request->getParsedBody()['password'])
    ))->find_one();
    if ($user) {
        return $response->withJson($user->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->post('/client/reg', function (Request $request, Response $response, array  $args) {
    $user = ORM::for_table('clients')->create();
    $user->name = $request->getParsedBody()['name'];
    $user->email = $request->getParsedBody()['email'];
    $user->password = md5($request->getParsedBody()['password']);
    $user->date_of_birth = $request->getParsedBody()['date_of_birth'];
    $user->pass_num = $request->getParsedBody()['pass_num'];
    $user->pass_country = $request->getParsedBody()['pass_country'];
    $user->pass_issue = $request->getParsedBody()['pass_issue'];
    $user->pass_exp = $request->getParsedBody()['pass_exp'];
    $user->pass_authority = $request->getParsedBody()['pass_authority'];
    $user->place_of_birth = $request->getParsedBody()['place_of_birth'];
    $user->fraud_scoring = 0;
    $user->save();
    if ($user) {
        return $response->withJson($user->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->post('/loan/create', function (Request $request, Response $response, array  $args) {
    $loan = ORM::forTable('loans')->create();
    $loan->client_id = $request->getParsedBody()['client_id'];
    $loan->sum = $request->getParsedBody()['sum'];
    $loan->sum_to_return = $request->getParsedBody()['sum_to_return'];
    $loan->date_issue = $request->getParsedBody()['date_issue'];
    $loan->date_exp = $request->getParsedBody()['date_exp'];
    $loan->save();
    if ($loan) {
        return $response->withJson($loan->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->get('/client/{client_id}/loans', function (Request $request, Response $response, array $args) {
    $array = array();
    $loans = ORM::forTable('loans')->where('client_id', $args['client_id'])->orderByDesc('id')->find_result_set();
    foreach ($loans as $loan) {
        $temp = $loan->as_array();
        $loan_history = ORM::forTable('loan_history')->where('loan_id', $loan->id)->orderByDesc('id')->findArray();
        $loan_transactions = ORM::forTable('loan_transactions')->where('loan_id', $loan->id)->orderByDesc('id')->findArray();
        $temp["loan_history"] = $loan_history;
        $temp["loan_transactions"] = $loan_transactions;
        $array[] = $temp;
    }
    return $response->withJson($array);
});

$app->post('/client/{client_id}/loans/{loan_id}/addPayment', function (Request $request, Response $response, array $args) {
    $transaction = ORM::forTable('loan_transactions')->create();
    $transaction->loan_id = $args['loan_id'];
    $transaction->is_inside = "1";
    $transaction->nonce = $request->getParsedBody()['nonce'];
    $transaction->sum = $request->getParsedBody()['sum'];
    $transaction->date = $request->getParsedBody()['date'];
    $transaction->save();
    if ($transaction) {
        return $response->withJson($transaction->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->get('/client/{client_id}', function (Request $request, Response $response, array $args) {
    $client = ORM::forTable('clients')->where('id', $args['client_id'])->findOne();
    if ($client) {
        return $response->withJson($client->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->get('/user/{user_id}/get', function (Request $request, Response $response, array $args) {
    $client = ORM::forTable('users')->where('id', $args['user_id'])->findOne();
    if ($client) {
        return $response->withJson($client->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->get('/user/loans', function (Request $request, Response $response, array $args) {
    $array = array();
    $loans = ORM::forTable('loans')->limit(200)->orderByDesc('id')->find_result_set();
    foreach ($loans as $loan) {
        $temp = $loan->as_array();
        $loan_history = ORM::forTable('loan_history')->where('loan_id', $loan->id)->limit(200)->orderByDesc('id')->findArray();
        $loan_transactions = ORM::forTable('loan_transactions')->where('loan_id', $loan->id)->limit(200)->orderByDesc('id')->findArray();
        $client = ORM::forTable('clients')->where('id', $loan->client_id)->findOne()->as_array();
        $temp["loan_history"] = $loan_history;
        $temp["loan_transactions"] = $loan_transactions;
        $temp["client"]= $client;
        $array[] = $temp;
    }
    return $response->withJson($array);
});

$app->get('/support', function (Request $request, Response $response, array $args) {
    $array = array();
    $supports = ORM::forTable('support')->orderByAsc('name')->findResultSet();
    foreach ($supports as $support) {
        $temp = $support->as_array();
        $temp['count_tickets'] = ORM::forTable('support_tickets')->where('support_id', $support->id)->count();
        $array[] = $temp;
    }
    return $response->withJson($array);
});

$app->get('/support/{support_id}/tickets', function (Request $request, Response $response, array $args) {
    $array = array();
    $tickets = ORM::forTable('support_tickets')->where('support_id', $args['support_id'])->orderByDesc('id')->findResultSet();
    foreach ($tickets as $ticket) {
        $temp = $ticket->as_array();
        $temp['client'] = ORM::forTable('clients')->where('id', $ticket->client_id)->find_one()->as_array();
        $array[] = $temp;
    }
    return $response->withJson($array);
});

$app->post('/support/add', function (Request $request, Response $response, array $args) {
   $support = ORM::forTable('support')->create();
   $support->name = $request->getParsedBody()['name'];
   $support->description = $request->getParsedBody()['description'];
   $support->save();
    if ($support) {
        return $response->withJson($support->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});

$app->get('/client/{client_id}/support', function (Request $request, Response $response, array $args) {
    $array = array();
    $support_tickets = ORM::forTable('support_tickets')->where('client_id',$args['client_id'])->orderByDesc('id')->findResultSet();
    foreach ($support_tickets as $ticket) {
        $temp = $ticket->as_array();
        $temp['count_answers'] = ORM::forTable('support_answers')->where('support_ticket_id', $ticket->id)->count();
        $support_name = ORM::forTable('support')->select('name')->where('id', $ticket->support_id)->find_one();
        $temp['support_name'] = $support_name->name;
        $array[] = $temp;
    }
    return $response->withJson($array);
});

$app->post('/client/{client_id}/support/{support_id}/add', function (Request $request, Response $response, array $args) {
    $support_ticket = ORM::forTable('support_tickets')->create();
    $support_ticket->support_id = $args['support_id'];
    $support_ticket->client_id = $args['client_id'];
    $support_ticket->text = $request->getParsedBody()['text'];
    $support_ticket->date_issue = $request->getParsedBody()['date_issue'];
    $support_ticket->save();
    if ($support_ticket) {
        return $response->withJson($support_ticket->as_array());
    } else {
        $newResponse = $response->withStatus(400);
        return $newResponse->withJson(array("status"=> "failed"));
    }
});