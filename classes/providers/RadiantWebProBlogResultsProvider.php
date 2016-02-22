<?php
namespace OFFLINE\SiteSearch\Classes\Providers;

use Html;
use Illuminate\Database\Eloquent\Collection;
use OFFLINE\SiteSearch\Models\Settings as SiteSearchSettings;
use Radiantweb\Problog\Models\Post;
use Radiantweb\Problog\Models\Settings;
use System\Classes\PluginManager;

/**
 * Searches the contents generated by the
 * Radiantweb.Problog plugin
 *
 * @package OFFLINE\SiteSearch\Classes\Providers
 */
class RadiantWebProBlogResultsProvider extends ResultsProvider
{
    /**
     * Runs the search for this provider.
     *
     * @return ResultsProvider
     */
    public function search()
    {
        if ( ! $this->blogInstalledAndEnabled()) {
            return $this;
        }

        foreach ($this->posts() as $post) {
            // Make this result more relevant, if the query is found in the title
            $relevance = stripos($post->title, $this->query) === false ? 1 : 2;

            $this->addResult($post->title, $this->getSummary($post), $this->getUrl($post), $relevance);
        }

        return $this;
    }

    /**
     * Get all posts with matching title or content.
     *
     * @return Collection
     */
    protected function posts()
    {
        return Post::with('categories')
                   ->isPublished()
                   ->where('title', 'like', "%{$this->query}%")
                   ->orWhere('content', 'like', "%{$this->query}%")
                   ->orWhere('excerpt', 'like', "%{$this->query}%")
                   ->orderBy('published_at', 'desc')
                   ->get();
    }

    /**
     * Checks if the Radiantweb.Problog Plugin is installed and
     * enabled in the config.
     *
     * @return bool
     */
    protected function blogInstalledAndEnabled()
    {
        return PluginManager::instance()->hasPlugin('Radiantweb.Problog')
        && SiteSearchSettings::get('radiantweb_problog_enabled', true);
    }

    /**
     * Genreates the url to a blog post.
     *
     * @param $post
     *
     * @return string
     */
    protected function getUrl($post)
    {
        $url = trim(SiteSearchSettings::get('radiantweb_problog_posturl', '/blog'), '/');

        return implode('/', [$url, $post->categories->slug, $post->slug]);
    }

    /**
     * Get the post's excerpt if available
     * otherwise fall back to a limited content string.
     *
     * @param $post
     *
     * @return string
     */
    private function getSummary($post)
    {
        $excerpt = $post->excerpt;
        if (strlen(trim($excerpt))) {
            return $excerpt;
        }

        $content = $post->content;
        if (Settings::get('markdownMode')) {
            $content = Post::formatHtml($content);
        }

        return $content;
    }

    /**
     * Display name for this provider.
     *
     * @return mixed
     */
    public function displayName()
    {
        return SiteSearchSettings::get('radiantweb_problog_label', 'Blog');
    }
}
