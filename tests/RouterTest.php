<?php

use PHPUnit\Framework\TestCase;
use TorstenDittmann\Gustav\Method;
use TorstenDittmann\Gustav\Router;

final class RouterTest extends TestCase
{
    public function tearDown(): void
    {
        Router::reset();
    }

    public function testCanMatchUrl(): void
    {
        Router::addRoute(Method::GET, '/', 'index');
        Router::addRoute(Method::GET, '/about', 'about');
        Router::addRoute(Method::GET, '/about/me', 'about_me');

        $this->assertEquals([
            'identifier' => 'index',
        ], Router::match(Method::GET, '/'));
        $this->assertEquals([
            'identifier' => 'about',
        ], Router::match(Method::GET, '/about'));
        $this->assertEquals([
            'identifier' => 'about_me',
        ], Router::match(Method::GET, '/about/me'));
    }

    public function testCanMatchUrlWithPlaceholder(): void
    {
        Router::addRoute(Method::GET, '/blog', 'blog');
        Router::addRoute(Method::GET, '/blog/authors', 'blog_authors');
        Router::addRoute(Method::GET, '/blog/authors/comments', 'blog_authors_comments');
        Router::addRoute(Method::GET, '/blog/:post', 'blog_post');
        Router::addRoute(Method::GET, '/blog/:post/comments', 'blog_post_comments');
        Router::addRoute(Method::GET, '/blog/:post/comments/:comment', 'blog_post_comments_single');

        $this->assertEquals([
            'identifier' => 'blog',
        ], Router::match(Method::GET, '/blog'));
        $this->assertEquals([
            'identifier' => 'blog_authors',
        ], Router::match(Method::GET, '/blog/authors'));
        $this->assertEquals([
            'identifier' => 'blog_authors_comments',
        ], Router::match(Method::GET, '/blog/authors/comments'));
        $this->assertEquals([
            'identifier' => 'blog_post',
        ], Router::match(Method::GET, '/blog/:post'));
        $this->assertEquals([
            'identifier' => 'blog_post_comments',
        ], Router::match(Method::GET, '/blog/:post/comments'));
        $this->assertEquals([
            'identifier' => 'blog_post_comments_single',
        ], Router::match(Method::GET, '/blog/:post/comments/:comment'));
    }

    public function testCannotFindUnknownRoute(): void
    {
        $this->expectException(Throwable::class);

        $this->assertEquals('unknown', Router::match(Method::GET, '/404'));
    }
}
