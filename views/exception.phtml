<?php $view->layout('layout', ['title' => $title, 'version' => $version]) ?>
<article>
    <h1><?= $view->escape($message) ?></h1>
    <table>
        <tbody>
            <tr>
                <th scope="row"><strong>Type</strong></th>
                <td><code><?= $view->escape($exception) ?></code></td>
            </tr>
            <tr>
                <th scope="row"><strong>Message</strong></th>
                <td><code><?= $view->escape($message) ?></code></td>
            </tr>
            <tr>
                <th scope="row"><strong>Code</strong></th>
                <td><code><?= $view->escape($code) ?></code></td>
            </tr>
            <tr>
                <th scope="row"><strong>File</strong></th>
                <td><code><?= $view->escape($file) ?></code></td>
            </tr>
            <tr>
                <th scope="row"><strong>Line</strong></th>
                <td><code><?= $view->escape($line) ?></code></td>
            </tr>
        </tbody>
    </table>
    <pre class="snippet"><code><?= $view->escape($snippet) ?></code></pre>
</article>
<article>
    <?php foreach ($trace as $entry): ?>
        <details>
            <summary>
                <small><?= $view->escape($entry->file) ?>:<?= $view->escape($entry->line) ?></small>
            </summary>
            <table>
                <tbody>
                    <tr>
                        <th scope="row"><strong>Function</strong></th>
                        <td>
                            <code><?= $view->escape($entry->class) ?><?= $view->escape($entry->type) ?><?= $view->escape($entry->function) ?></code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><strong>File</strong></th>
                        <td><code><?= $view->escape($entry->file) ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><strong>Line</strong></th>
                        <td><code><?= $view->escape($entry->line) ?></code></td>
                    </tr>
                </tbody>
            </table>
            <pre class="snippet"><code><?= $view->escape($entry->snippet) ?></code></pre>
        </details>
    <?php endforeach ?>
</article>
<script type="module">
    import { codeToHtml } from "https://esm.sh/shiki@1.6.0";

    document.querySelectorAll(".snippet").forEach(async (block) => {
        block.outerHTML = await codeToHtml(
            block.querySelector("code").textContent,
            {
                lang: "php",
                theme: "dracula",
                decorations: [
                    {
                        start: { line: 0, character: 0 },
                        end: { line: 4, character: 0 },
                        properties: { class: "highlighted-word" },
                    },
                ],
            },
        );
    });
</script>
