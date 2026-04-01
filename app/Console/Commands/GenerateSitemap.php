<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Service;
use DOMDocument;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for AURA Derma Clinic';

    /**
     * The base URL for the sitemap.
     */
    protected string $baseUrl;

    /**
     * The XML document.
     */
    protected DOMDocument $dom;

    /**
     * The root <urlset> element.
     */
    protected \DOMElement $urlset;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->baseUrl = rtrim(config('app.url'), '/');

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        $this->urlset = $this->dom->createElement('urlset');
        $this->urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->dom->appendChild($this->urlset);

        $locales = ['ar', 'en'];
        $today = now()->toDateString();

        // -------------------------------------------------------
        // Static pages common to both locales
        // -------------------------------------------------------
        foreach ($locales as $locale) {
            // Home
            $this->addUrl("/{$locale}", $today, 'daily', '1.0');

            // About
            $this->addUrl("/{$locale}/about", $today, 'monthly', '0.6');

            // Services index
            $this->addUrl("/{$locale}/services", $today, 'weekly', '0.9');

            // Gallery
            $this->addUrl("/{$locale}/gallery", $today, 'monthly', '0.6');

            // Offers
            $this->addUrl("/{$locale}/offers", $today, 'weekly', '0.7');

            // Blog index
            $this->addUrl("/{$locale}/blog", $today, 'weekly', '0.7');

            // FAQ
            $this->addUrl("/{$locale}/faq", $today, 'monthly', '0.6');

            // Booking
            $this->addUrl("/{$locale}/booking", $today, 'monthly', '0.6');

            // Contact
            $this->addUrl("/{$locale}/contact", $today, 'monthly', '0.6');
        }

        // -------------------------------------------------------
        // Dynamic: Individual services
        // -------------------------------------------------------
        $services = Service::active()->get();

        foreach ($services as $service) {
            $lastmod = $service->updated_at?->toDateString() ?? $today;

            foreach ($locales as $locale) {
                $this->addUrl("/{$locale}/services/{$service->slug}", $lastmod, 'weekly', '0.8');
            }
        }

        $this->info("Added {$services->count()} active services.");

        // -------------------------------------------------------
        // Dynamic: Published blog posts
        // -------------------------------------------------------
        $posts = Post::published()->get();

        foreach ($posts as $post) {
            $lastmod = $post->updated_at?->toDateString() ?? $today;

            foreach ($locales as $locale) {
                $this->addUrl("/{$locale}/blog/{$post->slug}", $lastmod, 'weekly', '0.7');
            }
        }

        $this->info("Added {$posts->count()} published posts.");

        // -------------------------------------------------------
        // Write the XML file
        // -------------------------------------------------------
        $path = public_path('sitemap.xml');
        $bytes = $this->dom->save($path);

        if ($bytes === false) {
            $this->error('Failed to write sitemap.xml');

            return self::FAILURE;
        }

        $totalUrls = $this->urlset->childNodes->length;
        $this->info("Sitemap generated successfully at {$path} ({$totalUrls} URLs).");

        return self::SUCCESS;
    }

    /**
     * Add a single <url> entry to the sitemap.
     */
    protected function addUrl(string $path, string $lastmod, string $changefreq, string $priority): void
    {
        $url = $this->dom->createElement('url');

        $loc = $this->dom->createElement('loc', $this->baseUrl . $path);
        $url->appendChild($loc);

        $lastmodEl = $this->dom->createElement('lastmod', $lastmod);
        $url->appendChild($lastmodEl);

        $changefreqEl = $this->dom->createElement('changefreq', $changefreq);
        $url->appendChild($changefreqEl);

        $priorityEl = $this->dom->createElement('priority', $priority);
        $url->appendChild($priorityEl);

        $this->urlset->appendChild($url);
    }
}
