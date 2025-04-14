<?php

namespace Markfolio;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class ContentManager
{
    protected string $contentDirectory;
    
    public function __construct(string $contentDirectory = null)
    {
        $this->contentDirectory = $contentDirectory ?? config('markfolio.content_directory', resource_path('content'));
    }
    
    /**
     * Get all markdown pages
     *
     * @param string|null $directory Subdirectory to get pages from
     * @param bool $recursive Whether to search recursively
     * @return Collection
     */
    public function all(string $directory = null, bool $recursive = true): Collection
    {
        $cacheEnabled = config('markfolio.cache.enabled', true);
        $cacheTtl = config('markfolio.cache.ttl', 3600);
        $cacheKey = 'markfolio_all_' . md5($directory ?? 'root' . '_' . ($recursive ? 'r' : 'nr'));
        
        if ($cacheEnabled) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($directory, $recursive) {
                return $this->getAllPages($directory, $recursive);
            });
        }
        
        return $this->getAllPages($directory, $recursive);
    }
    
    /**
     * Find a specific page by path
     *
     * @param string $path Path to the page, without .md extension
     * @return array|null Page data or null if not found
     */
    public function find(string $path): ?array
    {
        // Normalize path
        $path = trim($path, '/');
        
        $fullPath = $this->contentDirectory . '/' . $path . '.md';
        
        if (!File::exists($fullPath)) {
            return null;
        }
        
        return $this->parseFile($fullPath);
    }
    
    /**
     * Filter pages based on metadata criteria
     *
     * @param array $criteria Key-value pairs to match against metadata
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function where(array $criteria, string $directory = null): Collection
    {
        return $this->all($directory)->filter(function ($page) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if (!isset($page['meta'][$key]) || $page['meta'][$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Get pages sorted by a metadata field
     *
     * @param string $field Metadata field to sort by
     * @param string $direction Sort direction (asc or desc)
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function sortBy(string $field, string $direction = 'asc', string $directory = null): Collection
    {
        $pages = $this->all($directory);
        
        if ($direction === 'desc') {
            return $pages->sortByDesc(function ($page) use ($field) {
                return $page['meta'][$field] ?? null;
            });
        }
        
        return $pages->sortBy(function ($page) use ($field) {
            return $page['meta'][$field] ?? null;
        });
    }
    
    /**
     * Get pages tagged with specific tag(s)
     *
     * @param string|array $tags Tag or tags to search for
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function withTags($tags, string $directory = null): Collection
    {
        $tags = is_array($tags) ? $tags : [$tags];
        
        return $this->all($directory)->filter(function ($page) use ($tags) {
            $pageTags = $page['meta']['tags'] ?? [];
            
            // If tags is a JSON string, decode it
            if (is_string($pageTags) && Str::startsWith($pageTags, '[')) {
                $pageTags = json_decode($pageTags, true) ?? [];
            }
            
            // If it's still a string, it might be comma-separated
            if (is_string($pageTags)) {
                $pageTags = array_map('trim', explode(',', $pageTags));
            }
            
            foreach ($tags as $tag) {
                if (in_array($tag, $pageTags)) {
                    return true;
                }
            }
            
            return false;
        });
    }
    
    /**
     * Get published pages
     *
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function published(string $directory = null): Collection
    {
        return $this->where(['status' => 'published'], $directory)
            ->merge($this->all($directory)->filter(function ($page) {
                // If no status is specified, consider it published
                return !isset($page['meta']['status']);
            }));
    }
    
    /**
     * Get draft pages
     *
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function drafts(string $directory = null): Collection
    {
        return $this->where(['status' => 'draft'], $directory);
    }
    
    /**
     * Get collection of unique tags from all pages
     *
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function tags(string $directory = null): Collection
    {
        $allTags = collect();
        
        $this->all($directory)->each(function ($page) use ($allTags) {
            $pageTags = $page['meta']['tags'] ?? [];
            
            // If tags is a JSON string, decode it
            if (is_string($pageTags) && Str::startsWith($pageTags, '[')) {
                $pageTags = json_decode($pageTags, true) ?? [];
            }
            
            // If it's still a string, it might be comma-separated
            if (is_string($pageTags)) {
                $pageTags = array_map('trim', explode(',', $pageTags));
            }
            
            $allTags = $allTags->merge($pageTags);
        });
        
        return $allTags->unique()->values();
    }
    
    /**
     * Get collection of unique categories from all pages
     *
     * @param string|null $directory Subdirectory to search in
     * @return Collection
     */
    public function categories(string $directory = null): Collection
    {
        $allCategories = collect();
        
        $this->all($directory)->each(function ($page) use ($allCategories) {
            $pageCategories = $page['meta']['categories'] ?? $page['meta']['category'] ?? [];
            
            // If categories is a JSON string, decode it
            if (is_string($pageCategories) && Str::startsWith($pageCategories, '[')) {
                $pageCategories = json_decode($pageCategories, true) ?? [];
            }
            
            // If it's still a string, it might be comma-separated or a single category
            if (is_string($pageCategories)) {
                if (Str::contains($pageCategories, ',')) {
                    $pageCategories = array_map('trim', explode(',', $pageCategories));
                } else {
                    $pageCategories = [$pageCategories];
                }
            }
            
            $allCategories = $allCategories->merge($pageCategories);
        });
        
        return $allCategories->unique()->values();
    }
    
    /**
     * Clear the content cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('markfolio_all_root_r');
        
        // Also try to clear any other cached content
        $keys = Cache::get('markfolio_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('markfolio_cache_keys');
    }
    
    protected function getAllPages(string $directory = null, bool $recursive = true): Collection
    {
        $directory = $directory !== null 
            ? rtrim($this->contentDirectory, '/') . '/' . ltrim($directory, '/')
            : $this->contentDirectory;
            
        $pattern = $recursive 
            ? $directory . '/**/*.md'
            : $directory . '/*.md';
            
        $files = glob($pattern);
        
        return collect($files)->map(function ($file) {
            return $this->parseFile($file);
        });
    }
    
    protected function parseFile(string $path): array
    {
        $document = YamlFrontMatter::parseFile($path);
        $parser = new PageParser($path);
        $data = $parser->toViewData();
        
        // Add path and URL info
        $relativePath = str_replace([$this->contentDirectory, '.md'], '', $path);
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        
        $data['path'] = $relativePath;
        $data['url'] = '/' . $relativePath;
        
        return $data;
    }
} 