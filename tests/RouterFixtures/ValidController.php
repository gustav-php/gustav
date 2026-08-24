<?php

namespace GustavPHP\Tests\RouterFixtures;

use GustavPHP\Gustav\Attribute\{Controller, Csrf, Get, Param, Post};

#[Controller('/blog')]
#[Csrf]
final class ValidController
{
    /** @return array{} */
    #[Get('/authors')]
    public function authors(): array
    {
        return [];
    }

    /** @return array{} */
    #[Get('/{post}/comments/{comment}', name: 'blog.comments.show')]
    public function comment(
        #[Param('post')] string $post,
        #[Param('comment')] string $comment,
    ): array {
        return [];
    }

    /** @return array{} */
    #[Post]
    public function create(): array
    {
        return [];
    }
    /** @return array{} */
    #[Get(name: 'blog.index')]
    public function index(): array
    {
        return [];
    }

    /** @return array{} */
    #[Get('/{post}', name: 'blog.show')]
    public function show(#[Param('post')] string $post): array
    {
        return [];
    }
}
