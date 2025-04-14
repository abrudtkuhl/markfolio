<?php

namespace Markfolio\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;

class ContentImporter
{
    protected $contentDirectory;
    protected $htmlConverter;
    
    public function __construct(string $contentDirectory = null)
    {
        $this->contentDirectory = $contentDirectory ?? config('markfolio.content_directory', resource_path('content'));
        $this->htmlConverter = new HtmlConverter();
    }
    
    public function fromHtml(string $html, string $path, array $metadata = []): string
    {
        $markdown = $this->htmlConverter->convert($html);
        
        return $this->saveMarkdownFile($path, $markdown, $metadata);
    }
    
    public function fromUrl(string $url, string $path = null, array $metadata = []): string
    {
        $response = Http::get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch content from URL: {$url}");
        }
        
        $html = $response->body();
        
        // Try to extract title from HTML if not provided in metadata
        if (!isset($metadata['title'])) {
            preg_match('/<title>(.*?)<\/title>/i', $html, $matches);
            $metadata['title'] = $matches[1] ?? basename($url);
        }
        
        // Auto-generate path from URL if not provided
        if (!$path) {
            $path = $this->pathFromUrl($url);
        }
        
        // Extract main content (basic implementation)
        $mainContent = $this->extractMainContent($html);
        
        return $this->fromHtml($mainContent, $path, $metadata);
    }
    
    public function fromWordPress(string $wpApiUrl, string $targetDir = 'blog', int $limit = 100): array
    {
        $postsUrl = rtrim($wpApiUrl, '/') . '/wp-json/wp/v2/posts?per_page=' . $limit;
        
        $response = Http::get($postsUrl);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch WordPress posts from: {$wpApiUrl}");
        }
        
        $posts = $response->json();
        $importedFiles = [];
        
        foreach ($posts as $post) {
            $slug = $post['slug'];
            $title = $post['title']['rendered'] ?? 'Untitled';
            $content = $post['content']['rendered'] ?? '';
            $date = $post['date'] ?? now()->toIso8601String();
            
            $metadata = [
                'title' => $title,
                'created_at' => $date,
                'wordpress_id' => $post['id'],
                'status' => $post['status']
            ];
            
            if (isset($post['categories'])) {
                $metadata['categories'] = $post['categories'];
            }
            
            if (isset($post['tags'])) {
                $metadata['tags'] = $post['tags'];
            }
            
            // Create post path
            $path = $targetDir . '/' . $slug;
            
            $importedFiles[] = $this->fromHtml($content, $path, $metadata);
        }
        
        return $importedFiles;
    }
    
    public function fromCsv(string $csvPath, array $options = []): array
    {
        $options = array_merge([
            'delimiter' => ',',
            'title_column' => 'title',
            'content_column' => 'content',
            'path_column' => 'path',
            'has_header' => true,
            'directory' => '',
        ], $options);
        
        if (!File::exists($csvPath)) {
            throw new \Exception("CSV file not found: {$csvPath}");
        }
        
        $csv = array_map(function($line) use ($options) {
            return str_getcsv($line, $options['delimiter']);
        }, file($csvPath));
        
        if ($options['has_header']) {
            $headers = array_shift($csv);
            $titleIndex = array_search($options['title_column'], $headers);
            $contentIndex = array_search($options['content_column'], $headers);
            $pathIndex = array_search($options['path_column'], $headers);
            
            if ($titleIndex === false || $contentIndex === false) {
                throw new \Exception("Required columns not found in CSV headers");
            }
        } else {
            $titleIndex = 0;
            $contentIndex = 1;
            $pathIndex = 2;
        }
        
        $importedFiles = [];
        
        foreach ($csv as $row) {
            $title = $row[$titleIndex];
            $content = $row[$contentIndex];
            $path = $pathIndex !== false && isset($row[$pathIndex]) 
                ? $row[$pathIndex] 
                : Str::slug($title);
                
            if ($options['directory']) {
                $path = rtrim($options['directory'], '/') . '/' . $path;
            }
            
            $metadata = ['title' => $title];
            
            // Add all other columns as metadata
            if ($options['has_header']) {
                foreach ($headers as $i => $header) {
                    if ($i !== $contentIndex && $i !== $pathIndex) {
                        $metadata[$header] = $row[$i];
                    }
                }
            }
            
            // Detect if content is HTML or already markdown
            $isHtml = Str::contains($content, ['<p>', '<div>', '<h1>', '<br>']);
            
            if ($isHtml) {
                $importedFiles[] = $this->fromHtml($content, $path, $metadata);
            } else {
                $importedFiles[] = $this->saveMarkdownFile($path, $content, $metadata);
            }
        }
        
        return $importedFiles;
    }
    
    protected function saveMarkdownFile(string $path, string $content, array $metadata = []): string
    {
        $filePath = $this->contentDirectory . '/' . ltrim($path, '/');
        
        // Ensure .md extension
        if (!Str::endsWith($filePath, '.md')) {
            $filePath .= '.md';
        }
        
        // Ensure directory exists
        $dirName = dirname($filePath);
        if (!File::isDirectory($dirName)) {
            File::makeDirectory($dirName, 0755, true);
        }
        
        // Generate front matter
        $frontMatter = "---\n";
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $frontMatter .= "{$key}: {$value}\n";
        }
        
        // Ensure we have a title if not provided
        if (!isset($metadata['title'])) {
            $title = basename($path);
            $frontMatter .= "title: {$title}\n";
        }
        
        // Ensure we have a layout if not provided
        if (!isset($metadata['layout'])) {
            $layout = config('markfolio.default_layout', 'layouts.app');
            $frontMatter .= "layout: {$layout}\n";
        }
        
        $frontMatter .= "---\n\n";
        
        // Write the file
        File::put($filePath, $frontMatter . $content);
        
        return $filePath;
    }
    
    protected function pathFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Remove file extensions
        $path = preg_replace('/\.\w+$/', '', $path);
        
        // Remove leading/trailing slashes
        $path = trim($path, '/');
        
        // Replace remaining slashes with directory separators
        return $path;
    }
    
    protected function extractMainContent(string $html): string
    {
        // Basic content extraction - in reality you'd want a more
        // sophisticated algorithm or library for this
        $patterns = [
            '/<article[^>]*>(.*?)<\/article>/is',
            '/<div[^>]*?class="[^"]*?(?:post-content|entry-content|article-content)[^"]*?"[^>]*>(.*?)<\/div>/is',
            '/<div[^>]*?id="[^"]*?(?:post-content|entry-content|article-content)[^"]*?"[^>]*>(.*?)<\/div>/is',
            '/<div[^>]*?class="[^"]*?(?:content|main)[^"]*?"[^>]*>(.*?)<\/div>/is',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }
        
        // Fallback to body content
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches);
        return $matches[1] ?? $html;
    }
} 