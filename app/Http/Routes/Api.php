<?php
declare(strict_types=1);

/** @var \Laravel\Lumen\Routing\Router $router */

// MailChimp group
$router->group(['prefix' => 'mailchimp', 'namespace' => 'MailChimp'], function () use ($router) {
    // Lists group
    $router->group(['prefix' => 'lists'], function () use ($router) {
        $router->post('/', 'ListsController@create');
        $router->get('/{listId}', 'ListsController@show');
        $router->put('/{listId}', 'ListsController@update');
        $router->delete('/{listId}', 'ListsController@remove');

        //List all the members from a specific list
        $router->get('/{listId}/members', 'ListsController@listMembers');
        
    });

    //Member group
    $router->group(['prefix' => 'members'], function () use ($router) {
        //Adding member to a list
        $router->post('/list/{listId}', 'MembersController@add');
        // update a member info from a list
        $router->patch('/{memberId}/list/{listId}', 'MembersController@update');
        //remove a member from a list
        $router->delete('{memberId}/list/{listId}/{hash}', 'ListsController@unsubscribe');
    });
});
