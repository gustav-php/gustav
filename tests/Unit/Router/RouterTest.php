<?php

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Router\{Method, Router};

beforeEach(fn () => Router::reset());

test('can match url', function () {
    $routeIndex = new Route('/');
    $routeAbout = new Route('/about');
    $routeAboutMe = new Route('/about/me');

    Router::addRoute($routeIndex);
    Router::addRoute($routeAbout);
    Router::addRoute($routeAboutMe);

    expect($routeIndex)->toBe(Router::match(Method::GET, '/'));
    expect($routeAbout)->toBe(Router::match(Method::GET, '/about'));
    expect($routeAboutMe)->toBe(Router::match(Method::GET, '/about/me'));
});

test('can match url with placeholder', function () {
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

    expect($routeBlog)->toBe(Router::match(Method::GET, '/blog'));
    expect($routeBlogAuthors)->toBe(Router::match(Method::GET, '/blog/authors'));
    expect($routeBlogAuthorsComments)->toBe(Router::match(Method::GET, '/blog/authors/comments'));
    expect($routeBlogPost)->toBe(Router::match(Method::GET, '/blog/:post'));
    expect($routeBlogPostComments)->toBe(Router::match(Method::GET, '/blog/:post/comments'));
    expect($routeBlogPostCommentsSingle)->toBe(Router::match(Method::GET, '/blog/:post/comments/:comment'));
});

test('can match http method', function () {
    $routeGET = new Route('/');
    $routePOST = new Route('/', Method::POST);

    Router::addRoute($routeGET);
    Router::addRoute($routePOST);

    expect($routeGET)->toBe(Router::match(Method::GET, '/'));
    expect($routePOST)->toBe(Router::match(Method::POST, '/'));

    expect($routeGET)->not()->toBe(Router::match(Method::POST, '/'));
    expect($routePOST)->not()->toBe(Router::match(Method::GET, '/'));
});

test('cannot find unknwon route by path', function () {
    Router::match(Method::GET, '/404');
})->throws(Exception::class);

test('cannot find unknwon route by method', function () {
    $route = new Route('/404');
    Router::addRoute($route);

    expect($route)->toBe(Router::match(Method::GET, '/404'));

    Router::match(Method::POST, '/404');
})->throws(Exception::class);
