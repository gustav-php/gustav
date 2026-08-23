<?php

use GustavPHP\Gustav\View;
use GustavPHP\Gustav\View\Exception\{ViewNotFoundException, ViewRenderingException};
use GustavPHP\Gustav\View\PhpViewRenderer;

function nativeViewRenderer(): PhpViewRenderer
{
    return new PhpViewRenderer(dirname(__DIR__, 2) . '/ViewFixtures');
}

it('renders escaped data, layouts, sections, partials, and explicitly trusted HTML', function () {
    $html = nativeViewRenderer()->render(new View('page', [
        'title' => 'Dogs & Friends',
        'heading' => '<Welcome>',
        'content' => '<script>alert("unsafe")</script>',
        'item' => 'Rex & Ada',
        'trusted' => '<em>trusted</em>',
    ]));

    expect($html)
        ->toContain('<title>Dogs &amp; Friends</title>')
        ->toContain('<strong>&lt;Welcome&gt;</strong>')
        ->toContain('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;')
        ->toContain('<span>Rex &amp; Ada</span>')
        ->toContain('<em>trusted</em>')
        ->not->toContain('<script>alert');
});

it('makes an object available as a typed model', function () {
    $model = new readonly class ('Gustav <Views>') {
        public function __construct(public string $title)
        {
        }
    };

    $html = nativeViewRenderer()->render(new View('model.php', $model));

    expect($html)->toContain('<h1>Gustav &lt;Views&gt;</h1>');
});

it('does not shadow valid variables with renderer implementation details', function () {
    $html = nativeViewRenderer()->render(new View('variables', [
        'reserved' => 'application value',
        'name' => 'Ada',
    ]));

    expect(trim($html))->toBe('application value:Ada');
});

it('does not leak sections between renders on a shared renderer', function () {
    $renderer = nativeViewRenderer();
    $first = $renderer->render(new View('page', [
        'title' => 'First',
        'heading' => 'First heading',
        'content' => 'First content',
        'item' => 'First item',
        'trusted' => '',
    ]));
    $second = $renderer->render(new View('page-without-heading', [
        'title' => 'Second',
        'content' => 'Second content',
    ]));

    expect($first)->toContain('First heading')
        ->and($second)->toContain('<header>Fallback</header>')
        ->not->toContain('First heading');
});

it('rejects missing views and paths outside the configured root', function (string $template, string $message) {
    expect(fn () => nativeViewRenderer()->render(new View($template)))
        ->toThrow(ViewNotFoundException::class, $message);
})->with([
    'missing view' => ['missing', "View 'missing' was not found"],
    'parent traversal' => ['../composer.json', 'must stay within the configured directory'],
    'absolute path' => ['/etc/passwd', 'must be a relative logical name'],
]);

it('requires a configured view directory', function () {
    expect(fn () => (new PhpViewRenderer(null))->render(new View('plain')))
        ->toThrow(ViewRenderingException::class, 'View directory is not configured');
});

it('rejects unsafe template variable names', function (array $data, string $message) {
    expect(fn () => nativeViewRenderer()->render(new View('plain', $data)))
        ->toThrow(ViewRenderingException::class, $message);
})->with([
    'invalid identifier' => [['invalid-name' => 'value'], "Invalid view variable 'invalid-name'"],
    'reserved view variable' => [['view' => 'value'], "View variable 'view' is reserved"],
    'reserved model variable' => [['model' => 'value'], "View variable 'model' is reserved"],
]);

it('restores output buffers when a template throws or leaves a section open', function (string $template, string $exception) {
    $renderer = nativeViewRenderer();
    $level = ob_get_level();

    expect(fn () => $renderer->render(new View($template)))
        ->toThrow($exception);

    expect(ob_get_level())->toBe($level)
        ->and($renderer->render(new View('plain', ['message' => 'still works'])))
        ->toContain('still works');
})->with([
    'template exception' => ['throws', RuntimeException::class],
    'unclosed section' => ['unclosed-section', ViewRenderingException::class],
]);

it('rejects circular layouts and partials', function (string $template, string $message) {
    expect(fn () => nativeViewRenderer()->render(new View($template)))
        ->toThrow(ViewRenderingException::class, $message);
})->with([
    'layout cycle' => ['circular-layout', 'Circular view layout detected'],
    'partial cycle' => ['circular-partial', 'Circular view partial detected'],
]);
