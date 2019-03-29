<?php

namespace Drupal\feeds_recursive_directory_contents_fetcher\Feeds\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "recursive_dir_contents",
 *   title = @Translation("Recursive Directory Contents"),
 *   description = @Translation("Provides contents of a local directory."),
 *   form = {
 *     "configuration" = "Drupal\feeds_recursive_directory_contents_fetcher\Feeds\Fetcher\Form\FeedsRecursiveDirectoryContentsFetcherForm"
 *   },
 *   arguments = {"@cache.feeds_download", "@file_system"}
 * )
 */
class FeedsRecursiveDirectoryContentsFetcher extends PluginBase implements ClearableInterface, FetcherInterface {


    /**
     * The cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected $cache;

    /**
     * Drupal file system helper.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * Constructs a Fetcher object.
     *
     * @param array $configuration
     *   The plugin configuration.
     * @param string $plugin_id
     *   The plugin id.
     * @param array $plugin_definition
     *   The plugin definition.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache
     *   The cache backend.
     * @param \Drupal\Core\File\FileSystemInterface $file_system
     *   The Drupal file system helper.
     */
    public function __construct(array $configuration, $plugin_id, array $plugin_definition, CacheBackendInterface $cache, FileSystemInterface $file_system) {
        $this->cache = $cache;
        $this->fileSystem = $file_system;
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(FeedInterface $feed, StateInterface $state) {

        $sink = $this->fileSystem->tempnam('temporary://', 'recursive_dir_fetcher');
        $sinkRealPath = $this->fileSystem->realpath($sink);

        //$response = $this->get($sink, $this->getCacheKey($feed));
        $dirs = $this->getDirContents($this->configuration['local_dir']);
        $dirsImploded = implode("\r\n",$dirs);

        file_save_data($dirsImploded, $sink, FILE_EXISTS_REPLACE);

        // Determine actual path of saved file
        $sinkRealPath = $this->fileSystem->realpath($sink);

        return new FetcherResult($sinkRealPath);
    }

    /**
     * @param $dir
     * @param array $results
     * @return array
     */
    private function getDirContents($dir, &$results = array()){
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }

    /**
     * Returns the download cache key for a given feed.
     *
     * @param \Drupal\feeds\FeedInterface $feed
     *   The feed to find the cache key for.
     *
     * @return string
     *   The cache key for the feed.
     */
    protected function getCacheKey(FeedInterface $feed) {
        return $feed->id() . ':' . hash('sha256', $feed->getSource());
    }

    /**
     * {@inheritdoc}
     */
    public function clear(FeedInterface $feed, StateInterface $state) {
        $this->onFeedDeleteMultiple([$feed]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return [
            'local_dir' => FALSE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onFeedDeleteMultiple(array $feeds) {
        foreach ($feeds as $feed) {
            $this->cache->delete($this->getCacheKey($feed));
        }
    }

}
