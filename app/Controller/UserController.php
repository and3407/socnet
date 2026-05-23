<?php

namespace App\Controller;

use App\Domain\Post\Usecases\GetUserPosts;
use App\Domain\User\Dto\CreateUserDto;
use App\Domain\User\Models\User;
use App\Domain\User\Usecases\CreateUser;
use App\Domain\User\Usecases\SearchUsers;
use App\Domain\Counter\CounterServiceClient;
use App\Requests\User\UserGetRequest;
use App\Requests\User\UserPostsRequest;
use App\Requests\User\UserRegisterRequest;
use App\Requests\User\UserSearchRequest;
use App\Responses\UserGetResponse;
use App\Responses\UserPostsResponse;
use App\Responses\UserRegisterResponse;
use App\Responses\UserSearchResponse;

class UserController extends Controller
{
    public function userRegister(): void
    {
        $data = new UserRegisterRequest()->validation();

        $dto = CreateUserDto::createDto($data);

        $user = CreateUser::createUsecase()->createUser($dto);

        UserRegisterResponse::create($user)->json();
    }

    public function userGet(): void
    {
        $data = new UserGetRequest()->validation();

        UserGetResponse::create($data['user'])->json();
    }

    /**
     * @throws \JsonException
     */
    public function userSearch(): void
    {
        $params = new UserSearchRequest()->validation();

        $users = new SearchUsers()->searchByName($params['first_name'], $params['second_name']);

        UserSearchResponse::create($users)->json();
    }

    public function getUserPosts(): void
    {
        $params = new UserPostsRequest()->validation();

        /** @var User $user */
        $user = $params['user'];

        $posts = new GetUserPosts()->getCacheWithPagination($user->id, $params['page']);

        UserPostsResponse::create($user)->json($posts);
    }

    /**
     * Get total unread messages count for user
     */
    public function getUnreadCount(): void
    {
        // Extract userId from URL path
        $requestUri = $_SERVER['REQUEST_URI'];
        // Pattern: /user/{userId}/unread-count
        if (preg_match('#^/user/([^/]+)/unread-count$#', $requestUri, $matches)) {
            $userId = $matches[1];
        } else {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid URL pattern']);
            return;
        }

        $counterClient = new CounterServiceClient();
        $totalUnread = $counterClient->getTotalUnread($userId);

        header('Content-Type: application/json');
        echo json_encode(['total_unread' => $totalUnread]);
    }
}