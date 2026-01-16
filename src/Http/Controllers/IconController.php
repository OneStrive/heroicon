<?php

namespace OneStrive\Heroicon\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IconController extends Controller
{
    /**
     * Get icons for specified sets
     * GET /nova-vendor/heroicon/icons?sets[]=solid&sets[]=outline
     */
    public function index(Request $request): JsonResponse
    {
        $requestedSets = $request->input('sets', []);
        $supportedSets = $this->getSupportedSets();

        $icons = [];

        foreach ($requestedSets as $setKey) {
            if ($set = $this->findSupportedSet($setKey, $supportedSets)) {
                $icons[$setKey] = [
                    'value' => $set['value'],
                    'label' => $set['label'],
                    'icons' => $this->loadIcons($set['value'], $set['path'])
                ];
            }
        }

        return response()->json(['iconSets' => $icons]);
    }

    /**
     * Load icons from disk (extracted from Heroicon::prepareIcons)
     */
    protected function loadIcons(string $key, string $path): array
    {
        $icons = [];

        if (!is_dir($path)) {
            return $icons;
        }

        $files = scandir($path);

        foreach ($files as $file) {
            if (preg_match("/.*\.svg/i", $file)) {
                $filePath = "$path/$file";
                $content = file_get_contents($filePath);
                $content = preg_replace('/<!--(.*?)-->/m', '', $content);
                $icons[] = [
                    'type'    => $key,
                    'name'    => strtolower(str_replace('.svg', '', $file)),
                    'content' => $content,
                ];
            }
        }

        return $icons;
    }

    /**
     * Get supported icon sets with their paths
     */
    protected function getSupportedSets(): array
    {
        return [
            ['value' => 'solid', 'label' => 'Heroicons solid', 'path' => __DIR__ . '/../../../resources/icons/heroicons/solid'],
            ['value' => 'outline', 'label' => 'Heroicons outline', 'path' => __DIR__ . '/../../../resources/icons/heroicons/outline'],
            ['value' => 'fa-brands', 'label' => 'Font Awesome brands', 'path' => __DIR__ . '/../../../resources/icons/fa/free/brands'],
            ['value' => 'fa-regular', 'label' => 'Font Awesome regular', 'path' => __DIR__ . '/../../../resources/icons/fa/free/regular'],
            ['value' => 'fa-solid', 'label' => 'Font Awesome solid', 'path' => __DIR__ . '/../../../resources/icons/fa/free/solid'],
        ];
    }

    /**
     * Find a supported set by key
     */
    protected function findSupportedSet(string $key, array $sets): ?array
    {
        foreach ($sets as $set) {
            if ($set['value'] === $key) {
                return $set;
            }
        }
        return null;
    }
}
