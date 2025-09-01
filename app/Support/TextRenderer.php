<?php


namespace App\Support;


use App\Enums\TextContentType;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

class TextRenderer
{
    public function toHtml(string $content, TextContentType $type): string
    {
        return match ($type) {
            TextContentType::Html => $content,
            TextContentType::Markdown => $this->renderMarkdownToHtml($content)
        };
    }

    protected function renderMarkdownToHtml(string $markdown): string
    {
        $parser = $this->getMarkdownParser();

        $document = $parser->parse($markdown);

        $renderer = $this->getMarkdownRenderer();

        return $renderer->renderDocument($document)->getContent();
    }

    protected function getMarkdownParser(): MarkdownParser
    {
        return new MarkdownParser(
            (new Environment)
                ->addExtension(new CommonMarkCoreExtension)
        );
    }

    protected function getMarkdownRenderer(): HtmlRenderer
    {
        return new HtmlRenderer(
            (new Environment)
                ->addExtension(new CommonMarkCoreExtension)
        );
    }
}
