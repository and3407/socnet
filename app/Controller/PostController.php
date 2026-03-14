<?php

namespace App\Controller;

use App\Domain\Post\Dto\CreatePostDto;
use App\Domain\Post\Usecases\CreatePost;
use App\Requests\Post\PostCreateRequest;
use App\Responses\PostCreateResponse;

class PostController extends Controller
{
    public function createPost(): void
    {
        $data = new PostCreateRequest()->validation();

        $dto = CreatePostDto::createDto($data);

        $postId = CreatePost::createUsecase()->createPost($dto);

        PostCreateResponse::create($postId)->json();
    }
}