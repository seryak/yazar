<?php

namespace Yazar\Markdown\Extensions;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;

class DiskUrlExtension implements ExtensionInterface
{
    private const PATTERN = '/disk(?:\(([\w.-]+)\))?:\/\/([^\s"\'<>)]+)/';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolve(...));
    }

    public function resolve(DocumentParsedEvent $event): void
    {
        foreach (new NodeIterator($event->getDocument()) as $node) {
            match (true) {
                $node instanceof Link, $node instanceof Image => $node->setUrl(self::replace($node->getUrl())),
                $node instanceof Text, $node instanceof HtmlInline, $node instanceof HtmlBlock => $node->setLiteral(self::replace($node->getLiteral())),
                default => null,
            };
        }
    }

    private static function replace(string $value): string
    {
        return preg_replace_callback(
            self::PATTERN,
            static function (array $matches): string {
                $disk = $matches[1] !== '' ? $matches[1] : self::defaultDisk();

                try {
                    return Storage::disk($disk)->url($matches[2]);
                } catch (\Throwable $e) {
                    throw new DiskUrlResolutionException(
                        "Unable to resolve disk({$disk})://{$matches[2]}: {$e->getMessage()}",
                        previous: $e,
                    );
                }
            },
            $value,
        ) ?? $value;
    }

    private static function defaultDisk(): ?string
    {
        $disk = config('yazar.markdown.default_disk');

        return is_string($disk) ? $disk : null;
    }
}
