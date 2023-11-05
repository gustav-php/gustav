<?php

function startServer()
{
    $command = getcwd() . DIRECTORY_SEPARATOR . 'rr serve -p >/dev/null 2>/dev/null &';
    exec($command);
    sleep(5);
}

function stopServer()
{
    $command = getcwd() . DIRECTORY_SEPARATOR . 'rr stop >/dev/null 2>/dev/null &';
    exec($command);
    sleep(5);
}

uses()
    ->beforeAll(function () {
        stopServer();
        startServer();
    })
    ->afterAll(function () {
        stopServer();
    })->in('Integration');
