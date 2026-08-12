@php
    $scriptName = trim((string) ($name ?? ''));
    $sourcePath = $scriptName !== '' ? resource_path("views/pages/scripts/{$scriptName}.blade.php") : null;
    $shouldInline = true;
    $assetUrl = null;

    if ($sourcePath && is_file($sourcePath)) {
        $sourceContent = file_get_contents($sourcePath);
        $hasBladeDirective = $sourceContent !== false
            && preg_match('/@json\s*\(|@if\b|@foreach\b|@php\b|@csrf\b|@can\b|@cannot\b|@role\b|\{\{(?!\s*[\'"`$])/', $sourceContent) === 1;

        if (!$hasBladeDirective) {
            try {
                $assetRelativePath = "js/page-scripts/{$scriptName}.js";
                $assetPath = public_path($assetRelativePath);
                $assetDirectory = dirname($assetPath);

                if (!is_dir($assetDirectory)) {
                    mkdir($assetDirectory, 0775, true);
                }

                $normalizedSource = preg_replace('/^\s*<script>\s*/i', '', (string) $sourceContent);
                $normalizedSource = preg_replace('/\s*<\/script>\s*$/i', '', (string) $normalizedSource);
                $sourceMtime = @filemtime($sourcePath) ?: time();
                $assetMtime = is_file($assetPath) ? (@filemtime($assetPath) ?: 0) : 0;

                if (!is_file($assetPath) || $sourceMtime > $assetMtime) {
                    file_put_contents($assetPath, $normalizedSource);
                    clearstatcache(true, $assetPath);
                    $assetMtime = @filemtime($assetPath) ?: $sourceMtime;
                }

                $shouldInline = false;
                $assetUrl = asset($assetRelativePath) . '?v=' . ($assetMtime ?: $sourceMtime);
            } catch (\Throwable $exception) {
                $shouldInline = true;
                $assetUrl = null;
            }
        }
    }
@endphp

@if ($shouldInline)
    @include("pages.scripts.{$scriptName}")
@elseif ($assetUrl)
    <script src="{{ $assetUrl }}"></script>
@endif
