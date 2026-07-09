<?php

declare(strict_types=1);

namespace Snack\View\Extension;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

final class VueExtension implements ExtensionInterface
{
    public function __construct(private readonly string $publicPath)
    {
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('vueIsland', $this->vueIsland(...));
        $engine->registerFunction('vueIslandAttrs', $this->vueIslandAttrs(...));
        $engine->registerFunction('asset', $this->asset(...));
    }

    public function vueIsland(string $component, array $props = [], string $tag = 'div'): string
    {
        return sprintf(
            '<%1$s %2$s></%1$s>',
            $tag,
            $this->vueIslandAttrs($component, $props)
        );
    }

    public function vueIslandAttrs(string $component, array $props = []): string
    {
        $json = htmlspecialchars(
            json_encode($props, JSON_THROW_ON_ERROR),
            ENT_QUOTES,
            'UTF-8'
        );

        return sprintf(
            'data-vue-component="%s" data-vue-props="%s"',
            htmlspecialchars($component, ENT_QUOTES, 'UTF-8'),
            $json
        );
    }

    public function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $absolute = rtrim($this->publicPath, '/') . '/' . $relative;
        $version = is_file($absolute) ? (string) filemtime($absolute) : null;

        return '/' . $relative . ($version !== null ? '?v=' . $version : '');
    }
}
