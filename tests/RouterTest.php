<?php

use PHPUnit\Framework\TestCase;
use TorstenDittmann\Gustav\Attributes\Route;
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
        $routeIndex = new Route('/');
        $routeAbout = new Route('/about');
        $routeAboutMe = new Route('/about/me');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutMe);

        $this->assertEquals($routeIndex, Router::match(Method::GET, '/'));
        $this->assertEquals($routeAbout, Router::match(Method::GET, '/about'));
        $this->assertEquals($routeAboutMe, Router::match(Method::GET, '/about/me'));
    }

    public function testCanMatchUrlWithPlaceholder(): void
    {
        $routeBlog = new Route('/blog');
        $routeBlogAuthors = new Route('/blog/authors');
        $routeBlogAuthorsComments = new Route('/blog/authors/comments');
        $routeBlogPost = new Route('/blog/:post');
        $routeBlogPostComments = new Route('/blog/:post/comments');
        $routeBlogPostCommentsSingle = new Route('/blog/:post/comments/:comment');

        Router::addRoute($routeBlog);
        Router::addRoute($routeBlogAuthors);
        Router::addRoute($routeBlogAuthorsComments);
        Router::addRoute($routeBlogPost);
        Router::addRoute($routeBlogPostComments);
        Router::addRoute($routeBlogPostCommentsSingle);

        $this->assertEquals($routeBlog, Router::match(Method::GET, '/blog'));
        $this->assertEquals($routeBlogAuthors, Router::match(Method::GET, '/blog/authors'));
        $this->assertEquals($routeBlogAuthorsComments, Router::match(Method::GET, '/blog/authors/comments'));
        $this->assertEquals($routeBlogPost, Router::match(Method::GET, '/blog/:post'));
        $this->assertEquals($routeBlogPostComments, Router::match(Method::GET, '/blog/:post/comments'));
        $this->assertEquals($routeBlogPostCommentsSingle, Router::match(Method::GET, '/blog/:post/comments/:comment'));
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route('/');
        $routePOST = new Route('/', Method::POST);

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertEquals($routeGET, Router::match(Method::GET, '/'));
        $this->assertEquals($routePOST, Router::match(Method::POST, '/'));

        $this->assertNotEquals($routeGET, Router::match(Method::POST, '/'));
        $this->assertNotEquals($routePOST, Router::match(Method::GET, '/'));
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->expectException(Throwable::class);

        $this->assertEquals('unknown', Router::match(Method::GET, '/404'));
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route('/404');
        Router::addRoute($route);

        $this->assertEquals($route, Router::match(Method::GET, '/404'));

        $this->expectException(Throwable::class);

        Router::match(Method::POST, '/404');
    }
}
